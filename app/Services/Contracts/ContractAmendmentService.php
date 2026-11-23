<?php

namespace App\Services\Contracts;

use App\Models\Bill;
use App\Models\ContractAmendment;
use App\Models\Product;
use App\Models\Proposal;
use App\Models\User;
use App\Services\CommissionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Upgrade / Downgrade de contrato (aditivo).
 *
 * Regras:
 * - O número do contrato NÃO muda; a proposta ganha uma nova versão (current_version + 1).
 * - Um espelho completo da versão anterior é salvo em contract_amendments.snapshot.
 * - Valor já pago (entrada + saldo) entra como pagamento no novo contrato
 *   (categoria "credito_contrato_anterior", informativa, não gera parcela).
 * - Downgrade: se o valor pago for maior que o novo valor, a sobra fica como
 *   saldo credor (proposals.credit_balance). Não há devolução automática.
 * - Parcelas de entrada/saldo em aberto da versão anterior são canceladas e
 *   marcadas como substituídas pelo aditivo (replaced_by_amendment_id).
 *   Parcelas pagas permanecem pagas. Parcialmente pagas viram pagas pelo valor recebido.
 * - Taxa de manutenção: mantida como está (parcelas e negociação não são alteradas).
 * - Taxa de contrato: opcional no aditivo (charge_contract_fee).
 * - Comissão: upgrade gera comissão sobre a diferença; downgrade estorna proporcionalmente.
 * - Dashboard/relatórios: não é venda nova; a proposta original é atualizada.
 */
class ContractAmendmentService
{
    /** Categorias que compõem o valor principal do contrato. */
    public const PRINCIPAL_CATEGORIES = ['entrada', 'saldo'];

    /** Categorias da negociação anterior que são mantidas na nova versão sem gerar novas parcelas. */
    public const CARRIED_CATEGORIES = ['taxa_manutencao', 'taxa_contrato'];

    public const CREDIT_CATEGORY = 'credito_contrato_anterior';
    public const CREDIT_PAYMENT_METHOD = 'Crédito do Contrato Anterior';

    public function __construct(
        private ProposalBillGenerator $billGenerator,
        private CommissionService $commissionService,
    ) {
    }

    /**
     * Resumo financeiro atual do contrato (para pré-visualização na tela).
     */
    public function financialSummary(Proposal $proposal): array
    {
        $paid = $this->paidPrincipal($proposal);

        $openPrincipal = (float) Bill::where('proposal_id', $proposal->id)
            ->whereIn('category', self::PRINCIPAL_CATEGORIES)
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->selectRaw("COALESCE(SUM(amount + COALESCE(interest_amount,0) - CASE WHEN status = 'partially_paid' THEN COALESCE(paid_amount,0) ELSE 0 END),0) as total")
            ->value('total');

        return [
            'total_value' => (float) $proposal->total_value,
            'paid_principal' => $paid,
            'open_principal' => round($openPrincipal, 2),
            'credit_balance' => (float) ($proposal->credit_balance ?? 0),
            'current_version' => (int) ($proposal->current_version ?? 1),
        ];
    }

    /**
     * Simula o aditivo (sem gravar) para a tela mostrar o crédito e o restante a negociar.
     */
    public function preview(Proposal $proposal, float $newTotal): array
    {
        $paid = $this->paidPrincipal($proposal);
        $creditApplied = round(min($paid, $newTotal), 2);

        return [
            'old_total_value' => (float) $proposal->total_value,
            'new_total_value' => round($newTotal, 2),
            'difference_amount' => round($newTotal - (float) $proposal->total_value, 2),
            'paid_amount' => $paid,
            'credit_applied' => $creditApplied,
            'credit_balance' => round($paid - $creditApplied, 2),
            'remaining_amount' => round($newTotal - $creditApplied, 2),
        ];
    }

