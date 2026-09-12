<?php

namespace App\Services;

use App\Models\Proposal;
use App\Models\CommissionRule;
use App\Models\Commission;
use App\Models\CommissionInstallment;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CommissionService
{
    public function generateCommissions(Proposal $proposal)
    {
        DB::beginTransaction();
        try {
            $regras = CommissionRule::where('is_active', true)->get();
            $generatedCommissions = $this->processCommissionsForRules($proposal, $regras);
            DB::commit();
            return $generatedCommissions;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function generateCommissionsBulk($month, $year, array $roles)
    {
        $proposals = Proposal::whereMonth('created_at', $month)
                             ->whereYear('created_at', $year)
                             ->where('status', 'approved')
                             ->with(['salesService', 'bills'])
                             ->get();

        $count = 0;
        
        DB::beginTransaction();
        try {
            $regras = CommissionRule::where('is_active', true)
                                    ->whereIn('name', $roles)
                                    ->get();

            if ($regras->isEmpty()) {
                throw new Exception("Nenhuma regra ativa encontrada para os cargos selecionados.");
            }

            foreach ($proposals as $proposal) {
                // Remove existing commissions check (already done in controller)
                $this->processCommissionsForRules($proposal, $regras);
                $count++;
            }

            DB::commit();
            return $count;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function processCommissionsForRules(Proposal $proposal, $regras)
    {
        $salesService = $proposal->salesService;
        if (!$salesService) {
            return []; // Ignora se não houver atendimento, ao invés de quebrar o lote
        }

        // Base de cálculo: parcelas de entrada reais (bills).
        // Fallback para ProposalPayments se não houver bills de entrada.
        $billsEntrada = $proposal->bills()->where('category', 'entrada')->get();

        $vendaValor = (float) $proposal->total_value;

        if ($billsEntrada->isNotEmpty()) {
            // Normaliza para a mesma interface: [payment_method, amount]
            $pagamentos = $billsEntrada->map(fn($b) => (object)[
                'payment_method' => $b->payment_method,
                'amount'         => $b->amount,
                'installments'   => $b->total_installments ?? 1,
            ]);
        } else {
            $paymentsEntrada = $proposal->payments()->where('category', 'entrada')->get();
            if ($paymentsEntrada->isEmpty()) {
                return []; // Sem entrada registrada, sem comissão.
            }
            $pagamentos = $paymentsEntrada->map(fn($p) => (object)[
                'payment_method' => $p->payment_method,
                'amount'         => $p->total_value,
                'installments'   => $p->installments ?? 1,
            ]);
        }

        if ($vendaValor <= 0) {
            return [];
        }

        $valorAvista = 0;
        $valorCartao = 0;
        $valorBoleto = 0;
        $parcelasBoletoMax = 0;

        foreach ($pagamentos as $pagamento) {
            $metodo = strtolower($pagamento->payment_method ?? '');
            $totalPagamento = (float) $pagamento->amount;

            if (str_contains($metodo, 'pix') || str_contains($metodo, 'espécie') || str_contains($metodo, 'dinheiro') || str_contains($metodo, 'débito') || str_contains($metodo, 'debito') || str_contains($metodo, 'vista')) {
                $valorAvista += $totalPagamento;
            } elseif (str_contains($metodo, 'crédito') || str_contains($metodo, 'credito') || str_contains($metodo, 'cartão') || str_contains($metodo, 'cartao')) {
                $valorCartao += $totalPagamento;
            } elseif (str_contains($metodo, 'boleto') || str_contains($metodo, 'transferência') || str_contains($metodo, 'transferencia')) {
                $valorBoleto += $totalPagamento;
                $installments = (int) ($pagamento->installments ?? 1);
                if ($installments > $parcelasBoletoMax) {
                    $parcelasBoletoMax = $installments;
                }
            } else {
                // Método não mapeado: tratar como à vista (D+1)
                $valorAvista += $totalPagamento;
            }
        }

        $somaPagamentos = round($valorAvista + $valorCartao + $valorBoleto, 2);
        if ($somaPagamentos <= 0) {
            $valorAvista   = $vendaValor;
            $somaPagamentos = $vendaValor;
        }

        $mesVenda = Carbon::parse($proposal->created_at)->startOfMonth();
        $generatedCommissions = [];

        foreach ($regras as $regra) {
            $coluna = $regra->role_column;
            $userId = $salesService->{$coluna} ?? null;
            $taxa = $regra->percentage / 100;

            if (!$userId || $taxa <= 0) continue;

            $baseCalculo = round($vendaValor * ($regra->distribution_base_percentage / 100), 2);
            $comissaoTotal = round($baseCalculo * $taxa, 2);

            if ($comissaoTotal <= 0) {
                continue;
            }

            $propAvista = $valorAvista / $somaPagamentos;
            $propCartao = $valorCartao / $somaPagamentos;
            $propBoleto = $valorBoleto / $somaPagamentos;

            // 1. À Vista
            if ($propAvista > 0 && $regra->cash_installments > 0) {
                $fatiaTotal = round($comissaoTotal * $propAvista, 2);
                $fatiaBase = round($vendaValor * $propAvista, 2);
                
                $commission = Commission::create([
                    'proposal_id' => $proposal->id,
                    'user_id' => $userId,
                    'role' => $regra->name,
                    'origin_type' => 'avista',
                    'total_amount' => $fatiaTotal,
                    'base_sale_value' => $fatiaBase,
                    'base_commission_percentage' => $regra->percentage
                ]);

                $generatedCommissions[] = $commission;
                $parcelas = $regra->cash_installments;
                $valorParcela = round($fatiaTotal / $parcelas, 2);
                $somaParcelas = 0;

                for ($m = 1; $m <= $parcelas; $m++) {
                    $isUltima = ($m === $parcelas);
                    $valor = $isUltima ? round($fatiaTotal - ($valorParcela * ($parcelas - 1)), 2) : $valorParcela;
                    $somaParcelas += $valor;

                    CommissionInstallment::create([
                        'commission_id' => $commission->id,
                        'month_offset' => $m,
                        'due_date' => $mesVenda->copy()->addMonths($m),
                        'reference_month' => $mesVenda->copy()->addMonths($m)->format('m/Y'),
                        'amount' => $valor,
                        'origin_type' => 'avista'
                    ]);
                }
                
                $diff = round($fatiaTotal - $somaParcelas, 2);
                if (abs($diff) > 0) {
                    $firstInstallment = CommissionInstallment::where('commission_id', $commission->id)
                                        ->orderBy('month_offset', 'asc')->first();
                    if ($firstInstallment) {
                        $firstInstallment->amount += $diff;
                        $firstInstallment->save();
                    }
                }
            }

            // 2. Cartão
            if ($propCartao > 0 && $regra->credit_installments > 0) {
                $fatiaTotal = round($comissaoTotal * $propCartao, 2);
                $fatiaBase = round($vendaValor * $propCartao, 2);
                
                $commission = Commission::create([
                    'proposal_id' => $proposal->id,
                    'user_id' => $userId,
                    'role' => $regra->name,
                    'origin_type' => 'cartao',
                    'total_amount' => $fatiaTotal,
                    'base_sale_value' => $fatiaBase,
                    'base_commission_percentage' => $regra->percentage
                ]);

                $generatedCommissions[] = $commission;
                $parcelas = $regra->credit_installments;
                $valorParcela = round($fatiaTotal / $parcelas, 2);
                $somaParcelas = 0;
                
                for ($m = 1; $m <= $parcelas; $m++) {
                    $isUltima = ($m === $parcelas);
                    $valor = $isUltima ? round($fatiaTotal - ($valorParcela * ($parcelas - 1)), 2) : $valorParcela;
                    $somaParcelas += $valor;

                    CommissionInstallment::create([
                        'commission_id' => $commission->id,
                        'month_offset' => $m,
                        'due_date' => $mesVenda->copy()->addMonths($m), // Cartão inicia no mês seguinte (+1)
                        'reference_month' => $mesVenda->copy()->addMonths($m)->format('m/Y'),
                        'amount' => $valor,
                        'origin_type' => 'cartao'
                    ]);
                }
                
                $diff = round($fatiaTotal - $somaParcelas, 2);
                if (abs($diff) > 0) {
                    $firstInstallment = CommissionInstallment::where('commission_id', $commission->id)
                                        ->orderBy('month_offset', 'asc')->first();
                    if ($firstInstallment) {
                        $firstInstallment->amount += $diff;
                        $firstInstallment->save();
                    }
                }
            }

            // 3. Boleto
            if ($propBoleto > 0) {
                $parcelas = $regra->boleto_installments_type === 'dynamic' 
                            ? $parcelasBoletoMax 
                            : ($regra->boleto_fixed_installments ?? 1);
                
                if ($parcelas > 0) {
                    $fatiaTotal = round($comissaoTotal * $propBoleto, 2);
                    $fatiaBase = round($vendaValor * $propBoleto, 2);
                    
                    $commission = Commission::create([
                        'proposal_id' => $proposal->id,
                        'user_id' => $userId,
                        'role' => $regra->name,
                        'origin_type' => 'boleto',
                        'total_amount' => $fatiaTotal,
                        'base_sale_value' => $fatiaBase,
                        'base_commission_percentage' => $regra->percentage
                    ]);

                    $generatedCommissions[] = $commission;
                    $valorParcela = round($fatiaTotal / $parcelas, 2);
                    $somaParcelas = 0;

                    for ($m = 1; $m <= $parcelas; $m++) {
                        $isUltima = ($m === $parcelas);
                        $valor = $isUltima ? round($fatiaTotal - ($valorParcela * ($parcelas - 1)), 2) : $valorParcela;
                        $somaParcelas += $valor;

                        CommissionInstallment::create([
                            'commission_id' => $commission->id,
                            'month_offset' => $m,
                            'due_date' => $mesVenda->copy()->addMonths($m),
                            'reference_month' => $mesVenda->copy()->addMonths($m)->format('m/Y'),
                            'amount' => $valor,
                            'origin_type' => 'boleto'
                        ]);
                    }
                    
                    $diff = round($fatiaTotal - $somaParcelas, 2);
                    if (abs($diff) > 0) {
                        $firstInstallment = CommissionInstallment::where('commission_id', $commission->id)
                                            ->orderBy('month_offset', 'asc')->first();
                        if ($firstInstallment) {
                            $firstInstallment->amount += $diff;
                            $firstInstallment->save();
                        }
                    }
                }
            }
        }

        return $generatedCommissions;
    }
}
