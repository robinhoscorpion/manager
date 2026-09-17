<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PointTable\Holiday;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'holiday_date'   => 'nullable|date',
            'start_date'     => 'nullable|date',
            'end_date'       => 'nullable|date',
            'classification' => 'nullable|string|max:255',
        ]);

        Holiday::create($validated);

        return redirect()->back()->with('success', 'Feriado criado com sucesso!');
    }

    public function update(Request $request, Holiday $holiday)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'holiday_date'   => 'nullable|date',
            'start_date'     => 'nullable|date',
            'end_date'       => 'nullable|date',
            'classification' => 'nullable|string|max:255',
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
