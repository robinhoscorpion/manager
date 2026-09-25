<?php

namespace App\Http\Controllers\Commissions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CommissionRuleController extends Controller
{
    public function index()
    {
        $rules          = \App\Models\CommissionRule::orderBy('id', 'desc')->get();
        $products       = \App\Models\Product::where('is_active', true)->orderBy('name', 'asc')->get();
        $qualifications = \App\Models\Qualification::where('is_active', true)->orderBy('code', 'asc')->get();

        return Inertia::render('Commissions/Rules/Index', [
            'rules'          => $rules,
            'products'       => $products,
            'qualifications' => $qualifications,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'                         => 'required|string|max:255',
            'role_column'                  => 'required|string|max:255',
            'rule_type'                    => 'required|in:percentage,opc',
            'percentage'                   => 'nullable|numeric|min:0',
            'distribution_base_percentage' => 'nullable|numeric|min:0|max:100',
            'cash_installments'            => 'nullable|integer|min:1',
            'credit_installments'          => 'nullable|integer|min:1',
            'boleto_installments_type'      => 'nullable|in:dynamic,fixed',
            'boleto_fixed_installments'    => 'nullable|integer|min:1',
            'score_rules'                  => 'nullable|array',
            'qualification_rules'          => 'nullable|array',
        ]);

        \App\Models\CommissionRule::create([
            'name'                         => $request->name,
            'role_column'                  => $request->role_column,
            'rule_type'                    => $request->rule_type ?? 'percentage',
            'percentage'                   => $request->percentage ?? 0,
            'distribution_base_percentage' => $request->distribution_base_percentage ?? 15,
            'cash_installments'            => $request->cash_installments ?? 1,
            'credit_installments'          => $request->credit_installments ?? 1,
            'boleto_installments_type'      => $request->boleto_installments_type ?? 'dynamic',
            'boleto_fixed_installments'    => $request->boleto_installments_type === 'fixed' ? $request->boleto_fixed_installments : null,
            'score_rules'                  => $request->score_rules,
            'qualification_rules'          => $request->qualification_rules,
            'is_active'                    => true
        ]);

        return redirect()->back()->with('success', 'Regra de comissão criada com sucesso!');
    }

    public function update(Request $request, \App\Models\CommissionRule $rule)
    {
        $request->validate([
            'name'                         => 'required|string|max:255',
            'role_column'                  => 'required|string|max:255',
            'rule_type'                    => 'required|in:percentage,opc',
            'percentage'                   => 'nullable|numeric|min:0',
            'distribution_base_percentage' => 'nullable|numeric|min:0|max:100',
            'cash_installments'            => 'nullable|integer|min:1',
            'credit_installments'          => 'nullable|integer|min:1',
            'boleto_installments_type'      => 'nullable|in:dynamic,fixed',
            'boleto_fixed_installments'    => 'nullable|integer|min:1',
            'score_rules'                  => 'nullable|array',
            'qualification_rules'          => 'nullable|array',
        ]);

        $rule->update([
            'name'                         => $request->name,
            'role_column'                  => $request->role_column,
            'rule_type'                    => $request->rule_type ?? 'percentage',
            'percentage'                   => $request->percentage ?? 0,
            'distribution_base_percentage' => $request->distribution_base_percentage ?? 15,
            'cash_installments'            => $request->cash_installments ?? 1,
            'credit_installments'          => $request->credit_installments ?? 1,
            'boleto_installments_type'      => $request->boleto_installments_type ?? 'dynamic',
            'boleto_fixed_installments'    => $request->boleto_installments_type === 'fixed' ? $request->boleto_fixed_installments : null,
            'score_rules'                  => $request->score_rules,
            'qualification_rules'          => $request->qualification_rules,
        ]);

        return redirect()->back()->with('success', 'Regra de comissão atualizada com sucesso!');
    }

    public function destroy(\App\Models\CommissionRule $rule)
    {
        $rule->delete();
        return redirect()->back()->with('success', 'Regra removida com sucesso!');
    }
}
