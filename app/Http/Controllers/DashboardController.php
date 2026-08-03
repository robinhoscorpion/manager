<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\PlatformGoal;
use App\Models\Proposal;
use App\Models\SalesService;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $now = Carbon::now();
        
        $month = $request->input('month', $now->month);
        $year = $request->input('year', $now->year);
        $date = Carbon::createFromDate($year, $month, 1);
        
        $currentGoal = PlatformGoal::where('month', $month)
                                   ->where('year', $year)
                                   ->first();

        // Buscar total de vendas e quantidade de contratos no mês vigente
        $monthlyProposals = Proposal::whereMonth('created_at', $month)
                                    ->whereYear('created_at', $year)
                                    ->where('status', 'approved')
                                    ->get();

        $totalSalesRevenue = $monthlyProposals->sum('total_value');
        $totalContracts = $monthlyProposals->count();

        // Dados para o gráfico de Vendas por Dia
        $daysInMonth = $date->daysInMonth;
        $salesPerDay = array_fill(1, $daysInMonth, 0);
        $servicesPerDay = array_fill(1, $daysInMonth, 0);

        foreach ($monthlyProposals as $proposal) {
            $day = (int) $proposal->created_at->format('j');
            $salesPerDay[$day]++;
        }

        $monthlyServices = SalesService::whereMonth('created_at', $month)
                                       ->whereYear('created_at', $year)
                                       ->get();

        foreach ($monthlyServices as $service) {
            $day = (int) $service->created_at->format('j');
            $servicesPerDay[$day]++;
        }

        $totalServicesCount = $monthlyServices->count();

        // Rankings
        $rankingPromotores = [];
        $rankingConsultores = [];
        $rankingSupervisores = [];

        foreach ($monthlyProposals as $proposal) {
            $service = $proposal->salesService;
            if ($service) {
                $val = (float) $proposal->total_value;

                // Promotores (OPC)
                if ($service->opcUser) {
                    $id = $service->opc_id;
                    if (!isset($rankingPromotores[$id])) {
                        $rankingPromotores[$id] = [
                            'name' => $service->opcUser->name,
                            'avatar' => $service->opcUser->profile_photo_url,
                            'total' => 0
                        ];
                    }
                    $rankingPromotores[$id]['total'] += $val;
                }

                // Consultores (Liner)
                if ($service->linerUser) {
                    $id = $service->liner_id;
                    if (!isset($rankingConsultores[$id])) {
                        $rankingConsultores[$id] = [
                            'name' => $service->linerUser->name,
                            'avatar' => $service->linerUser->profile_photo_url,
                            'total' => 0
                        ];
                    }
                    $rankingConsultores[$id]['total'] += $val;
                }
                
                // Supervisores (Closer)
                if ($service->closerUser) {
                    $id = $service->closer_id;
                    if (!isset($rankingSupervisores[$id])) {
                        $rankingSupervisores[$id] = [
                            'name' => $service->closerUser->name,
                            'avatar' => $service->closerUser->profile_photo_url,
                            'total' => 0
                        ];
                    }
                    $rankingSupervisores[$id]['total'] += $val;
                }
            }
        }

        usort($rankingPromotores, fn($a, $b) => $b['total'] <=> $a['total']);
        usort($rankingConsultores, fn($a, $b) => $b['total'] <=> $a['total']);
        usort($rankingSupervisores, fn($a, $b) => $b['total'] <=> $a['total']);

        $formatRanking = function($list) {
            return array_map(function($item) {
                $item['value'] = 'R$ ' . number_format($item['total'], 2, ',', '.');
                return $item;
            }, array_slice($list, 0, 5));
        };

        return Inertia::render('Dashboard', [
            'current_month' => $month,
            'current_year' => $year,
            'current_goal' => $currentGoal,
            'total_sales_revenue' => $totalSalesRevenue,
            'total_contracts' => $totalContracts,
            'total_services' => $totalServicesCount,
            'chart_sales_data' => array_values($salesPerDay),
            'chart_services_data' => array_values($servicesPerDay),
            'ranking_promotores' => $formatRanking($rankingPromotores),
            'ranking_consultores' => $formatRanking($rankingConsultores),
            'ranking_supervisores' => $formatRanking($rankingSupervisores),
        ]);
    }
}
