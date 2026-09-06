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
        
        // Se NÃO houver nenhum filtro explícito de data e NEM filtro de status, assumimos o mês atual
        if (!$request->has('due_date_start') && !$request->has('due_date_end') && !$request->has('status') && !$request->has('search')) {
            $dueDateStart = date('Y-m-01');
            $dueDateEnd = date('Y-m-t');
        } else {
            $dueDateStart = $request->input('due_date_start');
            $dueDateEnd = $request->input('due_date_end');
        }

        if ($dueDateStart) {
            $query->whereDate('due_date', '>=', $dueDateStart);
        }
        if ($dueDateEnd) {
            $query->whereDate('due_date', '<=', $dueDateEnd);
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

        if ($request->filled('recipient')) {
            $query->where('recipient', $request->recipient);
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
        $receivables = (clone $query)->orderBy('due_date', 'asc')->paginate(20)->withQueryString();

        // Base query para os KPIs (sem o filtro de data padrão e sem o status padrão, que podem estar em $query)
        $baseKpiQuery = Bill::query();
        
        // Reaplica os mesmos filtros de texto, recebedor e método para os KPIs
        if ($request->filled('search')) {
            $search = $request->search;
            $baseKpiQuery->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($qClient) use ($search) {
                      $qClient->where('nome', 'like', "%{$search}%")->orWhere('cpf', 'like', "%{$search}%");
                  });
            });
        }
        if ($request->filled('payment_method')) $baseKpiQuery->where('payment_method', $request->payment_method);
        if ($request->filled('recipient')) $baseKpiQuery->where('recipient', $request->recipient);
        if ($request->filled('sales_service_id')) $baseKpiQuery->where('sales_service_id', $request->sales_service_id);

        $currentMonth = date('m');
        $currentYear = date('Y');

        $kpis = [
            'total_pending' => (clone $baseKpiQuery)->where('status', 'pending')
                ->whereMonth('due_date', $currentMonth)
                ->whereYear('due_date', $currentYear)
                ->sum('amount'),
                
            'total_paid' => (clone $baseKpiQuery)->where('status', 'paid')
                ->whereMonth('paid_at', $currentMonth)
                ->whereYear('paid_at', $currentYear)
                ->sum('amount'),
                
            'total_overdue' => (clone $baseKpiQuery)->where('status', 'overdue')
                ->sum('amount'),
                
            'received_today' => (clone $baseKpiQuery)->where('status', 'paid')
                ->whereDate('paid_at', date('Y-m-d'))
                ->get()
                ->sum(function($bill) { return $bill->amount + ($bill->interest_amount ?? 0); }),
                
            'due_today' => (clone $baseKpiQuery)->where('status', 'pending')
                ->whereDate('due_date', date('Y-m-d'))
                ->sum('amount'),
                
            'total_interest' => (clone $baseKpiQuery)->where('status', 'paid')
                ->sum('interest_amount'),
        ];

        return Inertia::render('Finance/Receivable/Index', [
            'filters' => array_merge($request->only([
                'search', 'status', 
                'paid_at_start', 'paid_at_end', 'payment_method', 
                'min_amount', 'max_amount', 'sales_service_id', 'recipient'
            ]), [
                'due_date_start' => $dueDateStart,
                'due_date_end' => $dueDateEnd,
            ]),
            'receivables' => $receivables,
            'kpis' => $kpis,
            'bankAccounts' => \App\Models\BankAccount::where('is_active', true)->orderBy('name')->get(),
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
            'global_paid_amount' => 'nullable|numeric',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
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
                    'bank_account_id' => $validated['bank_account_id'] ?? null,
                ]);
            }
        });

        return redirect()->back()->with('success', count($bills) . ' parcelas foram baixadas com sucesso!');
    }
}
