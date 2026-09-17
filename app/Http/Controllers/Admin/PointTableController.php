<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PointTable\Resort;
use App\Models\PointTable\Season;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PointTableController extends Controller
{
    public static function ensureMatrixIntegrity()
    {
        $seasons = Season::all();
        if ($seasons->isEmpty()) {
            return;
        }

        $accommodations = \App\Models\PointTable\Accommodation::with('scores')->get();

        foreach ($accommodations as $acc) {
            $paxs = $acc->scores->pluck('pax')->unique();
            if ($paxs->isEmpty()) {
                $paxs = collect([$acc->max_pax > 0 ? (int)$acc->max_pax : 2]);
            }

            foreach ($seasons as $season) {
                foreach ($paxs as $pax) {
                    \App\Models\PointTable\Score::firstOrCreate([
                        'accommodation_id' => $acc->id,
                        'season_id'        => $season->id,
                        'pax'              => (int)$pax,
                    ], [
                        'points'           => 0,
                    ]);
                }
            }
        }
    }

    public function index()
    {
        self::ensureMatrixIntegrity();

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
                    'accommodation_id' => $accs->first()->id, // Adicionado para permitir criar PAX em acomodações vazias
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

    public function storeResort(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:50',
            'color_theme' => 'nullable|string|max:50',
        ]);

        Resort::create($validated);

        return redirect()->back()->with('success', 'Empreendimento criado com sucesso!');
    }

    public function destroyResort($id)
    {
        $resort = Resort::findOrFail($id);
        $resort->delete();
        return redirect()->back()->with('success', 'Empreendimento excluído com sucesso!');
    }

    public function storeAccommodation(Request $request, $resort_id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'group_name' => 'nullable|string|max:255',
            'max_pax' => 'required|integer|min:1',
        ]);

        $resort = Resort::findOrFail($resort_id);

        $acc = \App\Models\PointTable\Accommodation::create([
            'resort_id' => $resort->id,
            'name' => $validated['name'],
            'group_name' => $validated['group_name'] ?? $validated['name'],
            'max_pax' => $validated['max_pax'],
        ]);

        self::ensureMatrixIntegrity();

        return redirect()->back()->with('success', 'Acomodação criada com sucesso!');
    }

    public function destroyAccommodation($id)
    {
        $acc = \App\Models\PointTable\Accommodation::findOrFail($id);
        $acc->delete();
        return redirect()->back()->with('success', 'Acomodação excluída com sucesso!');
    }

    public function storeSeason(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'advance_days' => 'required|integer|min:0',
            'period_description' => 'nullable|string',
        ]);

        Season::create([
            'name' => $validated['name'],
            'advance_days' => $validated['advance_days'],
            'period_description' => $validated['period_description'],
            'months_active' => [],
            'special_dates' => [],
        ]);

        self::ensureMatrixIntegrity();

        return redirect()->back()->with('success', 'Temporada criada com sucesso!');
    }

    public function destroySeason($id)
    {
        $season = Season::findOrFail($id);
        $season->delete();
        return redirect()->back()->with('success', 'Temporada excluída com sucesso!');
    }

    public function storePax(Request $request, $accommodation_id)
    {
        $validated = $request->validate([
            'pax' => 'required|integer|min:1',
        ]);

        $acc = \App\Models\PointTable\Accommodation::findOrFail($accommodation_id);
        $pax = (int) $validated['pax'];

        // Atualizar max_pax da acomodação se o novo for maior
        if ($pax > $acc->max_pax) {
            $acc->update(['max_pax' => $pax]);
        }

        // Criar a pontuação 0 para cada temporada existente
        $seasons = Season::all();
        foreach ($seasons as $season) {
            \App\Models\PointTable\Score::firstOrCreate([
                'accommodation_id' => $acc->id,
                'season_id' => $season->id,
                'pax' => $pax,
            ], [
                'points' => 0,
            ]);
        }

        self::ensureMatrixIntegrity();

        return redirect()->back()->with('success', 'Capacidade (PAX) criada com sucesso!');
    }

    public function destroyPax(Request $request, $accommodation_id, $pax)
    {
        \App\Models\PointTable\Score::where('accommodation_id', $accommodation_id)
            ->where('pax', $pax)
            ->delete();

        return redirect()->back()->with('success', 'Capacidade (PAX) excluída com sucesso!');
    }
}
