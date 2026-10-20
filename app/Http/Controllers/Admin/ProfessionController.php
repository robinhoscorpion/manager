<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Profession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProfessionController extends Controller
{
    public function index()
    {
        $usage = Profession::usageCounts();

        $professions = Profession::orderBy('reviewed')->orderBy('name')->get()->map(function ($profession) use ($usage) {
            $profession->usage_count = $usage[mb_strtolower($profession->name)] ?? 0;
            return $profession;
        });

        return Inertia::render('Admin/Professions/Index', [
            'professions' => $professions,
        ]);
    }

    public function store(Request $request)
    {
        $request->merge(['name' => Profession::normalize($request->name)]);
        $request->validate([
            'name' => 'required|string|max:255|unique:professions,name',
            'active' => 'boolean',
        ]);

        Profession::create([
            'name' => $request->name,
            'active' => $request->has('active') ? $request->active : true,
            'reviewed' => true,
        ]);

        return redirect()->back()->with('success', 'Profissão criada com sucesso.');
    }

    public function update(Request $request, Profession $profession)
    {
        $request->merge(['name' => Profession::normalize($request->name)]);
        $request->validate([
            'name' => 'required|string|max:255|unique:professions,name,' . $profession->id,
            'active' => 'boolean',
        ]);

        DB::transaction(function () use ($request, $profession) {
            // Renomear corrige também os cadastros que já usam o nome antigo
            if ($profession->name !== $request->name) {
                Profession::replaceEverywhere($profession->name, $request->name);
            }

            $profession->update([
                'name' => $request->name,
                'active' => $request->has('active') ? $request->active : $profession->active,
                'reviewed' => true,
            ]);
        });

        return redirect()->back()->with('success', 'Profissão atualizada com sucesso.');
    }

    /**
     * Marca uma profissão digitada pela equipe como revisada (aprovada).
     */
    public function approve(Profession $profession)
    {
        $profession->update(['reviewed' => true]);

        return redirect()->back()->with('success', 'Profissão aprovada.');
    }

    /**
     * Unifica uma profissão duplicada/errada em outra: os cadastros passam a usar a profissão destino.
     */
    public function merge(Request $request, Profession $profession)
    {
        $request->validate([
            'target_id' => 'required|integer|exists:professions,id|different:' . $profession->id,
        ]);

        $target = Profession::findOrFail($request->target_id);

        DB::transaction(function () use ($profession, $target) {
            Profession::replaceEverywhere($profession->name, $target->name);
            $profession->delete();
        });

        return redirect()->back()->with('success', "Profissão unificada em \"{$target->name}\".");
    }

    public function destroy(Profession $profession)
    {
        if ((Profession::usageCounts()[mb_strtolower($profession->name)] ?? 0) > 0) {
            return redirect()->back()->with('error', 'Esta profissão está em uso. Unifique-a com outra ou desative-a.');
        }

        $profession->delete();

        return redirect()->back()->with('success', 'Profissão excluída com sucesso.');
    }
}
