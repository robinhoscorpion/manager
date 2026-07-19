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
    public function index()
    {
        $now = Carbon::now();
        
        $currentGoal = PlatformGoal::where('month', $now->month)
                                   ->where('year', $now->year)
                                   ->first();

        // Buscar total de vendas e quantidade de contratos no mês vigente
        $monthlyProposals = Proposal::whereMonth('created_at', $now->month)
                                    ->whereYear('created_at', $now->year)
                                    ->where('status', 'approved')
                                    ->get();

        $totalSalesRevenue = $monthlyProposals->sum('total_value');
        $totalContracts = $monthlyProposals->count();

        // Dados para o gráfico de Vendas por Dia
        $daysInMonth = $now->daysInMonth;
        $salesPerDay = array_fill(1, $daysInMonth, 0);
        $servicesPerDay = array_fill(1, $daysInMonth, 0);

        foreach ($monthlyProposals as $proposal) {
            $day = (int) $proposal->created_at->format('j');
            $salesPerDay[$day]++;
        }

        $monthlyServices = SalesService::whereMonth('created_at', $now->month)
                                       ->whereYear('created_at', $now->year)
                                       ->get();

        foreach ($monthlyServices as $service) {
            $day = (int) $service->created_at->format('j');
            $servicesPerDay[$day]++;
        }

        return Inertia::render('Dashboard', [
            'current_goal' => $currentGoal,
            'total_sales_revenue' => $totalSalesRevenue,
            'total_contracts' => $totalContracts,
            'chart_sales_data' => array_values($salesPerDay),
            'chart_services_data' => array_values($servicesPerDay),
        ]);
    }
}
