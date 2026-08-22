<?php

namespace App\Http\Controllers\Commissions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CommissionRuleController extends Controller
{
    public function index()
    {
        $rules = \App\Models\CommissionRule::orderBy('id', 'desc')->get();

        return Inertia::render('Commissions/Rules/Index', [
            'rules' => $rules
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'role_column' => 'required|string|max:255',
            'percentage' => 'required|numeric|min:0',
            'distribution_base_percentage' => 'required|numeric|min:0|max:100',
            'cash_installments' => 'required|integer|min:1',
            'credit_installments' => 'required|integer|min:1',
            'boleto_installments_type' => 'required|in:dynamic,fixed',
            'boleto_fixed_installments' => 'nullable|integer|min:1',
        ]);

        \App\Models\CommissionRule::create([
            'name' => $request->name,
            'role_column' => $request->role_column,
            'percentage' => $request->percentage,
            'distribution_base_percentage' => $request->distribution_base_percentage,
            'cash_installments' => $request->cash_installments,
            'credit_installments' => $request->credit_installments,
            'boleto_installments_type' => $request->boleto_installments_type,
            'boleto_fixed_installments' => $request->boleto_installments_type === 'fixed' ? $request->boleto_fixed_installments : null,
            'is_active' => true
        ]);

        return redirect()->back()->with('success', 'Regra de comissão criada com sucesso!');
    }

    public function update(Request $request, \App\Models\CommissionRule $rule)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'role_column' => 'required|string|max:255',
            'percentage' => 'required|numeric|min:0',
            'distribution_base_percentage' => 'required|numeric|min:0|max:100',
            'cash_installments' => 'required|integer|min:1',
            'credit_installments' => 'required|integer|min:1',
            'boleto_installments_type' => 'required|in:dynamic,fixed',
            'boleto_fixed_installments' => 'nullable|integer|min:1',
        ]);

        $rule->update([
            'name' => $request->name,
            'role_column' => $request->role_column,
            'percentage' => $request->percentage,
            'distribution_base_percentage' => $request->distribution_base_percentage,
            'cash_installments' => $request->cash_installments,
            'credit_installments' => $request->credit_installments,
            'boleto_installments_type' => $request->boleto_installments_type,
            'boleto_fixed_installments' => $request->boleto_installments_type === 'fixed' ? $request->boleto_fixed_installments : null,
        ]);

        return redirect()->back()->with('success', 'Regra de comissão atualizada com sucesso!');
    }

    public function destroy(\App\Models\CommissionRule $rule)
    {
        $rule->delete();
        return redirect()->back()->with('success', 'Regra removida com sucesso!');
    }
}
