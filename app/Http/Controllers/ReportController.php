<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\SalesService;
use App\Models\Qualification;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * [DEBUG TEMPORÁRIO] Inspecionar Shows por usuário
     * Acesse: /relatorios/debug-shows?start_date=2026-08-01&end_date=2026-08-31
     */
    public function debugShows(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate   = $request->input('end_date',   Carbon::now()->endOfMonth()->toDateString());

        $users = User::with('roles')->where('status', true)->whereHas('roles', function ($q) {
            $q->whereIn('slug', ['promotor', 'consultor', 'supervisor']);
        })->get();

        $result = [];
        foreach ($users as $user) {
            $roles = $user->roles->pluck('slug')->toArray();
            foreach (['opc_id' => 'promotor', 'liner_id' => 'consultor', 'closer_id' => 'supervisor'] as $col => $role) {
                if (!in_array($role, $roles)) continue;
                $services = SalesService::with('proposal')
                    ->where($col, $user->id)
                    ->whereBetween('date', [$startDate, $endDate])
                    ->get(['id', 'date', 'qualification', $col]);
                $result[] = [
                    'user_id'    => $user->id,
                    'user_name'  => $user->name,
                    'role'       => $role,
                    'column'     => $col,
                    'show_count' => $services->count(),
                    'services'   => $services->map(fn($s) => [
                        'id'              => $s->id,
                        'date'            => $s->date,
                        'qualification'   => $s->qualification,
                        'has_proposal'    => $s->proposal !== null,
                        'proposal_status' => $s->proposal?->status,
                        'proposal_value'  => $s->proposal?->total_value,
                    ])->values(),
                ];
            }
        }

        return response()->json([
            'period' => "$startDate → $endDate",
            'users'  => $result,
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Display the Sales Ranking report.

     */
    public function salesRanking(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        // Fetch active qualifications
        $activeQualifications = Qualification::where('is_active', true)->get(['code', 'name']);

        // Fetch active users with their roles
        $users = User::with('roles')->where('status', true)->whereHas('roles', function ($q) {
            $q->whereIn('slug', ['promotor', 'consultor', 'supervisor']);
        })->get();

        $opcs = [];
        $liners = [];
        $closers = [];

        foreach ($users as $user) {
            if ($user->hasRole('promotor')) {
                $opcs[] = $this->calculateMetrics($user, 'opc_id', $startDate, $endDate, $activeQualifications);
            }
            if ($user->hasRole('consultor')) {
                $liners[] = $this->calculateMetrics($user, 'liner_id', $startDate, $endDate, $activeQualifications);
            }
            if ($user->hasRole('supervisor')) {
                $closers[] = $this->calculateMetrics($user, 'closer_id', $startDate, $endDate, $activeQualifications);
            }
        }

        // Sort by Total Descending
        usort($opcs, fn($a, $b) => $b['total'] <=> $a['total']);
        usort($liners, fn($a, $b) => $b['total'] <=> $a['total']);
        usort($closers, fn($a, $b) => $b['total'] <=> $a['total']);

        $rankingData = [
            'period' => Carbon::parse($startDate)->format('d/m/Y') . ' - ' . Carbon::parse($endDate)->format('d/m/Y'),
            'active_qualifications' => $activeQualifications,
            'opcs' => $opcs,
            'liners' => $liners,
            'closers' => $closers
        ];

        return Inertia::render('Reports/SalesRanking', [
            'rankingData' => $rankingData
        ]);
    }

    /**
     * Export the Sales Ranking report to PDF.
     */
    public function exportPdf(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        // Fetch active qualifications
        $activeQualifications = Qualification::where('is_active', true)->get(['code', 'name']);

        // Fetch active users with their roles
        $users = User::with('roles')->where('status', true)->whereHas('roles', function ($q) {
            $q->whereIn('slug', ['promotor', 'consultor', 'supervisor']);
        })->get();

        $opcs = [];
        $liners = [];
        $closers = [];

        foreach ($users as $user) {
            if ($user->hasRole('promotor')) {
                $opcs[] = $this->calculateMetrics($user, 'opc_id', $startDate, $endDate, $activeQualifications);
            }
            if ($user->hasRole('consultor')) {
                $liners[] = $this->calculateMetrics($user, 'liner_id', $startDate, $endDate, $activeQualifications);
            }
            if ($user->hasRole('supervisor')) {
                $closers[] = $this->calculateMetrics($user, 'closer_id', $startDate, $endDate, $activeQualifications);
            }
        }

        // Sort by Total Descending
        usort($opcs, fn($a, $b) => $b['total'] <=> $a['total']);
        usort($liners, fn($a, $b) => $b['total'] <=> $a['total']);
        usort($closers, fn($a, $b) => $b['total'] <=> $a['total']);

        $rankingData = [
            'period' => Carbon::parse($startDate)->format('d/m/Y') . ' - ' . Carbon::parse($endDate)->format('d/m/Y'),
            'active_qualifications' => $activeQualifications,
            'opcs' => $opcs,
            'liners' => $liners,
            'closers' => $closers
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.sales-ranking', compact('rankingData'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('ranking-vendas-' . date('Y-m-d') . '.pdf');
    }

    private function calculateMetrics(User $user, string $roleColumn, $startDate, $endDate, $activeQualifications)
    {
        // Query Sales Services associated with the user as this specific role in the date range
        $services = SalesService::with('proposal')
            ->where($roleColumn, $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $qualCounts = [];
        // Initialize dynamic qualifications count
        foreach ($activeQualifications as $q) {
            $qualCounts[$q->code] = 0;
        }

        $vendidos = 0;
        $total = 0;

        foreach ($services as $service) {
            $qual = strtoupper(trim($service->qualification ?? ''));
            
            if (array_key_exists($qual, $qualCounts)) {
                $qualCounts[$qual]++;
            }

            // Considera vendido se proposta está aprovada ou concluída (contrato ativo/finalizado)
            if ($service->proposal && in_array(strtolower($service->proposal->status ?? ''), ['approved', 'completed'])) {
                $vendidos++;
                $total += (float) ($service->proposal->total_value ?? 0);
            }
        }

        $show = $services->count();
        $qualCounts['show'] = $show;
        
        // Aproveitamento = Vendidos ÷ Total de Shows (total de atendimentos)
        $aproveitamento = $show > 0 ? ($vendidos / $show) * 100 : 0;

        return [
            'name' => $user->name,
            'qualificacao' => $qualCounts,
            'vendidos' => $vendidos,
            'aproveitamento' => round($aproveitamento, 2),
            'total' => $total
        ];
    }
}