    /**
     * Aplica o upgrade/downgrade.
     *
     * @param array $data {
     *   type: 'upgrade'|'downgrade',
     *   product_id: int,
     *   total_value: float,              // novo valor base (entrada + saldo)
     *   quantity: ?int,
     *   reason: string,
     *   details: ?string,
     *   charge_contract_fee: bool,
     *   commission_reversal_responsible: ?string,
     *   payments: array<array{category,payment_method,installments,installment_value,total_value,start_date}>
     * }
     */
    public function apply(Proposal $proposal, array $data, ?User $user = null): ContractAmendment
    {
        return DB::transaction(function () use ($proposal, $data, $user) {
            /** @var Proposal $proposal */
            $proposal = Proposal::whereKey($proposal->id)->lockForUpdate()->firstOrFail();
            $proposal->load(['product', 'payments', 'bills', 'commissions.installments', 'salesService']);

            $this->assertCanAmend($proposal);

            $type = $data['type'];
            $oldTotal = (float) $proposal->total_value;
            $newTotal = round((float) $data['total_value'], 2);
            $newProduct = Product::findOrFail($data['product_id']);

            if ($type === ContractAmendment::TYPE_UPGRADE && $newTotal < $oldTotal) {
                throw ValidationException::withMessages(['total_value' => 'No upgrade o novo valor não pode ser menor que o valor atual do contrato.']);
            }
            if ($type === ContractAmendment::TYPE_DOWNGRADE && $newTotal > $oldTotal) {
                throw ValidationException::withMessages(['total_value' => 'No downgrade o novo valor não pode ser maior que o valor atual do contrato.']);
            }
            if ($newTotal == $oldTotal && (int) $newProduct->id === (int) $proposal->product_id
                && (int) ($data['quantity'] ?? 0) === (int) ($proposal->quantity ?? 0)) {
                throw ValidationException::withMessages(['product_id' => 'Nenhuma alteração de produto, valor ou pontuação foi informada.']);
            }

            $calc = $this->preview($proposal, $newTotal);
            $newPayments = collect($data['payments'] ?? []);
            $chargeFee = (bool) ($data['charge_contract_fee'] ?? false);

            // Taxa de contrato só entra se marcado
            if (!$chargeFee) {
                $newPayments = $newPayments->reject(fn ($p) => $p['category'] === 'taxa_contrato');
            }
            // Manutenção é mantida da negociação anterior; crédito é calculado pelo sistema
            $newPayments = $newPayments->reject(fn ($p) => in_array($p['category'], ['taxa_manutencao', self::CREDIT_CATEGORY], true))->values();

            $negotiated = round($newPayments->whereIn('category', self::PRINCIPAL_CATEGORIES)->sum('total_value'), 2);
            if (abs($negotiated - $calc['remaining_amount']) > 0.01) {
                throw ValidationException::withMessages([
                    'payments' => sprintf(
                        'A soma de entrada + saldo negociados (R$ %s) deve ser igual ao restante a negociar (R$ %s).',
                        number_format($negotiated, 2, ',', '.'),
                        number_format($calc['remaining_amount'], 2, ',', '.')
                    ),
                ]);
            }

            $fromVersion = (int) ($proposal->current_version ?? 1);
            $toVersion = $fromVersion + 1;

            // 1. Espelho da versão anterior
            $amendment = ContractAmendment::create([
                'proposal_id' => $proposal->id,
                'user_id' => $user?->id,
                'type' => $type,
                'from_version' => $fromVersion,
                'to_version' => $toVersion,
                'from_product_id' => $proposal->product_id,
                'to_product_id' => $newProduct->id,
                'old_total_value' => $oldTotal,
                'new_total_value' => $newTotal,
                'old_quantity' => $proposal->quantity,
                'new_quantity' => $data['quantity'] ?? null,
                'paid_amount' => $calc['paid_amount'],
                'credit_applied' => $calc['credit_applied'],
                'credit_balance' => $calc['credit_balance'],
                'difference_amount' => $calc['difference_amount'],
                'remaining_amount' => $calc['remaining_amount'],
                'charge_contract_fee' => $chargeFee,
                'contract_fee_amount' => round($newPayments->where('category', 'taxa_contrato')->sum('total_value'), 2),
                'commission_reversal_responsible' => $type === ContractAmendment::TYPE_DOWNGRADE
                    ? ($data['commission_reversal_responsible'] ?? ContractAmendment::RESPONSIBLE_UNDEFINED)
                    : null,
                'snapshot' => $this->buildSnapshot($proposal),
                'reason' => $data['reason'],
                'details' => $data['details'] ?? null,
                'status' => 'completed',
            ]);

            // 2. Parcelas de entrada/saldo em aberto da versão anterior -> substituídas
            $this->replaceOpenPrincipalBills($proposal, $amendment, $user);

            // 3. Negociação anterior deixa de ser a vigente (fica como histórico)
            $oldPayments = $proposal->payments;
            $proposal->allPayments()->where('is_current', true)->update(['is_current' => false]);

            // 4. Nova negociação
            $paymentAttrs = ['version' => $toVersion, 'is_current' => true, 'contract_amendment_id' => $amendment->id];

            if ($calc['credit_applied'] > 0) {
                $proposal->allPayments()->create(array_merge([
                    'category' => self::CREDIT_CATEGORY,
                    'payment_method' => self::CREDIT_PAYMENT_METHOD,
                    'installments' => 1,
                    'installment_value' => $calc['credit_applied'],
                    'total_value' => $calc['credit_applied'],
                    'start_date' => today(),
                ], $paymentAttrs));
            }

            // Mantém taxas de manutenção e de contrato já negociadas (sem gerar novas parcelas)
            foreach ($oldPayments->whereIn('category', self::CARRIED_CATEGORIES) as $old) {
                $proposal->allPayments()->create(array_merge(
                    $old->only(['category', 'payment_method', 'installments', 'installment_value', 'total_value', 'start_date']),
                    $paymentAttrs
                ));
            }

            $billablePayments = [];
            foreach ($newPayments as $p) {
                $billablePayments[] = $proposal->allPayments()->create(array_merge([
                    'category' => $p['category'],
                    'payment_method' => $p['payment_method'],
                    'installments' => $p['installments'],
                    'installment_value' => $p['installment_value'],
                    'total_value' => $p['total_value'],
                    'start_date' => $p['start_date'],
                ], $paymentAttrs));
            }

            // 5. Atualiza o contrato (mesmo número)
            $taxes = round($proposal->allPayments()->where('is_current', true)->where('category', 'taxa_contrato')->sum('total_value'), 2);
            $proposal->update([
                'product_id' => $newProduct->id,
                'total_value' => $newTotal,
                'base_value' => $newTotal,
                'taxes' => $taxes,
                'gross_value' => $newTotal + $taxes,
                'quantity' => $data['quantity'] ?? null,
                'current_version' => $toVersion,
                'credit_balance' => $calc['credit_balance'],
            ]);
            $proposal->setRelation('product', $newProduct);

            // 6. Parcelas da nova negociação
            $this->billGenerator->generate(
                $proposal,
                $billablePayments,
                ['version' => $toVersion, 'contract_amendment_id' => $amendment->id],
                " (Aditivo v{$toVersion} - " . ContractAmendment::TYPE_LABELS[$type] . ')'
            );

            // 7. Comissões
            if ($type === ContractAmendment::TYPE_UPGRADE) {
                $this->commissionService->generateAmendmentCommissions($proposal, $amendment);
            } else {
                $this->commissionService->reverseAmendmentCommissions($proposal, $amendment);
            }

            return $amendment->fresh(['fromProduct', 'toProduct', 'payments', 'bills', 'replacedBills', 'commissions', 'commissionReversals']);
        });
    }

