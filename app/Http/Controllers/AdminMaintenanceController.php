<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminMaintenanceController extends Controller
{
    /**
     * Display a listing of product maintenance configurations.
     */
    public function index()
    {
        return Inertia::render('Admin/Maintenance/Index', [
            'products' => Product::with('productType')->get(),
            'proposals' => \App\Models\Proposal::with('client')->where('status', 'approved')->get(),
            'adjustments' => \App\Models\MaintenanceAdjustment::orderBy('created_at', 'desc')->get(),
        ]);
    }

    /**
     * Update the maintenance configuration for a product.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'maintenance_fee_value' => 'nullable|numeric|min:0',
            'maintenance_fee_installments' => 'nullable|integer|min:0',
            'maintenance_fee_frequency' => 'required|string|in:annual,semestral',
            'maintenance_fee_start_rule' => 'required|string|in:semester_based,contract_anniversary,fixed_delay',
            'maintenance_fee_day' => 'required|integer|min:1|max:31',
            'maintenance_fee_delay_years' => 'required|integer|min:0|max:3',
            'is_maintenance_exempt' => 'boolean',
        ]);

        $product->update($validated);

        return redirect()->back()->with('success', 'Configuração de manutenção atualizada!');
    }

    /**
     * Apply a global IGPM adjustment to maintenance bills.
     */
    public function applyGlobal(Request $request)
    {
        $validated = $request->validate([
            'target_month' => 'required|integer|min:1|max:12',
            'target_year' => 'required|integer|min:2020',
            'igpm_rate' => 'required|numeric',
            'base_type' => 'required|string|in:current,previous',
            'exempt_proposal_ids' => 'nullable|array',
        ]);

        $exemptIds = $validated['exempt_proposal_ids'] ?? [];

        \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $exemptIds) {
            $bills = \App\Models\Bill::where('category', 'taxa_manutencao')
                ->where('status', 'pending')
                ->whereMonth('due_date', $validated['target_month'])
                ->whereYear('due_date', $validated['target_year'])
                ->whereNotIn('proposal_id', $exemptIds)
                ->get();

            foreach ($bills as $bill) {
                $baseValue = $bill->amount;

                if ($validated['base_type'] === 'previous') {
                    // Tenta encontrar a parcela de exatos 12 meses atrás como base
                    $previousBill = \App\Models\Bill::where('proposal_id', $bill->proposal_id)
                        ->where('category', 'taxa_manutencao')
                        ->whereMonth('due_date', $validated['target_month'])
                        ->whereYear('due_date', $validated['target_year'] - 1)
                        ->first();
                    
                    if ($previousBill) {
                        $baseValue = $previousBill->amount;
                    }
                }

                $increase = $baseValue * ($validated['igpm_rate'] / 100);
                $newAmount = $bill->amount + $increase;

                $bill->update([
                    'amount' => $newAmount,
                    'observations' => trim($bill->observations . "\n[SISTEMA]: Reajuste IGPM de {$validated['igpm_rate']}% aplicado.")
                ]);
            }

            \App\Models\MaintenanceAdjustment::create([
                'target_month' => $validated['target_month'],
                'target_year' => $validated['target_year'],
                'igpm_rate' => $validated['igpm_rate'],
                'base_type' => $validated['base_type'],
                'exempt_proposal_ids' => $exemptIds,
                'applied_at' => now(),
            ]);
        });

        return redirect()->back()->with('success', 'Reajuste global aplicado com sucesso!');
    }
}
