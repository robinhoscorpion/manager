<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Services\Finance\LateCharges;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Inadimplência: parcelas vencidas agrupadas por cliente, faixas de atraso (aging) e encargos.
 */
class DelinquencyController extends Controller
{
    public function index(Request $request)
    {
        LateCharges::refreshStatuses();
        $settings = LateCharges::settings();

        $bills = Bill::with(['client:id,nome,cpf,celular1,email', 'proposal:id,contract_number,sales_service_id'])
            ->where('status', 'overdue')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->search;
                $q->whereHas('client', fn ($c) => $c->where('nome', 'like', "%{$search}%")->orWhere('cpf', 'like', "%{$search}%"));
            })
            ->orderBy('due_date')
            ->get()
            ->map(function ($bill) use ($settings) {
                $charges = LateCharges::for($bill, null, $settings);

                return [
                    'id' => $bill->id,
                    'client_id' => $bill->client_id,
                    'sales_service_id' => $bill->sales_service_id ?? $bill->proposal?->sales_service_id,
                    'contract_number' => $bill->proposal?->contract_number,
                    'description' => $bill->description,
                    'category' => $bill->category,
                    'installment' => $bill->installment_number && $bill->total_installments ? "{$bill->installment_number}/{$bill->total_installments}" : null,
                    'due_date' => $bill->due_date,
                    'amount' => (float) $bill->amount,
                    'days_late' => $charges['days_late'],
                    'bucket' => LateCharges::agingBucket($charges['days_late']),
                    'fine' => $charges['fine'],
                    'interest' => $charges['interest'],
                    'updated_amount' => $charges['updated_amount'],
                    'client' => $bill->client,
                ];
            });

        // Faixas de atraso (aging)
        $buckets = collect(['0-30', '31-60', '61-90', '90+'])->mapWithKeys(fn ($b) => [$b => [
            'bucket' => $b,
            'count' => $bills->where('bucket', $b)->count(),
            'amount' => round($bills->where('bucket', $b)->sum('amount'), 2),
            'updated_amount' => round($bills->where('bucket', $b)->sum('updated_amount'), 2),
        ]])->values();

        // Por cliente
        $clients = $bills->groupBy('client_id')->map(function ($items) {
            $first = $items->first();

            return [
                'client_id' => $first['client_id'],
                'name' => $first['client']->nome ?? 'Cliente removido',
                'cpf' => $first['client']->cpf ?? null,
                'phone' => $first['client']->celular1 ?? null,
                'email' => $first['client']->email ?? null,
                'sales_service_id' => $items->pluck('sales_service_id')->filter()->first(),
                'contracts' => $items->pluck('contract_number')->filter()->unique()->values(),
                'count' => $items->count(),
                'oldest_due' => $items->min('due_date'),
                'max_days_late' => $items->max('days_late'),
                'bucket' => LateCharges::agingBucket($items->max('days_late')),
                'amount' => round($items->sum('amount'), 2),
                'updated_amount' => round($items->sum('updated_amount'), 2),
                'bills' => $items->map(fn ($b) => collect($b)->except('client'))->values(),
            ];
        })->sortByDesc('updated_amount')->values();

        // Carteira em aberto (pendente + vencido) para a taxa de inadimplência
        $openPortfolio = (float) Bill::whereIn('status', ['pending', 'overdue'])->sum('amount');
        $overdueTotal = round($bills->sum('amount'), 2);

        return Inertia::render('Finance/Delinquency/Index', [
            'summary' => [
                'clients' => $clients->count(),
                'bills' => $bills->count(),
                'amount' => $overdueTotal,
                'updated_amount' => round($bills->sum('updated_amount'), 2),
                'rate' => $openPortfolio > 0 ? round($overdueTotal / $openPortfolio * 100, 1) : 0,
                'avg_days_late' => $bills->count() ? (int) round($bills->avg('days_late')) : 0,
            ],
            'buckets' => $buckets,
            'clients' => $clients,
            'settings' => $settings,
            'filters' => $request->only('search'),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'auto_apply' => 'required|boolean',
            'fine_percent' => 'required|numeric|min:0|max:100',
            'interest_monthly_percent' => 'required|numeric|min:0|max:100',
            'grace_days' => 'required|integer|min:0|max:365',
        ], [], [
            'fine_percent' => 'multa',
            'interest_monthly_percent' => 'juros ao mês',
            'grace_days' => 'dias de carência',
        ]);

        LateCharges::saveSettings($validated);

        return back()->with('success', 'Regras de multa e juros atualizadas!');
    }
}
