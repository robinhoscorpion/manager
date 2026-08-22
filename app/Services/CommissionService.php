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
        $salesService = $proposal->salesService;
        if (!$salesService) {
            throw new Exception("Proposta não possui atendimento vinculado.");
        }

        $vendaValor = $proposal->total_value;
        $pagamentos = $proposal->payments;

        $valorAvista = 0;
        $valorCartao = 0;
        $valorBoleto = 0;
        $parcelasBoletoMax = 0;

        foreach ($pagamentos as $pagamento) {
            $metodo = strtolower($pagamento->payment_method);
            $totalPagamento = (float) $pagamento->total_value;

            if (in_array($metodo, ['pix', 'espécie', 'dinheiro', 'débito', 'debito', 'crédito à vista', 'credito a vista'])) {
                $valorAvista += $totalPagamento;
            } elseif (in_array($metodo, ['crédito', 'credito', 'cartão de crédito', 'cartao de credito', 'crédito parcelado'])) {
                $valorCartao += $totalPagamento;
            } elseif (in_array($metodo, ['boleto', 'boleto bancário', 'boleto bancario', 'transferência'])) {
                $valorBoleto += $totalPagamento;
                if ($pagamento->installments > $parcelasBoletoMax) {
                    $parcelasBoletoMax = (int) $pagamento->installments;
                }
            }
        }

        $somaPagamentos = round($valorAvista + $valorCartao + $valorBoleto, 2);
        $mesVenda = Carbon::parse($proposal->created_at)->startOfMonth();
        $generatedCommissions = [];

        DB::beginTransaction();

        try {
            $regras = CommissionRule::where('is_active', true)->get();

            foreach ($regras as $regra) {
                $coluna = $regra->role_column;
                $userId = $salesService->{$coluna} ?? null;
                $taxa = $regra->percentage / 100;

                if (!$userId || $taxa <= 0) continue;

                $comissaoTotal = round($vendaValor * $taxa, 2);
                $baseCalculada = round($vendaValor * ($regra->distribution_base_percentage / 100), 2);

                if (abs($somaPagamentos - $baseCalculada) > 0.05) {
                    throw new Exception("A soma dos pagamentos de entrada ({$somaPagamentos}) não confere com a base exigida pela regra '{$regra->name}' ({$baseCalculada}, correspondente a {$regra->distribution_base_percentage}% da venda).");
                }

                if ($baseCalculada <= 0) {
                    throw new Exception("Valor de entrada base para a regra '{$regra->name}' é inválido (Zero ou negativo).");
                }

                $propAvista = $valorAvista / $baseCalculada;
                $propCartao = $valorCartao / $baseCalculada;
                $propBoleto = $valorBoleto / $baseCalculada;

                $commission = Commission::create([
                    'proposal_id' => $proposal->id,
                    'user_id' => $userId,
                    'role' => $regra->name,
                    'total_amount' => $comissaoTotal,
                    'base_sale_value' => $vendaValor,
                    'base_commission_percentage' => $regra->percentage
                ]);

                $generatedCommissions[] = $commission;
                $somaParcelas = 0;

                // 1. À Vista
                if ($propAvista > 0 && $regra->cash_installments > 0) {
                    $fatia = $comissaoTotal * $propAvista;
                    $parcelas = $regra->cash_installments;
                    $valorParcela = round($fatia / $parcelas, 2);

                    for ($m = 1; $m <= $parcelas; $m++) {
                        $isUltima = ($m === $parcelas);
                        $valor = $isUltima ? round($fatia - ($valorParcela * ($parcelas - 1)), 2) : $valorParcela;
                        $somaParcelas += $valor;

                        CommissionInstallment::create([
                            'commission_id' => $commission->id,
                            'month_offset' => $m,
                            'due_date' => $mesVenda->copy()->addMonths($m),
                            'amount' => $valor,
                            'origin_type' => 'avista'
                        ]);
                    }
                }

                // 2. Cartão
                if ($propCartao > 0 && $regra->credit_installments > 0) {
                    $fatia = $comissaoTotal * $propCartao;
                    $parcelas = $regra->credit_installments;
                    $valorParcela = round($fatia / $parcelas, 2);
                    
                    for ($m = 1; $m <= $parcelas; $m++) {
                        $isUltima = ($m === $parcelas);
                        $valor = $isUltima ? round($fatia - ($valorParcela * ($parcelas - 1)), 2) : $valorParcela;
                        $somaParcelas += $valor;

                        CommissionInstallment::create([
                            'commission_id' => $commission->id,
                            'month_offset' => $m,
                            'due_date' => $mesVenda->copy()->addMonths($m),
                            'amount' => $valor,
                            'origin_type' => 'cartao'
                        ]);
                    }
                }

                // 3. Boleto
                if ($propBoleto > 0) {
                    $parcelas = $regra->boleto_installments_type === 'dynamic' 
                                ? $parcelasBoletoMax 
                                : ($regra->boleto_fixed_installments ?? 1);
                    
                    if ($parcelas > 0) {
                        $fatia = $comissaoTotal * $propBoleto;
                        $valorParcela = round($fatia / $parcelas, 2);

                        for ($m = 1; $m <= $parcelas; $m++) {
                            $isUltima = ($m === $parcelas);
                            $valor = $isUltima ? round($fatia - ($valorParcela * ($parcelas - 1)), 2) : $valorParcela;
                            $somaParcelas += $valor;

                            CommissionInstallment::create([
                                'commission_id' => $commission->id,
                                'month_offset' => $m,
                                'due_date' => $mesVenda->copy()->addMonths($m),
                                'amount' => $valor,
                                'origin_type' => 'boleto'
                            ]);
                        }
                    }
                }

                $diff = round($comissaoTotal - $somaParcelas, 2);
                if (abs($diff) > 0) {
                    $firstInstallment = CommissionInstallment::where('commission_id', $commission->id)
                                        ->orderBy('month_offset', 'asc')->first();
                    if ($firstInstallment) {
                        $firstInstallment->amount += $diff;
                        $firstInstallment->save();
                    }
                }
            }

            DB::commit();
            return $generatedCommissions;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
