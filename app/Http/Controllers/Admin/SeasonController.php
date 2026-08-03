<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PointTable\Season;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SeasonController extends Controller
{
    public function index()
    {
        $seasons = Season::orderBy('advance_days', 'asc')->get();
        return Inertia::render('Admin/Seasons/Index', [
            'seasons' => $seasons
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Seasons/Form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'advance_days' => 'required|integer|min:0',
            'period_description' => 'nullable|string',
            'months_active' => 'nullable|array',
            'special_dates' => 'nullable|array',
        ]);

        Season::create($validated);

        return redirect()->route('admin.seasons.index')->with('success', 'Temporada cadastrada com sucesso!');
    }

    public function edit(Season $season)
    {
        return Inertia::render('Admin/Seasons/Form', [
            'season' => $season
        ]);
    }

    public function update(Request $request, Season $season)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'advance_days' => 'required|integer|min:0',
            'period_description' => 'nullable|string',
            'months_active' => 'nullable|array',
            'special_dates' => 'nullable|array',
        ]);

        $season->update($validated);

        return redirect()->route('admin.seasons.index')->with('success', 'Temporada atualizada com sucesso!');
    }

    public function destroy(Season $season)
    {
        $season->delete();
        return redirect()->route('admin.seasons.index')->with('success', 'Temporada excluída com sucesso!');
    }

    public function mapping()
    {
        $seasons = Season::orderBy('advance_days', 'asc')->get();
        return Inertia::render('Admin/Seasons/Mapping', [
            'seasons' => $seasons
        ]);
    }

    public function storeMapping(Request $request)
    {
        $validated = $request->validate([
            'mappings' => 'required|array',
            'mappings.*.id' => 'required|exists:point_seasons,id',
            'mappings.*.items' => 'array',
        ]);

        foreach ($validated['mappings'] as $mapping) {
            $season = Season::find($mapping['id']);
            
            $months = [];
            $specialDates = [];
            
            foreach ($mapping['items'] as $item) {
                if ($item['type'] === 'month') {
                    $months[] = $item['name'];
                } else {
                    $specialDates[] = $item['name'];
                }
            }
            
            // Build the period description automatically
            $descriptionParts = [];
            if (!empty($months)) {
                $descriptionParts[] = implode(', ', $months);
            }
            if (!empty($specialDates)) {
                $descriptionParts[] = implode(', ', $specialDates);
            }
            
            $season->months_active = $months;
            $season->special_dates = $specialDates;
            $season->period_description = mb_strtoupper(implode("\n", $descriptionParts), 'UTF-8');
            $season->save();
        }

        return redirect()->back()->with('success', 'Mapeamento de temporadas salvo com sucesso!');
    }
}
