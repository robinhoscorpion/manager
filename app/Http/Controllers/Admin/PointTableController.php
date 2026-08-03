<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PointTable\Resort;
use App\Models\PointTable\Season;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PointTableController extends Controller
{
    public function index()
    {
        // Carrega toda a estrutura formatada
        $resorts = Resort::with(['accommodations' => function ($q) {
            $q->orderBy('max_pax', 'asc');
        }, 'accommodations.scores.season'])->get();

        $seasons = Season::orderBy('advance_days', 'asc')->get();

        // Estruturar os dados para o frontend (simulando a estrutura antiga, mas dinâmica)
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
                        $row[$key] = number_format($score->points, 0, ',', '.'); // Mostrar formatado para a view
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

        return Inertia::render('Admin/TabelaPontos/Index', [
            'resorts' => $formattedResorts,
            'seasons' => $seasons, // Envia as temporadas puras também para gerenciar
        ]);
    }

    public function updateScore(Request $request)
    {
        $validated = $request->validate([
            'score_id' => 'required|exists:point_scores,id',
            'points' => 'required|numeric',
        ]);

        $score = \App\Models\PointTable\Score::find($validated['score_id']);
        $score->points = $validated['points'];
        $score->save();

        return redirect()->back()->with('success', 'Pontuação atualizada com sucesso!');
    }
}
