<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\PlatformGoal;
use App\Models\Proposal;
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

        return Inertia::render('Dashboard', [
            'current_goal' => $currentGoal,
            'total_sales_revenue' => $totalSalesRevenue,
            'total_contracts' => $totalContracts,
        ]);
    }
}
