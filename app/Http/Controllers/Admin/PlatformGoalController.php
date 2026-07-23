<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformGoal;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class PlatformGoalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Admin/Goals/Index', [
            'goals' => PlatformGoal::orderBy('year', 'desc')->orderBy('month', 'desc')->get()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2000'],
            'revenue_target' => ['required', 'numeric', 'min:0'],
            'contracts_target' => ['required', 'integer', 'min:0'],
        ]);

        // Check uniqueness
        if (PlatformGoal::where('month', $request->month)->where('year', $request->year)->exists()) {
            return redirect()->back()->with('error', 'Já existe uma meta cadastrada para este mês e ano.');
        }

        PlatformGoal::create([
            'month' => $request->month,
            'year' => $request->year,
            'revenue_target' => $request->revenue_target,
            'contracts_target' => $request->contracts_target,
        ]);

        return redirect()->back()->with('message', 'Meta cadastrada com sucesso!');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PlatformGoal $platform_goal)
    {
        $request->validate([
            'revenue_target' => ['required', 'numeric', 'min:0'],
            'contracts_target' => ['required', 'integer', 'min:0'],
        ]);

        $platform_goal->update([
            'revenue_target' => $request->revenue_target,
            'contracts_target' => $request->contracts_target,
        ]);

        return redirect()->back()->with('message', 'Meta atualizada com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PlatformGoal $platform_goal)
    {
        $platform_goal->delete();
        return redirect()->back()->with('message', 'Meta removida com sucesso!');
    }
}
