<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CommissionService;
use App\Models\Proposal;
use App\Models\ProposalPayment;
use App\Models\SalesService;

class TestCommission extends Command
{
    protected $signature = 'test:commission';
    protected $description = 'Testa o motor de comissão com os dados do exemplo (R$ 65.600)';

    public function handle(CommissionService $service)
    {
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Limpar dados de teste anteriores
        $testSalesServiceId = 999999;
        \App\Models\Commission::whereHas('proposal', function($q) use ($testSalesServiceId) {
            $q->where('sales_service_id', $testSalesServiceId);
        })->delete();
        ProposalPayment::whereHas('proposal', function($q) use ($testSalesServiceId) {
            $q->where('sales_service_id', $testSalesServiceId);
        })->delete();
        Proposal::where('sales_service_id', $testSalesServiceId)->delete();
        SalesService::where('id', $testSalesServiceId)->delete();
        
        \App\Models\CommissionRule::truncate();
        
        \App\Models\CommissionRule::create([
            'name' => 'Liner Mock',
            'role_column' => 'liner_id',
            'percentage' => 1.5,
            'distribution_base_percentage' => 15.00,
            'cash_installments' => 1,
            'credit_installments' => 3,
            'boleto_installments_type' => 'dynamic',
            'is_active' => true
        ]);
        
        \App\Models\CommissionRule::create([
            'name' => 'Closer Mock',
            'role_column' => 'closer_id',
            'percentage' => 2.0,
            'distribution_base_percentage' => 15.00,
            'cash_installments' => 1,
            'credit_installments' => 3,
            'boleto_installments_type' => 'dynamic',
            'is_active' => true
        ]);

        $this->info("Criando dados de teste...");

        // Cria atendimento mock
        $salesService = SalesService::forceCreate([
            'id' => $testSalesServiceId,
            'client_id' => 1,
            'liner_id' => 1, // Assumindo usuário 1
            'closer_id' => 1, // Assumindo usuário 1
            'date' => now()->toDateString(),
            'time' => '12:00:00',
            'status' => 'completed'
        ]);

        // Cria Proposta mock
        $proposal = Proposal::forceCreate([
            'sales_service_id' => $salesService->id,
            'client_id' => 1,
            'product_id' => 1,
            'total_value' => 65600,
            'status' => 'approved'
        ]);

        // Pagamento à vista: R$ 5.904
        ProposalPayment::forceCreate([
            'proposal_id' => $proposal->id,
            'category' => 'down_payment',
            'payment_method' => 'pix',
            'installments' => 1,
            'installment_value' => 5904,
            'total_value' => 5904
        ]);

        // Pagamento em boleto: R$ 3.936 em 4x
        ProposalPayment::forceCreate([
            'proposal_id' => $proposal->id,
            'category' => 'down_payment',
            'payment_method' => 'boleto',
            'installments' => 4,
            'installment_value' => 984,
            'total_value' => 3936
        ]);

        $this->info("Executando CommissionService...");

        try {
            $comissoes = $service->generateCommissions($proposal);
            $this->info("Comissões geradas com sucesso: " . count($comissoes));

            foreach ($comissoes as $c) {
                $this->line("\n=======================");
                $this->line("Papel: {$c->role} (ID: {$c->user_id})");
                $this->line("Total da Comissão: R$ {$c->total_amount}");
                $this->line("Taxa: {$c->base_commission_percentage}%");
                $this->line("Parcelas:");
                
                foreach ($c->installments()->orderBy('month_offset')->get() as $inst) {
                    $this->line("  Mês +{$inst->month_offset} ({$inst->origin_type}): R$ {$inst->amount}");
                }
            }
        } catch (\Exception $e) {
            $this->error("Erro ao gerar comissões: " . $e->getMessage());
        }

    }
}
