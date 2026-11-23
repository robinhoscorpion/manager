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
            $contractNumber = '';

            do {
                $sequence = $product->current_sequence;
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

                // Verifica se já existe
                $exists = \App\Models\Proposal::where('contract_number', $contractNumber)->exists();
                if ($exists) {
                    $product->incrementSequence();
                    // Não salva ainda, salvará no final ou deixaremos em memória até o $product->save()
                }
            } while ($exists);

            $validated['contract_number'] = $contractNumber;
            // O payment_method original foi removido da proposta e passado para os pagamentos individuais

            // Calcular Valores da Proposta (Ignorando Taxa de Manutenção para o Valor Bruto)
            $validated['base_value'] = $validated['total_value']; // Total Base (Entrada + Saldo)
            $validated['taxes'] = collect($validated['payments'])->where('category', 'taxa_contrato')->sum('total_value');
            $validated['gross_value'] = $validated['base_value'] + $validated['taxes'];

            // Criar Proposta
            $proposal = Proposal::create($validated);

            // Criar Pagamentos Detalhados
            foreach ($validated['payments'] as $paymentData) {
                $proposal->payments()->create($paymentData);
            }

            // Salvar a nova sequência (mesmo que tenha precisado pular números para evitar colisão)
            $product->incrementSequence();
            $product->save();

            // Auto-qualificar atendimento como Q e alterar status para Proposta ao gerar proposta
            $proposal->salesService->update([
                'qualification' => 'Q',
                'status' => \App\Models\SalesService::STATUS_PROPOSTA
            ]);

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

        // Contratos que já passaram por upgrade/downgrade possuem histórico de versões:
        // a edição direta apagaria esse histórico, então deve ser feita via novo aditivo.
        if (($proposal->current_version ?? 1) > 1) {
            return redirect()->back()->withErrors(['product_id' => 'Este contrato já possui aditivo (upgrade/downgrade). Para alterá-lo, registre um novo aditivo em Pós-venda > Upgrade/Downgrade.']);
        }

        // O bloqueio de edição quando existem parcelas pagas foi removido a pedido.
        // As parcelas serão deletadas e recriadas abaixo.

        DB::transaction(function () use ($validated, $proposal) {
            // Calcular Valores da Proposta (Ignorando Taxa de Manutenção para o Valor Bruto)
            $baseValue = $validated['total_value'];
            $taxes = collect($validated['payments'])->where('category', 'taxa_contrato')->sum('total_value');
            $grossValue = $baseValue + $taxes;

            // Atualiza dados básicos
            $proposal->update([
                'product_id' => $validated['product_id'],
                'total_value' => $validated['total_value'],
                'base_value' => $baseValue,
                'taxes' => $taxes,
                'gross_value' => $grossValue,
                'quantity' => $validated['quantity'],
                'observations' => $validated['observations'],
            ]);

            // Sincroniza Pagamentos: Remove antigos e cria novos
            $proposal->payments()->delete();
            foreach ($validated['payments'] as $paymentData) {
                $proposal->payments()->create($paymentData);
            }

            if ($proposal->status !== 'approved') {
                // Garante que o atendimento esteja qualificado como Q e com status de Proposta
                $proposal->salesService->update([
                    'qualification' => 'Q',
                    'status' => \App\Models\SalesService::STATUS_PROPOSTA
                ]);
            } else {
                // Se já estava aprovado, exclui os recebíveis pendentes e gera novos
                \App\Models\Bill::where('proposal_id', $proposal->id)->delete();
                $proposal->load('payments', 'product');
                $this->generateBills($proposal);
            }
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
            $this->generateBills($proposal);

            // 4. Automação de Envio de Boas-Vindas e Acesso
            try {
                $setting = \App\Models\Setting::where('key', 'welcome_access_settings')->first();
                $settings = $setting && is_array($setting->value) ? $setting->value : [];
                if (($settings['dispatch_mode'] ?? 'manual') === 'automatic') {
                    $salesService = $proposal->salesService;
                    $client = $salesService ? $salesService->client : null;
                    if ($client && !empty($client->email) && ($settings['auto_send_email'] ?? true)) {
                        \Illuminate\Support\Facades\Mail::to($client->email)->send(
                            new \App\Mail\SocioWelcomeMail($client, config('app.url'), $settings['email_template'] ?? '')
                        );
                        $salesService->update([
                            'welcome_status' => 'sent_email',
                            'welcome_sent_at' => now(),
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                // Falha no e-mail não trava a aprovação da venda
            }
        });

        return redirect()->back()->with('success', 'Proposta #' . $proposal->contract_number . ' aprovada e financeiro gerado!');
    }

    /**
     * Generate financial bills for a proposal.
     */
    private function generateBills(Proposal $proposal)
    {
        app(\App\Services\Contracts\ProposalBillGenerator::class)->generate($proposal);
    }

    /**
     * Get list of products for the selection.
     */
    public function getProducts()
    {
        return response()->json(Product::where('is_active', true)->with('productType')->get());
    }
}
