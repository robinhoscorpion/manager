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
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        $users = User::with('roles')->where('status', true)->whereHas('roles', function ($q) {
            $q->whereIn('slug', ['promotor', 'consultor', 'supervisor']);
        })->get();

        $result = [];
        foreach ($users as $user) {
            $roles = $user->roles->pluck('slug')->toArray();
            foreach (['opc_id' => 'promotor', 'liner_id' => 'consultor', 'closer_id' => 'supervisor'] as $col => $role) {
                if (!in_array($role, $roles))
                    continue;
                $services = SalesService::with('proposal')
                    ->where($col, $user->id)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->get(['id', 'created_at', 'date', 'qualification', $col]);
                $result[] = [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'role' => $role,
                    'column' => $col,
                    'show_count' => $services->count(),
                    'services' => $services->map(fn($s) => [
                        'id' => $s->id,
                        'date' => $s->date,
                        'qualification' => $s->qualification,
                        'has_proposal' => $s->proposal !== null,
                        'proposal_status' => $s->proposal?->status,
                        'proposal_value' => $s->proposal?->total_value,
                    ])->values(),
                ];
            }
        }

        return response()->json([
            'period' => "$startDate → $endDate",
            'users' => $result,
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Display the Sales Ranking report.

     */
    public function salesRanking(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        return Inertia::render('Reports/SalesRanking', [
            'rankingData' => $this->buildRankingData($startDate, $endDate)
        ]);
    }

    /**
     * Export the Sales Ranking report to PDF.
     */
    public function exportPdf(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        $rankingData = $this->buildRankingData($startDate, $endDate);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.sales-ranking', compact('rankingData'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('ranking-vendas-' . date('Y-m-d') . '.pdf');
    }

    /**
     * Monta o ranking de OPCs, Liners e Closers do período.
     * Usuários desativados só entram se tiverem dados (shows) no período.
     */
    private function buildRankingData($startDate, $endDate): array
    {
        // Fetch active qualifications
        $activeQualifications = Qualification::where('is_active', true)->get(['code', 'name']);

        // Fetch users with their roles (including deactivated ones)
        $users = User::with('roles')->whereHas('roles', function ($q) {
            $q->whereIn('slug', ['promotor', 'consultor', 'supervisor']);
        })->get();

        $opcs = [];
        $liners = [];
        $closers = [];

        foreach ($users as $user) {
            if ($user->hasRole('promotor')) {
                $this->pushIfVisible($opcs, $user, $this->calculateMetrics($user, 'opc_id', $startDate, $endDate, $activeQualifications));
            }
            if ($user->hasRole('consultor')) {
                $this->pushIfVisible($liners, $user, $this->calculateMetrics($user, 'liner_id', $startDate, $endDate, $activeQualifications));
            }
            if ($user->hasRole('supervisor')) {
                $this->pushIfVisible($closers, $user, $this->calculateMetrics($user, 'closer_id', $startDate, $endDate, $activeQualifications));
            }
        }

        // Sort by Total Descending
        usort($opcs, fn($a, $b) => $b['total'] <=> $a['total']);
        usort($liners, fn($a, $b) => $b['total'] <=> $a['total']);
        usort($closers, fn($a, $b) => $b['total'] <=> $a['total']);

        return [
            'period' => Carbon::parse($startDate)->format('d/m/Y') . ' - ' . Carbon::parse($endDate)->format('d/m/Y'),
            'active_qualifications' => $activeQualifications,
            'opcs' => $opcs,
            'liners' => $liners,
            'closers' => $closers
        ];
    }

    /**
     * Usuário ativo sempre aparece; desativado só aparece se tiver shows no período.
     */
    private function pushIfVisible(array &$list, User $user, array $metrics): void
    {
        if ($user->status || $metrics['qualificacao']['show'] > 0) {
            $list[] = $metrics;
        }
    }

    private function calculateMetrics(User $user, string $roleColumn, $startDate, $endDate, $activeQualifications)
    {
        // Query Sales Services associated with the user as this specific role in the date range
        $services = SalesService::with('proposal')
            ->where($roleColumn, $user->id)
            ->whereBetween('created_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
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

    /**
     * Relatório de Desempenho Comercial: casais atendidos, vendas, conversão e VGV
     * por perfil (qualificação), ponto de captação e profissional (OPC, Liner, Closer, MKT).
     * Venda = proposta aprovada ou finalizada; VGV = soma do valor total dessas propostas.
     */
    public function commercialPerformance(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        $services = SalesService::with(['proposal:id,sales_service_id,status,total_value'])
            ->whereBetween('created_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
            ->get(['id', 'qualification', 'local', 'opc_id', 'liner_id', 'closer_id', 'mkt_id']);

        // Venda e VGV de cada atendimento
        $rows = $services->map(function ($service) {
            $sold = $service->proposal && in_array(strtolower($service->proposal->status ?? ''), ['approved', 'completed']);
            return [
                'service' => $service,
                'sold' => $sold,
                'vgv' => $sold ? (float) ($service->proposal->total_value ?? 0) : 0,
            ];
        });

        $qualifications = Qualification::pluck('name', 'code');
        $capturePoints = \App\Models\CapturePoint::pluck('name', 'value');
        $userNames = User::pluck('name', 'id');

        $byProfile = $this->groupPerformance($rows, fn($s) => strtoupper(trim($s->qualification ?? '')) ?: null,
            fn($code) => $code ? ($qualifications[$code] ?? $code) . " ({$code})" : 'Sem perfil');

        $byPoint = $this->groupPerformance($rows, fn($s) => $s->local ?: null,
            fn($value) => $value ? ($capturePoints[$value] ?? ucfirst(strtolower($value))) : 'Sem ponto');

        $professionals = [];
        foreach (['opc' => 'opc_id', 'liner' => 'liner_id', 'closer' => 'closer_id', 'mkt' => 'mkt_id'] as $role => $column) {
            $professionals[$role] = $this->groupPerformance(
                $rows->filter(fn($r) => !empty($r['service']->{$column})),
                fn($s) => $s->{$column},
                fn($id) => $userNames[$id] ?? "Usuário #{$id}"
            );
        }

        return Inertia::render('Reports/CommercialPerformance', [
            'period' => Carbon::parse($startDate)->format('d/m/Y') . ' - ' . Carbon::parse($endDate)->format('d/m/Y'),
            'totals' => $this->performanceMetrics('Total', $rows),
            'byProfile' => $byProfile,
            'byPoint' => $byPoint,
            'professionals' => $professionals,
        ]);
    }

    /**
     * Agrupa os atendimentos por uma chave e calcula as métricas de cada grupo (ordenado por VGV).
     */
    private function groupPerformance($rows, callable $keyOf, callable $labelOf): array
    {
        return $rows->groupBy(fn($r) => $keyOf($r['service']) ?? '')
            ->map(fn($group, $key) => $this->performanceMetrics($labelOf($key === '' ? null : $key), $group))
            ->sortByDesc(fn($m) => [$m['vgv'], $m['atendidos']])
            ->values()
            ->all();
    }

    private function performanceMetrics(string $label, $group): array
    {
        $atendidos = $group->count();
        $vendas = $group->where('sold', true)->count();
        $vgv = $group->sum('vgv');

        return [
            'label' => $label,
            'atendidos' => $atendidos,
            'vendas' => $vendas,
            'conversao' => $atendidos > 0 ? round($vendas / $atendidos * 100, 2) : 0,
            'vgv' => round($vgv, 2),
            'ticket_medio' => $vendas > 0 ? round($vgv / $vendas, 2) : 0,
        ];
    }
}
