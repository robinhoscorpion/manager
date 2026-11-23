<?php

namespace App\Services\Contracts;

use App\Models\Bill;
use App\Models\PaymentMethod;
use App\Models\Proposal;
use App\Models\ProposalPayment;
use Carbon\Carbon;

/**
 * Gera as parcelas financeiras (bills) a partir dos pagamentos negociados.
 *
 * Extraído do ProposalController para ser reutilizado pela aprovação da
 * proposta e pelos aditivos de contrato (upgrade / downgrade).
 */
class ProposalBillGenerator
{
    /** Categorias apenas informativas: não geram parcela a receber. */
    public const NON_BILLABLE_CATEGORIES = [
        'credito_contrato_anterior',
    ];

    public const CATEGORY_LABELS = [
        'taxa_contrato' => 'Taxa de Contrato',
        'entrada' => 'Entrada',
        'saldo' => 'Saldo',
        'taxa_manutencao' => 'Taxa de Manutenção',
        'credito_contrato_anterior' => 'Valor Pago no Contrato Anterior',
    ];

    public static function categoryLabel(?string $category): string
    {
        return self::CATEGORY_LABELS[$category] ?? 'Pagamento';
    }

    /**
     * @param iterable<ProposalPayment>|null $payments  Pagamentos a faturar (padrão: negociação vigente)
     * @param array $extra  Campos extras para cada bill (ex.: version, contract_amendment_id)
     * @param string|null $descriptionSuffix  Sufixo da descrição (ex.: " (Aditivo v2)")
     */
    public function generate(Proposal $proposal, ?iterable $payments = null, array $extra = [], ?string $descriptionSuffix = null): void
    {
        $proposal->loadMissing('product');
        $product = $proposal->product;
        $payments = $payments ?? $proposal->payments()->get();

        $paymentMethodsMap = PaymentMethod::pluck('auto_baixa', 'name')->toArray();
        $bankAccountMap = PaymentMethod::pluck('bank_account_id', 'name')->toArray();
        $recipientMap = PaymentMethod::pluck('recipient', 'name')->toArray();

        foreach ($payments as $payment) {
            if (in_array($payment->category, self::NON_BILLABLE_CATEGORIES, true)) {
                continue;
            }

            // Se for taxa de manutenção e o produto for isento, pula
            if ($payment->category === 'taxa_manutencao' && $product->is_maintenance_exempt) {
                continue;
            }

            for ($i = 0; $i < $payment->installments; $i++) {
                $dueDate = Carbon::parse($payment->start_date)->addMonths($i);

                // Lógica especial para Taxa de Manutenção baseada nas regras do Produto
                if ($payment->category === 'taxa_manutencao') {
                    $dueDate = $this->maintenanceDueDate($product, $i);
                }

                $categoryLabel = self::categoryLabel($payment->category);
                $installmentStr = ($i + 1) . '/' . $payment->installments;

                $isAutoBaixa = !empty($paymentMethodsMap[$payment->payment_method]);

                Bill::create(array_merge([
                    'client_id' => $proposal->client_id,
                    'proposal_id' => $proposal->id,
                    'sales_service_id' => $proposal->sales_service_id,
                    'category' => $payment->category,
                    'description' => "{$categoryLabel} - {$installmentStr} - Contrato #{$proposal->contract_number}" . ($descriptionSuffix ?? ''),
                    'amount' => $payment->installment_value,
                    'due_date' => $dueDate,
                    'payment_method' => $payment->payment_method,
                    'bank_account_id' => $bankAccountMap[$payment->payment_method] ?? null,
                    'recipient' => $recipientMap[$payment->payment_method] ?? null,
                    'installment_number' => $i + 1,
                    'total_installments' => $payment->installments,
                    'status' => $isAutoBaixa ? 'paid' : 'pending',
                    'paid_amount' => $isAutoBaixa ? $payment->installment_value : null,
                    'paid_at' => $isAutoBaixa ? $dueDate : null,
                ], $extra));
            }
        }
    }

    private function maintenanceDueDate($product, int $i): Carbon
    {
        $baseDate = today()->addYears($product->maintenance_fee_delay_years)->startOfMonth();

        switch ($product->maintenance_fee_start_rule) {
            case 'semester_based':
                // Jan-Jul -> Dez do ano atual (ou ano com carência)
                // Ago-Dez -> Jul do ano seguinte (ou ano com carência + 1)
                if ($baseDate->month <= 7) {
                    $dueDate = $baseDate->copy()->month(12)->day($product->maintenance_fee_day);
                } else {
                    $dueDate = $baseDate->copy()->addYear()->month(7)->day($product->maintenance_fee_day);
                }
                // Se houver mais de uma parcela, aplica a frequência (Anual vs Semestral)
                if ($i > 0) {
                    $monthsToAdd = ($product->maintenance_fee_frequency === 'annual') ? 12 : 6;
                    $dueDate->addMonths($i * $monthsToAdd);
                }
                return $dueDate;

            case 'contract_anniversary':
                // No mês do contrato + carência + i anos/semestres
                if ($product->maintenance_fee_frequency === 'semestral') {
                    return $baseDate->copy()->day($product->maintenance_fee_day)->addMonths($i * 6);
                }
                return $baseDate->copy()->day($product->maintenance_fee_day)->addYears($i);

            case 'fixed_delay':
            default:
                // Apenas carência + dia fixo + i meses/anos
                if ($product->maintenance_fee_frequency === 'semestral') {
                    return $baseDate->copy()->day($product->maintenance_fee_day)->addMonths($i * 6);
                }
                return $baseDate->copy()->day($product->maintenance_fee_day)->addMonths($i * 12);
        }
    }
}
