<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PointTable\Holiday;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HolidayController extends Controller
{
    public function index()
    {
        $holidays = Holiday::orderBy('holiday_date', 'asc')->get();
        return Inertia::render('Admin/Holidays/Index', [
            'holidays' => $holidays
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'holiday_date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        Holiday::create($validated);

        return redirect()->back()->with('success', 'Feriado criado com sucesso!');
    }

    public function update(Request $request, Holiday $holiday)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'holiday_date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $holiday->update($validated);

        return redirect()->back()->with('success', 'Feriado atualizado com sucesso!');
    }

    public function destroy(Holiday $holiday)
    {
        $holiday->delete();
        return redirect()->back()->with('success', 'Feriado excluído com sucesso!');
    }
}
