<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PointTable\Holiday;
use App\Models\PointTable\Season;
use App\Models\PointTable\Resort;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CalendarSettingsController extends Controller
{
    public function index(Request $request)
    {
        PointTableController::ensureMatrixIntegrity();

        $currentYear = now()->year;
        $selectedYear = (int) $request->input('year', $currentYear);

        $seasons  = Season::orderBy('advance_days', 'asc')->get();
        $holidays = Holiday::orderBy('holiday_date', 'asc')->get();

        // Carrega toda a estrutura de resorts para a tabela de pontos
        $resorts = Resort::with(['accommodations' => function ($q) {
            $q->orderBy('max_pax', 'asc');
        }, 'accommodations.scores.season'])->get();

        $formattedResorts = $resorts->map(function ($resort) use ($seasons) {
            $accommodationGroups = [];
            
            // Agrupar acomodações (por group_name ou name se for null)
            $grouped = $resort->accommodations->groupBy(function($item) {
                return $item->group_name ?? $item->name;
            });

            foreach ($grouped as $groupName => $accs) {
                $columns = [];
                foreach ($accs as $acc) {
                    // Buscar os PAX únicos dessa acomodação
                    $paxs = $acc->scores->pluck('pax')->unique()->sort()->values();
                    foreach ($paxs as $pax) {
                        $columns[] = [
                            'label' => str_pad($pax, 2, '0', STR_PAD_LEFT) . ' PAX',
                            'key' => 'acc_' . $acc->id . '_pax_' . $pax,
                            'accommodation_id' => $acc->id,
                            'pax' => $pax,
                        ];
                    }
                }

                $accommodationGroups[] = [
                    'label' => $groupName,
                    'columns' => $columns,
                    'accommodation_id' => $accs->first()->id,
                ];
            }

            // Montar as linhas (uma por temporada)
            $rows = [];
            foreach ($seasons as $season) {
                $row = [
                    'season_id' => $season->id,
                    'season' => $season->name,
                    'days' => $season->advance_days . ' DIAS',
                    'period' => $season->period_description,
                ];

                foreach ($resort->accommodations as $acc) {
                    $scores = $acc->scores->where('season_id', $season->id);
                    foreach ($scores as $score) {
                        $key = 'acc_' . $acc->id . '_pax_' . $score->pax;
                        $row[$key] = number_format($score->points, 0, ',', '.'); // Mostrar formatado
                        $row[$key . '_raw'] = $score->points; // Valor real para edição
                        $row[$key . '_score_id'] = $score->id; // ID para update
                    }
                }
                $rows[] = $row;
            }

            return [
                'id' => $resort->id,
                'name' => $resort->name,
                'icon' => $resort->icon,
                'color' => $resort->color_theme,
                'headerColor' => $resort->color_theme,
                'accommodationGroups' => $accommodationGroups,
                'rows' => $rows,
            ];
        });

        return Inertia::render('Admin/CalendarSettings/Index', [
            'seasons'      => $seasons,
            'holidays'     => $holidays,
            'resorts'      => $formattedResorts,
            'selectedYear' => $selectedYear,
        ]);
    }
}
