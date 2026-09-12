<?php

namespace App\Http\Controllers\Commissions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Proposal;
use App\Models\CommissionRule;
use App\Models\CommissionInstallment;
use App\Services\CommissionService;

class CommissionController extends Controller
{
    public function index(Request $request)
    {
        $query = CommissionInstallment::with([
            'commission.proposal.client',
            'commission.proposal.product',
            'commission.user'
        ]);

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));
        
        if ($startDate && $endDate) {
            $start = \Carbon\Carbon::parse($startDate)->startOfDay();
            $end = \Carbon\Carbon::parse($endDate)->endOfDay();
            $query->whereBetween('due_date', [$start, $end]);
        }

        // Metrics Calculation
        $baseQuery = clone $query;
        
        // Sum all commissions total_amount across all filtered commissions
        $totalCommissions = $baseQuery->sum('amount');
        
        // Unique proposals to calculate VGV and count
        $uniqueProposals = $baseQuery->get()->pluck('commission.proposal')->unique('id');
        $totalVGV = $uniqueProposals->sum('total_value');
        $totalContracts = $uniqueProposals->count();

        $commissions = $query->orderBy('due_date', 'asc')->paginate(15);

        $activeRules = CommissionRule::where('is_active', true)->get();

        return Inertia::render('Commissions/Index', [
            'commissions' => $commissions,
            'activeRules' => $activeRules,
            'metrics' => [
                'total_vgv' => $totalVGV,
                'total_contracts' => $totalContracts,
                'total_commissions' => $totalCommissions,
            ],
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]
        ]);
    }

    public function generate(Request $request, CommissionService $commissionService)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
            'roles' => 'required|array|min:1',
            'roles.*' => 'string'
        ]);

        try {
            // Verifica no backend se alguma das roles pedidas já foi gerada
            $month = $request->month;
            $year = $request->year;
            $roles = $request->roles;
            
            $existingRoles = \App\Models\Commission::whereHas('proposal', function($q) use ($month, $year) {
                $q->whereMonth('created_at', $month)
                  ->whereYear('created_at', $year);
            })->whereIn('role', $roles)->pluck('role')->unique();

            if ($existingRoles->isNotEmpty()) {
                throw new \Exception("As comissões para os cargos: " . $existingRoles->implode(', ') . " já foram geradas neste mês. Exclua as comissões do período caso deseje recalcular.");
            }

            $count = $commissionService->generateCommissionsBulk($month, $year, $roles);
            return redirect()->back()->with('success', "Comissões geradas com sucesso para {$count} propostas aprovadas.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erro ao gerar comissões: ' . $e->getMessage());
        }
    }

    public function generatedStatus(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        $month = $request->month;
        $year = $request->year;

        $generatedRoles = \App\Models\Commission::whereHas('proposal', function($q) use ($month, $year) {
            $q->whereMonth('created_at', $month)
              ->whereYear('created_at', $year);
        })->pluck('role')->unique()->values()->toArray();

        return response()->json([
            'generated_roles' => $generatedRoles
        ]);
    }

    public function destroyPeriod(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        try {
            $month = $request->month;
            $year = $request->year;

            $proposals = Proposal::whereMonth('created_at', $month)
                                 ->whereYear('created_at', $year)
                                 ->pluck('id');

            if ($proposals->isEmpty()) {
                return redirect()->back()->with('success', 'Nenhuma comissão encontrada para excluir neste período.');
            }

            $commissions = \App\Models\Commission::whereIn('proposal_id', $proposals)->pluck('id');

            if ($commissions->isNotEmpty()) {
                \App\Models\CommissionInstallment::whereIn('commission_id', $commissions)->delete();
                \App\Models\Commission::whereIn('id', $commissions)->delete();
            }

            return redirect()->back()->with('success', 'Todas as comissões deste período foram excluídas e podem ser geradas novamente.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erro ao excluir comissões do período: ' . $e->getMessage());
        }
    }
}
