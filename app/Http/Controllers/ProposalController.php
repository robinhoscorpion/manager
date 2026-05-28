<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProposalController extends Controller
{
    /**
     * Store a newly created proposal in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'sales_service_id' => 'required|exists:sales_services,id',
            'product_id' => 'required|exists:products,id',
            'total_value' => 'required|numeric|min:0',
            'quantity' => 'nullable|integer|min:0',
            'observations' => 'nullable|string',
            'payments' => 'required|array|min:1',
            'payments.*.category' => 'required|string',
            'payments.*.payment_method' => 'required|string',
            'payments.*.installments' => 'required|integer|min:1',
            'payments.*.installment_value' => 'required|numeric|min:0',
            'payments.*.total_value' => 'required|numeric|min:0',
            'payments.*.start_date' => 'required|date',
        ]);

        // Validar se o cliente tem CPF/Documento cadastrado
        $client = \App\Models\Client::find($validated['client_id']);
        if (!$client || empty($client->cpf)) {
            return redirect()->back()->withErrors(['client_id' => 'O cliente deve ter um CPF ou Documento cadastrado para gerar uma proposta.']);
        }

        $proposal = DB::transaction(function () use ($validated) {
            $product = Product::lockForUpdate()->find($validated['product_id']);
            
            // Gerar Número do Contrato
            $sequence = str_pad($product->current_sequence, 5, '0', STR_PAD_LEFT);
            $contractNumber = '';

            switch ($product->contract_format) {
                case 'prefix_sep_seq':
                    $contractNumber = ($product->contract_prefix ? $product->contract_prefix . '-' : '') . $sequence;
                    break;
                case 'prefix_seq':
                    $contractNumber = ($product->contract_prefix ? $product->contract_prefix : '') . $sequence;
                    break;
                case 'seq_only':
                default:
                    $contractNumber = $product->current_sequence;
                    break;
            }

            $validated['contract_number'] = $contractNumber;
            // O payment_method original foi removido da proposta e passado para os pagamentos individuais

            // Criar Proposta
            $proposal = Proposal::create($validated);

            // Criar Pagamentos Detalhados
            foreach ($validated['payments'] as $paymentData) {
                $proposal->payments()->create($paymentData);
            }

            // Incrementar Sequência
            $product->increment('current_sequence');

            // Auto-qualificar atendimento como Q ao gerar proposta
            $proposal->salesService->update(['qualification' => 'Q']);

            return $proposal;
        });

        return redirect()->back()->with('success', 'Proposta #' . $proposal->contract_number . ' preenchida com sucesso!');
    }

    /**
     * Update the specified proposal in storage.
     */
    public function update(Request $request, Proposal $proposal)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'total_value' => 'required|numeric|min:0',
            'quantity' => 'nullable|integer|min:0',
            'observations' => 'nullable|string',
            'payments' => 'required|array|min:1',
            'payments.*.category' => 'required|string',
            'payments.*.payment_method' => 'required|string',
            'payments.*.installments' => 'required|integer|min:1',
            'payments.*.installment_value' => 'required|numeric|min:0',
            'payments.*.total_value' => 'required|numeric|min:0',
            'payments.*.start_date' => 'required|date',
        ]);

        // Validar se o cliente tem CPF/Documento cadastrado
        if (empty($proposal->client?->cpf)) {
            return redirect()->back()->withErrors(['product_id' => 'O cliente deve ter um CPF ou Documento cadastrado para salvar a proposta.']);
        }

        DB::transaction(function () use ($validated, $proposal) {
            // Atualiza dados básicos
            $proposal->update([
                'product_id' => $validated['product_id'],
                'total_value' => $validated['total_value'],
                'quantity' => $validated['quantity'],
                'observations' => $validated['observations'],
            ]);

            // Sincroniza Pagamentos: Remove antigos e cria novos
            $proposal->payments()->delete();
            foreach ($validated['payments'] as $paymentData) {
                $proposal->payments()->create($paymentData);
            }

            // Garante que o atendimento esteja qualificado como Q
            $proposal->salesService->update(['qualification' => 'Q']);
        });

        return redirect()->back()->with('success', 'Proposta #' . $proposal->contract_number . ' atualizada com sucesso!');
    }

    /**
     * Approve the specified proposal and generate financial bills.
     */
    public function approve(Request $request, Proposal $proposal)
    {
        DB::transaction(function () use ($proposal) {
            // 1. Atualizar Proposta
            $proposal->update(['status' => 'approved']);

            // 2. Atualizar Atendimento
            $proposal->salesService->update(['status' => \App\Models\SalesService::STATUS_APROVADO]);

            // 3. Gerar Parcelas Financeiras (Bills)
            $product = $proposal->product;

            foreach ($proposal->payments as $payment) {
                // Se for taxa de manutenção e o produto for isento, pula
                if ($payment->category === 'taxa_manutencao' && $product->is_maintenance_exempt) {
                    continue;
                }

                for ($i = 0; $i < $payment->installments; $i++) {
                    $dueDate = \Carbon\Carbon::parse($payment->start_date)->addMonths($i);

                    // Lógica especial para Taxa de Manutenção baseada nas regras do Produto
                    if ($payment->category === 'taxa_manutencao') {
                        $baseDate = today()->addYears($product->maintenance_fee_delay_years)->startOfMonth();
                        
                        switch ($product->maintenance_fee_start_rule) {
                            case 'semester_based':
                                // Jan-Jul -> Dez do ano atual (ou ano com carência)
                                // Ago-Dez -> Jul do ano seguinte (ou ano com carência + 1)
                                if ($baseDate->month <= 7) {
                                    $dueDate = $baseDate->copy()->month(12)->day($product->maintenance_fee_day);
                                } else {
                                    $dueDate = $baseDate->copy()->addYear()->month(7)->day($product->maintenance_fee_day);
                                }
                                // Se houver mais de uma parcela, aplica a frequência (Anual vs Semestral)
                                if ($i > 0) {
                                    $monthsToAdd = ($product->maintenance_fee_frequency === 'annual') ? 12 : 6;
                                    $dueDate->addMonths($i * $monthsToAdd);
                                }
                                break;

                            case 'contract_anniversary':
                                // No mês do contrato + carência + i anos/semestres
                                if ($product->maintenance_fee_frequency === 'semestral') {
                                    $dueDate = $baseDate->copy()->day($product->maintenance_fee_day)->addMonths($i * 6);
                                } else {
                                    $dueDate = $baseDate->copy()->day($product->maintenance_fee_day)->addYears($i);
                                }
                                break;

                            case 'fixed_delay':
                            default:
                                // Apenas carência + dia fixo + i meses/anos
                                if ($product->maintenance_fee_frequency === 'semestral') {
                                    $dueDate = $baseDate->copy()->day($product->maintenance_fee_day)->addMonths($i * 6);
                                } else {
                                    $dueDate = $baseDate->copy()->day($product->maintenance_fee_day)->addMonths($i * 12);
                                }
                                break;
                        }
                    }
                    
                    $categoryLabel = $this->getCategoryLabel($payment->category);
                    $installmentStr = ($i + 1) . '/' . $payment->installments;

                    \App\Models\Bill::create([
                        'client_id' => $proposal->client_id,
                        'proposal_id' => $proposal->id,
                        'sales_service_id' => $proposal->sales_service_id,
                        'category' => $payment->category,
                        'description' => "{$categoryLabel} - {$installmentStr} - Contrato #{$proposal->contract_number}",
                        'amount' => $payment->installment_value,
                        'due_date' => $dueDate,
                        'payment_method' => $payment->payment_method,
                        'installment_number' => $i + 1,
                        'total_installments' => $payment->installments,
                        'status' => 'pending'
                    ]);
                }
            }
        });

        return redirect()->back()->with('success', 'Proposta #' . $proposal->contract_number . ' aprovada e financeiro gerado!');
    }

    private function getCategoryLabel($category)
    {
        $labels = [
            'taxa_contrato' => 'Taxa de Contrato',
            'entrada' => 'Entrada',
            'saldo' => 'Saldo',
            'taxa_manutencao' => 'Taxa de Manutenção',
        ];
        return $labels[$category] ?? 'Pagamento';
    }

    /**
     * Get list of products for the selection.
     */
    public function getProducts()
    {
        return response()->json(Product::where('is_active', true)->with('productType')->get());
    }
}
