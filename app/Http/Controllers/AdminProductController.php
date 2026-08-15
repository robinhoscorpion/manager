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
            'products' => Product::with(['productType', 'proposalTemplate', 'contractTemplate'])->withCount('proposals')->get(),
            'productTypes' => ProductType::orderByRaw("CASE WHEN name = 'Pontos' THEN 1 ELSE 2 END")->orderBy('name')->get(),
            'proposalTemplates' => ProposalTemplate::where('is_active', true)->orderBy('name')->get(),
            'contractTemplates' => ContractTemplate::orderBy('name')->get()
        ]);
    }

    private function getValidationRules()
    {
        return [
            'product_type_id' => 'required|exists:product_types,id',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'min_price' => 'nullable|numeric|min:0',
            'duration' => 'nullable|string',
            'quantity' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'min_down_payment_percentage' => 'nullable|numeric|min:0|max:100',
            'contract_fee' => 'nullable|numeric|min:0',
            
            // Maintenance Config
            'maintenance_fee_value' => 'nullable|numeric|min:0',
            'maintenance_fee_installments' => 'nullable|integer|min:0',
            'maintenance_fee_start_rule' => 'nullable|string|in:semester_based,contract_anniversary,fixed_delay',
            'maintenance_fee_day' => 'nullable|integer|min:1|max:31',
            'maintenance_fee_delay_years' => 'nullable|integer|min:0|max:3',
            'is_maintenance_exempt' => 'nullable|boolean',

            'description' => 'nullable|string',
            'contract_prefix' => 'nullable|string',
            'contract_format' => 'required|in:prefix_sep_seq,prefix_seq,seq_only',
            'current_sequence' => 'required|string|max:255',
            'proposal_template_id' => 'nullable|exists:proposal_templates,id',
            'contract_template_id' => 'nullable|exists:contract_templates,id',
        ];
    }

    private function getValidationMessages()
    {
        return [
            'product_type_id.required' => 'A categoria de produto é obrigatória.',
            'product_type_id.exists' => 'A categoria de produto selecionada não é válida.',
            
            'name.required' => 'O nome comercial é obrigatório.',
            'name.string' => 'O nome comercial deve ser um texto válido.',
            'name.max' => 'O nome comercial não pode exceder 255 caracteres.',
            
            'price.required' => 'O valor de venda (base) é obrigatório.',
            'price.numeric' => 'O valor de venda deve ser numérico.',
            'price.min' => 'O valor de venda não pode ser negativo.',
            
            'min_price.numeric' => 'O valor teto deve ser numérico.',
            'min_price.min' => 'O valor teto não pode ser negativo.',
            
            'duration.string' => 'A duração deve ser um texto válido.',
            
            'quantity.integer' => 'A quantidade deve ser um número inteiro.',
            'quantity.min' => 'A quantidade não pode ser negativa.',
            
            'min_down_payment_percentage.numeric' => 'O percentual de entrada deve ser numérico.',
            'min_down_payment_percentage.min' => 'O percentual de entrada não pode ser menor que 0%.',
            'min_down_payment_percentage.max' => 'O percentual de entrada não pode ser maior que 100%.',
            
            'contract_fee.numeric' => 'A taxa de contrato deve ser numérica.',
            'contract_fee.min' => 'A taxa de contrato não pode ser negativa.',
            
            'contract_format.required' => 'O formato da numeração do contrato é obrigatório.',
            'contract_format.in' => 'O formato da numeração selecionada é inválida.',
            
            'current_sequence.required' => 'A sequência inicial é obrigatória.',
            'current_sequence.string' => 'A sequência inicial deve ser um texto.',
            'current_sequence.max' => 'A sequência inicial não pode exceder 255 caracteres.',
            
            'contract_prefix.string' => 'O prefixo do contrato deve ser um texto.',
            
            'proposal_template_id.exists' => 'O layout de proposta selecionado não foi encontrado.',
            'contract_template_id.exists' => 'O modelo de contrato selecionado não foi encontrado.',
        ];
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request)
    {
        $validated = $request->validate($this->getValidationRules(), $this->getValidationMessages());

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
        $validated = $request->validate($this->getValidationRules(), $this->getValidationMessages());

        $product->update($validated);

        return redirect()->back()->with('success', 'Produto atualizado com sucesso!');
    }

    /**
     * Remove the specified product.
     */
    public function destroy(Product $product)
    {
        if ($product->proposals()->count() > 0) {
            return redirect()->back()->with('error', "Não é possível excluir este produto pois já existem vendas (propostas) vinculadas a ele. Você pode inativá-lo para que não seja mais vendido.");
        }

        $product->delete();
        return redirect()->back()->with('success', 'Produto removido com sucesso!');
    }
}