    public function assertCanAmend(Proposal $proposal): void
    {
        if ($proposal->status === 'cancelled') {
            throw ValidationException::withMessages(['proposal_id' => 'Contrato cancelado (distrato) não pode receber upgrade/downgrade.']);
        }
        if ($proposal->status !== 'approved') {
            throw ValidationException::withMessages(['proposal_id' => 'Somente contratos aprovados podem receber upgrade/downgrade.']);
        }
    }

    /** Total recebido de entrada + saldo em todas as versões do contrato. */
    public function paidPrincipal(Proposal $proposal): float
    {
        return round((float) Bill::where('proposal_id', $proposal->id)
            ->whereIn('category', self::PRINCIPAL_CATEGORIES)
            ->whereIn('status', ['paid', 'partially_paid'])
            ->sum('paid_amount'), 2);
    }

    private function replaceOpenPrincipalBills(Proposal $proposal, ContractAmendment $amendment, ?User $user): void
    {
        $label = ContractAmendment::TYPE_LABELS[$amendment->type];
        $who = $user ? " por {$user->name}" : '';
        $note = "[SISTEMA]: Substituída pelo aditivo v{$amendment->to_version} ({$label}){$who} em " . now()->format('d/m/Y') . '.';

        $openBills = Bill::where('proposal_id', $proposal->id)
            ->whereIn('category', self::PRINCIPAL_CATEGORIES)
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->get();

        foreach ($openBills as $bill) {
            // Só considera valor recebido quando a parcela está de fato parcialmente paga
            // (há parcelas pendentes com paid_amount residual de outras rotinas).
            $paidPart = $bill->status === 'partially_paid' ? (float) ($bill->paid_amount ?? 0) : 0.0;
            $obs = trim(($bill->observations ? $bill->observations . "\n" : '') . $note);

            if ($paidPart > 0) {
                // Parcialmente paga: fica como paga pelo valor recebido; o saldo vai para o novo contrato
                $bill->update([
                    'status' => 'paid',
                    'amount' => $paidPart,
                    'interest_amount' => 0,
                    'replaced_by_amendment_id' => $amendment->id,
                    'observations' => $obs . ' Valor original R$ ' . number_format((float) $bill->amount, 2, ',', '.') . '; recebido R$ ' . number_format($paidPart, 2, ',', '.') . '.',
                ]);
            } else {
                $bill->update([
                    'status' => 'cancelled',
                    'replaced_by_amendment_id' => $amendment->id,
                    'observations' => $obs,
                ]);
            }
        }
    }

