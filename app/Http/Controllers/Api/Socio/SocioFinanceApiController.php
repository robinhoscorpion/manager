<?php

namespace App\Http\Controllers\Api\Socio;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Client;
use App\Models\Proposal;
use Illuminate\Http\Request;

class SocioFinanceApiController extends Controller
{
    /**
     * Retorna o financeiro completo do sócio autenticado.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $client = $user instanceof Client ? $user : ($user ? $user->client : null);

        if (!$client) {
            return response()->json([
                'summary' => [
                    'total_bills' => 0,
                    'paid_count' => 0,
                    'pending_count' => 0,
                    'overdue_count' => 0,
                    'total_amount' => 0,
                    'total_paid' => 0,
                    'amount_open' => 0,
                    'contract_number' => null,
                    'contract_total_value' => 0,
                ],
                'grouped_bills' => [],
                'bills' => []
            ]);
        }

        // Busca a proposta ativa do cliente
        $proposal = Proposal::where('client_id', $client->id)
            ->orderByRaw("CASE WHEN status = 'approved' THEN 1 WHEN status = 'pending' THEN 2 ELSE 3 END")
            ->latest('id')
            ->first();
        $proposalId = $proposal?->id;

        // Busca todas as parcelas e boletos do cliente ou proposta
        $bills = Bill::where(function ($q) use ($client, $proposalId) {
            $q->where('client_id', $client->id);
            if ($proposalId) {
                $q->orWhere('proposal_id', $proposalId);
            }
        })
        ->orderBy('due_date', 'asc')
        ->get();

        $isPaid = fn($b) => in_array(strtolower($b->status ?? ''), ['paid', 'pago', 'quitado']);
        $isPending = fn($b) => in_array(strtolower($b->status ?? ''), ['pending', 'pendente', 'aberto']);
        $isOverdue = fn($b) => $isPending($b) && !empty($b->due_date) && $b->due_date < date('Y-m-d');

        $grouped = [];
        foreach ($bills as $bill) {
            $cat = $bill->category ?: 'outros';
            $label = match ($cat) {
                'taxa_contrato', 'taxa_adesao' => 'Taxa de Emissão e Adesão Contratual',
                'entrada' => 'Entrada do Contrato',
                'saldo' => 'Parcelas Mensais do Saldo',
                'manutencao', 'condominio' => 'Taxas de Manutenção Condominial',
                default => ucfirst(str_replace('_', ' ', $cat))
            };

            if (!isset($grouped[$cat])) {
                $grouped[$cat] = [
                    'category' => $cat,
                    'label' => $label,
                    'total_amount' => 0,
                    'total_paid' => 0,
                    'paid_count' => 0,
                    'pending_count' => 0,
                    'overdue_count' => 0,
                    'items' => []
                ];
            }

            $amt = (float) $bill->amount;
            $paid = $isPaid($bill) ? (float) ($bill->paid_amount ?: $bill->amount) : 0;
            $status = $isPaid($bill) ? 'pago' : ($isOverdue($bill) ? 'atrasado' : 'pendente');

            $grouped[$cat]['total_amount'] += $amt;
            $grouped[$cat]['total_paid'] += $paid;
            if ($status === 'pago') {
                $grouped[$cat]['paid_count']++;
            } elseif ($status === 'atrasado') {
                $grouped[$cat]['overdue_count']++;
            } else {
                $grouped[$cat]['pending_count']++;
            }

            $grouped[$cat]['items'][] = [
                'id' => $bill->id,
                'description' => $bill->description,
                'category' => $cat,
                'installment' => $bill->installment_number ? "{$bill->installment_number}/{$bill->total_installments}" : "1/1",
                'installment_number' => $bill->installment_number,
                'total_installments' => $bill->total_installments,
                'due_date' => $bill->due_date ? date('d/m/Y', strtotime($bill->due_date)) : '-',
                'due_date_raw' => $bill->due_date,
                'paid_at' => $bill->paid_at ? date('d/m/Y', strtotime($bill->paid_at)) : null,
                'amount' => $amt,
                'amount_formatted' => 'R$ ' . number_format($amt, 2, ',', '.'),
                'paid_amount' => $paid,
                'paid_amount_formatted' => 'R$ ' . number_format($paid, 2, ',', '.'),
                'status' => $status,
                'recipient' => $bill->recipient,
                'payment_method' => $bill->payment_method ?: 'PIX',
                'barcode' => $bill->barcode ?? null,
                'digitable_line' => $bill->digitable_line ?? null,
                'observations' => $bill->observations,
            ];
        }

        $totalAmount = (float) $bills->sum('amount');
        $totalPaid = (float) $bills->filter($isPaid)->sum(fn($b) => $b->paid_amount ?: $b->amount);
        $amountOpen = max(0, $totalAmount - $totalPaid);

        $summary = [
            'total_bills' => $bills->count(),
            'paid_count' => $bills->filter($isPaid)->count(),
            'pending_count' => $bills->filter($isPending)->count(),
            'overdue_count' => $bills->filter($isOverdue)->count(),
            'total_amount' => $totalAmount,
            'total_paid' => $totalPaid,
            'amount_open' => $amountOpen,
            'contract_number' => $proposal?->contract_number,
            'contract_total_value' => (float) ($proposal?->total_value ?? $totalAmount),
            'is_adimplente' => $bills->filter($isOverdue)->count() === 0,
        ];

        return response()->json([
            'summary' => $summary,
            'grouped_bills' => array_values($grouped),
            'bills' => $bills,
        ]);
    }

    /**
     * Retorna detalhes de uma parcela específica.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $client = $user instanceof Client ? $user : ($user ? $user->client : null);

        $bill = Bill::where('id', $id)
            ->where(function ($q) use ($client) {
                if ($client) {
                    $q->where('client_id', $client->id);
                }
            })
            ->firstOrFail();

        return response()->json(['bill' => $bill]);
    }
}