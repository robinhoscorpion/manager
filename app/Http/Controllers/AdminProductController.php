<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductType;
use App\Models\ProposalTemplate;
use App\Models\ContractTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminProductController extends Controller
{
    /**
     * Display a listing of products and types.
     */
    public function index()
    {
        return Inertia::render('Admin/Product/Index', [
            'products' => Product::with(['productType', 'proposalTemplate', 'contractTemplate'])->get(),
            'productTypes' => ProductType::all(),
            'proposalTemplates' => ProposalTemplate::where('is_active', true)->orderBy('name')->get(),
            'contractTemplates' => ContractTemplate::orderBy('name')->get()
        ]);
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_type_id' => 'required|exists:product_types,id',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'min_price' => 'nullable|numeric|min:0',
            'duration' => 'nullable|string',
            'quantity' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'min_down_payment_percentage' => 'nullable|numeric|min:0|max:100',
            'contract_fee' => 'nullable|numeric|min:0',
            
            // Maintenance Config (Optional on product creation, defaults applied below)
            'maintenance_fee_value' => 'nullable|numeric|min:0',
            'maintenance_fee_installments' => 'nullable|integer|min:0',
            'maintenance_fee_start_rule' => 'nullable|string|in:semester_based,contract_anniversary,fixed_delay',
            'maintenance_fee_day' => 'nullable|integer|min:1|max:31',
            'maintenance_fee_delay_years' => 'nullable|integer|min:0|max:3',
            'is_maintenance_exempt' => 'nullable|boolean',

            'description' => 'nullable|string',
            'contract_prefix' => 'nullable|string',
            'contract_format' => 'required|in:prefix_sep_seq,prefix_seq,seq_only',
            'current_sequence' => 'required|integer|min:1',
            'proposal_template_id' => 'nullable|exists:proposal_templates,id',
            'contract_template_id' => 'nullable|exists:contract_templates,id',
        ]);

        // Define defaults for maintenance fields if not provided
        $data = array_merge([
            'maintenance_fee_value' => 0,
            'maintenance_fee_installments' => 1,
            'maintenance_fee_start_rule' => 'semester_based',
            'maintenance_fee_day' => 10,
            'maintenance_fee_delay_years' => 0,
            'is_maintenance_exempt' => false,
        ], $validated);

        Product::create($data);

        return redirect()->back()->with('success', 'Produto cadastrado com sucesso!');
    }

    /**
     * Update the specified product.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'product_type_id' => 'required|exists:product_types,id',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'min_price' => 'nullable|numeric|min:0',
            'duration' => 'nullable|string',
            'quantity' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'min_down_payment_percentage' => 'nullable|numeric|min:0|max:100',
            'contract_fee' => 'nullable|numeric|min:0',

            // Maintenance Config (Optional in Product area, managed in Maintenance area)
            'maintenance_fee_value' => 'nullable|numeric|min:0',
            'maintenance_fee_installments' => 'nullable|integer|min:0',
            'maintenance_fee_start_rule' => 'nullable|string|in:semester_based,contract_anniversary,fixed_delay',
            'maintenance_fee_day' => 'nullable|integer|min:1|max:31',
            'maintenance_fee_delay_years' => 'nullable|integer|min:0|max:3',
            'is_maintenance_exempt' => 'nullable|boolean',

            'description' => 'nullable|string',
            'contract_prefix' => 'nullable|string',
            'contract_format' => 'required|in:prefix_sep_seq,prefix_seq,seq_only',
            'current_sequence' => 'required|integer|min:1',
            'proposal_template_id' => 'nullable|exists:proposal_templates,id',
            'contract_template_id' => 'nullable|exists:contract_templates,id',
        ]);

        $product->update($validated);

        return redirect()->back()->with('success', 'Produto atualizado com sucesso!');
    }

    /**
     * Remove the specified product.
     */
    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->back()->with('success', 'Produto removido com sucesso!');
    }
}
