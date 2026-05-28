<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContractTemplate;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContractTemplateController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/ContractTemplates/Index', [
            'templates' => ContractTemplate::with('products')->orderBy('name')->get(),
            'products' => Product::with('productType')->orderBy('product_type_id')->orderBy('name')->get(['id', 'name', 'product_type_id'])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'nullable|string',
            'is_default' => 'boolean',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        if ($validated['is_default']) {
            ContractTemplate::where('is_default', true)->update(['is_default' => false]);
            // If it's global, we might want to clear specific links? 
            // Or keep them as "overrides". Let's clear for now to be safe.
            Product::whereNotNull('contract_template_id')->update(['contract_template_id' => null]);
        }

        $template = ContractTemplate::create($validated);

        if (!$validated['is_default'] && !empty($validated['product_ids'])) {
            Product::whereIn('id', $validated['product_ids'])->update(['contract_template_id' => $template->id]);
        }

        return redirect()->back()->with('success', 'Modelo de contrato criado com sucesso.');
    }

    public function update(Request $request, ContractTemplate $contractTemplate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'nullable|string',
            'is_default' => 'boolean',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        if ($validated['is_default']) {
            ContractTemplate::where('id', '!=', $contractTemplate->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
            
            // Clear specific links if this becomes global
            Product::whereNotNull('contract_template_id')->update(['contract_template_id' => null]);
        }

        $contractTemplate->update($validated);

        // Sync products
        if (!$validated['is_default']) {
            // Remove from products not in list
            Product::where('contract_template_id', $contractTemplate->id)
                ->whereNotIn('id', $validated['product_ids'] ?? [])
                ->update(['contract_template_id' => null]);
            
            // Add to products in list
            if (!empty($validated['product_ids'])) {
                Product::whereIn('id', $validated['product_ids'])
                    ->update(['contract_template_id' => $contractTemplate->id]);
            }
        }

        return redirect()->back()->with('success', 'Modelo de contrato atualizado com sucesso.');
    }

    public function destroy(ContractTemplate $contractTemplate)
    {
        $contractTemplate->delete();
        return redirect()->back()->with('success', 'Modelo de contrato excluído com sucesso.');
    }

    public function uploadImage(Request $request)
    {
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('public/contracts/images');
            return response()->json(['url' => \Storage::url($path)]);
        }
        return response()->json(['error' => 'No image uploaded'], 400);
    }
}