    private function buildSnapshot(Proposal $proposal): array
    {
        $product = $proposal->product;

        return [
            'captured_at' => now()->toIso8601String(),
            'version' => (int) ($proposal->current_version ?? 1),
            'proposal' => $proposal->only([
                'id', 'contract_number', 'product_id', 'total_value', 'gross_value', 'base_value', 'taxes',
                'quantity', 'status', 'audit_status', 'observations', 'credit_balance', 'created_at',
            ]),
            'product' => $product ? $product->only([
                'id', 'name', 'price', 'category', 'package', 'duration', 'quantity', 'contract_fee',
                'maintenance_fee_value', 'maintenance_fee_installments', 'maintenance_fee_frequency',
                'is_maintenance_exempt', 'contract_template_id', 'proposal_template_id',
            ]) : null,
            'payments' => $proposal->payments->map(fn ($p) => $p->only([
                'id', 'category', 'payment_method', 'installments', 'installment_value', 'total_value', 'start_date', 'version',
            ]))->values()->all(),
            'bills' => $proposal->bills->map(fn ($b) => $b->only([
                'id', 'category', 'description', 'amount', 'interest_amount', 'paid_amount', 'due_date', 'paid_at',
                'status', 'payment_method', 'installment_number', 'total_installments', 'version',
            ]))->values()->all(),
            'commissions' => $proposal->commissions->map(fn ($c) => array_merge(
                $c->only(['id', 'user_id', 'role', 'total_amount', 'base_sale_value', 'base_commission_percentage', 'contract_amendment_id']),
                ['installments' => $c->installments->map(fn ($i) => $i->only(['id', 'due_date', 'amount', 'status', 'origin_type']))->values()->all()]
            ))->values()->all(),
            'totals' => [
                'paid_principal' => $this->paidPrincipal($proposal),
                'paid_all' => round((float) $proposal->bills->whereIn('status', ['paid', 'partially_paid'])->sum('paid_amount'), 2),
            ],
        ];
    }
}
