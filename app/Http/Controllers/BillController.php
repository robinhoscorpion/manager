<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use Illuminate\Http\Request;

class BillController extends Controller
{
    /**
     * Store a newly created bill in storage.
     */
    public function store(Request $request, \App\Models\Proposal $proposal)
    {
        $validated = $request->validate([
            'category' => 'required|string|in:taxa_contrato,entrada,saldo,taxa_manutencao',
            'due_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'status' => 'required|string|in:pending,paid,overdue,cancelled',
            'paid_at' => 'nullable|date',
            'paid_amount' => 'nullable|numeric|min:0',
            'interest_amount' => 'nullable|numeric|min:0',
            'observations' => 'nullable|string',
        ]);

        $latestBill = $proposal->bills()
            ->where('category', $validated['category'])
            ->orderBy('installment_number', 'desc')
            ->first();
            
        $nextNumber = ($latestBill?->installment_number ?? 0) + 1;

        $proposal->bills()->create([
            'client_id' => $proposal->client_id,
            'sales_service_id' => $proposal->sales_service_id,
            'category' => $validated['category'],
            'description' => 'Parcela Adicional de ' . ucfirst(str_replace('_', ' ', $validated['category'])),
            'installment_number' => $nextNumber,
            'total_installments' => $nextNumber,
            'due_date' => $validated['due_date'],
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'status' => $validated['status'],
            'paid_at' => $validated['paid_at'] ?? null,
            'paid_amount' => $validated['paid_amount'] ?? null,
            'interest_amount' => $validated['interest_amount'] ?? null,
            'observations' => $validated['observations'] ?? null,
        ]);

        // Sync total_installments for all bills in this category
        $proposal->bills()->where('category', $validated['category'])->update(['total_installments' => $nextNumber]);

        return redirect()->back()->with('success', 'Nova parcela adicionada com sucesso!');
    }

    /**
     * Update the specified bill in storage.
     */
    public function update(Request $request, Bill $bill)
    {
        $validated = $request->validate([
            'due_date' => 'required|date',
            'paid_at' => 'nullable|date',
            'amount' => 'required|numeric|min:0',
            'interest_amount' => 'nullable|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'required|string',
            'status' => 'required|string|in:pending,paid,overdue,cancelled',
            'observations' => 'nullable|string',
        ]);

        $bill->update($validated);

        return redirect()->back()->with('success', 'Parcela atualizada com sucesso!');
    }

    /**
     * Remove the specified bill from storage.
     */
    public function destroy(Bill $bill)
    {
        $bill->delete();

        return redirect()->back()->with('success', 'Parcela excluída com sucesso!');
    }

    /**
     * Renegotiate and merge multiple bills into one.
     */
    public function renegotiate(Request $request, \App\Models\Proposal $proposal)
    {
        $validated = $request->validate([
            'bill_ids' => 'required|array|min:2',
            'bill_ids.*' => 'exists:bills,id',
            'due_date' => 'required|date',
            'payment_method' => 'required|string',
        ]);

        $bills = $proposal->bills()->whereIn('id', $validated['bill_ids'])->get();

        if ($bills->count() !== count($validated['bill_ids'])) {
            return redirect()->back()->with('error', 'Algumas parcelas não pertencem a esta proposta.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($proposal, $bills, $validated) {
            $totalAmount = 0;
            $oldNumbers = [];

            foreach ($bills as $bill) {
                // Calculate open amount
                $openAmount = $bill->amount + $bill->interest_amount - $bill->paid_amount;
                $totalAmount += $openAmount;
                $oldNumbers[] = $bill->installment_number;

                // Mark old bill as cancelled
                $bill->update([
                    'status' => 'cancelled',
                    'observations' => trim($bill->observations . "\n[SISTEMA]: Cancelada e agrupada por renegociação.")
                ]);
            }

            // Default to 'saldo' if category vanishes, else use the first bill's category
            $category = $bills->first()->category ?? 'saldo';

            $latestBill = $proposal->bills()
                ->where('category', $category)
                ->orderBy('installment_number', 'desc')
                ->first();
                
            $nextNumber = ($latestBill?->installment_number ?? 0) + 1;

            $proposal->bills()->create([
                'client_id' => $proposal->client_id,
                'sales_service_id' => $proposal->sales_service_id,
                'category' => $category,
                'description' => 'Renegociação de ' . ucfirst(str_replace('_', ' ', $category)),
                'installment_number' => $nextNumber,
                'total_installments' => $nextNumber,
                'due_date' => $validated['due_date'],
                'amount' => $totalAmount,
                'payment_method' => $validated['payment_method'],
                'status' => 'pending',
                'observations' => '[SISTEMA]: Parcela criada a partir da renegociação e agrupamento das parcelas nº ' . implode(', ', $oldNumbers) . '.',
            ]);

            // Sync total_installments for all bills in this category
            $proposal->bills()->where('category', $category)->update(['total_installments' => $nextNumber]);
        });

        return redirect()->back()->with('success', 'Parcelas renegociadas com sucesso!');
    }

    /**
     * Bulk pay multiple bills.
     */
    public function bulkPay(Request $request, \App\Models\Proposal $proposal)
    {
        $validated = $request->validate([
            'bill_ids' => 'required|array|min:1',
            'bill_ids.*' => 'exists:bills,id',
            'paid_at' => 'required_if:mode,single|date',
            'payment_method' => 'required|string',
            'observations' => 'nullable|string',
            'mode' => 'required|string|in:single,individual',
            'bill_dates' => 'required_if:mode,individual|array',
        ]);

        $bills = $proposal->bills()->whereIn('id', $validated['bill_ids'])->get();

        if ($bills->count() !== count($validated['bill_ids'])) {
            return redirect()->back()->with('error', 'Algumas parcelas não pertencem a esta proposta.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($bills, $validated) {
            foreach ($bills as $bill) {
                if ($bill->status === 'paid') continue;

                $paidAt = ($validated['mode'] === 'individual' && isset($validated['bill_dates'][$bill->id])) 
                    ? $validated['bill_dates'][$bill->id] 
                    : $validated['paid_at'];

                $bill->update([
                    'status' => 'paid',
                    'paid_at' => $paidAt,
                    'payment_method' => $validated['payment_method'],
                    'paid_amount' => $bill->amount + ($bill->interest_amount ?? 0),
                    'observations' => trim($bill->observations . "\n[LOTE]: " . ($validated['observations'] ?? 'Baixa em lote realizada.'))
                ]);
            }
        });

        return redirect()->back()->with('success', 'Baixa em lote realizada com sucesso!');
    }
}
