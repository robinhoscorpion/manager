<?php
namespace App\Http\Controllers;

use App\Models\Qualification;
use Illuminate\Http\Request;
use Inertia\Inertia;

class QualificationController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Qualifications/Index', [
            'qualifications' => Qualification::orderBy('name')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:5',
            'color' => 'required|string',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        Qualification::create($validated);

        return redirect()->back()->with('success', 'Qualificação cadastrada com sucesso!');
    }

    public function update(Request $request, Qualification $qualification)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:5',
            'color' => 'required|string',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $qualification->update($validated);

        return redirect()->back()->with('success', 'Qualificação atualizada com sucesso!');
    }

    public function destroy(Qualification $qualification)
    {
        $qualification->delete();

        return redirect()->back()->with('success', 'Qualificação excluída com sucesso!');
    }
}
