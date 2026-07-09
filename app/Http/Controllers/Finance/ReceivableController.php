<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bill;
use Inertia\Inertia;

class ReceivableController extends Controller
{
    public function index(Request $request)
    {
        $query = Bill::with(['client', 'salesService', 'proposal']);

        // Filtrar por status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Pesquisa por nome do cliente ou descrição
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($qClient) use ($search) {
                      $qClient->where('nome', 'like', "%{$search}%")
                              ->orWhere('cpf', 'like', "%{$search}%");
                  });
            });
        }

        // Filtros Avançados
        if ($request->filled('due_date_start')) {
            $query->whereDate('due_date', '>=', $request->due_date_start);
        }
        if ($request->filled('due_date_end')) {
            $query->whereDate('due_date', '<=', $request->due_date_end);
        }

        if ($request->filled('paid_at_start')) {
            $query->whereDate('paid_at', '>=', $request->paid_at_start);
        }
        if ($request->filled('paid_at_end')) {
            $query->whereDate('paid_at', '<=', $request->paid_at_end);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('min_amount')) {
            $query->where('amount', '>=', $request->min_amount);
        }
        if ($request->filled('max_amount')) {
            $query->where('amount', '<=', $request->max_amount);
        }

        if ($request->filled('sales_service_id')) {
            $query->where('sales_service_id', $request->sales_service_id);
        }

        // Ordenar por data de vencimento
        $query->orderBy('due_date', 'asc');

        $receivables = $query->paginate(20)->withQueryString();

        // Calcular KPIs (Total a receber e Recebido no mês atual)
        $currentMonth = date('m');
        $currentYear = date('Y');

        $kpis = [
            'total_pending' => Bill::where('status', 'pending')->whereMonth('due_date', $currentMonth)->whereYear('due_date', $currentYear)->sum('amount'),
            'total_paid' => Bill::where('status', 'paid')->whereMonth('paid_at', $currentMonth)->whereYear('paid_at', $currentYear)->sum('amount'),
            'total_overdue' => Bill::where('status', 'overdue')->sum('amount'),
        ];

        return Inertia::render('Finance/Receivable/Index', [
            'filters' => $request->only([
                'search', 'status', 'due_date_start', 'due_date_end', 
                'paid_at_start', 'paid_at_end', 'payment_method', 
                'min_amount', 'max_amount', 'sales_service_id'
            ]),
            'receivables' => $receivables,
            'kpis' => $kpis,
        ]);
    }

    public function bulkPayGlobal(Request $request)
    {
        $validated = $request->validate([
            'bill_ids' => 'required|array|min:1',
            'bill_ids.*' => 'exists:bills,id',
            'paid_at' => 'required_if:mode,single|date',
            'payment_method' => 'required|string',
            'mode' => 'required|string|in:single,individual',
            'bill_dates' => 'required_if:mode,individual|array',
            'global_paid_amount' => 'nullable|numeric'
        ]);

        $bills = Bill::whereIn('id', $validated['bill_ids'])->get();
        $globalPaidAmount = $validated['global_paid_amount'] ?? null;

        \Illuminate\Support\Facades\DB::transaction(function () use ($bills, $validated, $globalPaidAmount) {
            $totalOriginal = $bills->sum(function($b) { return $b->amount + ($b->interest_amount ?? 0); });
            $remainingGlobal = $globalPaidAmount;

            foreach ($bills->values() as $index => $bill) {
                if ($bill->status === 'paid') continue;

                $originalBillAmount = $bill->amount + ($bill->interest_amount ?? 0);
                
                if ($globalPaidAmount !== null) {
                    if ($index === $bills->count() - 1) {
                        $finalPaidAmount = $remainingGlobal;
                    } else {
                        $ratio = $totalOriginal > 0 ? ($globalPaidAmount / $totalOriginal) : 0;
                        $finalPaidAmount = round($originalBillAmount * $ratio, 2);
                        $remainingGlobal -= $finalPaidAmount;
                    }
                } else {
                    $finalPaidAmount = $originalBillAmount;
                }

                $paidAt = ($validated['mode'] === 'single') 
                    ? $validated['paid_at'] 
                    : ($validated['bill_dates'][$bill->id] ?? now()->toDateString());
                
                $interestAmount = ($finalPaidAmount > $bill->amount) ? ($finalPaidAmount - $bill->amount) : 0;

                $bill->update([
                    'status' => 'paid',
                    'paid_at' => $paidAt,
                    'paid_amount' => $finalPaidAmount,
                    'interest_amount' => $interestAmount,
                    'payment_method' => $validated['payment_method'],
                ]);
            }
        });

        return redirect()->back()->with('success', count($bills) . ' parcelas foram baixadas com sucesso!');
    }
}
