<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CapturePoint;
use App\Models\SalesService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CapturePointController extends Controller
{
    public function index()
    {
        $points = CapturePoint::orderBy('name')->get()->map(function ($point) {
            $point->services_count = SalesService::where('local', $point->value)->count();
            return $point;
        });

        return Inertia::render('Admin/CapturePoints/Index', [
            'points' => $points
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:capture_points,name',
            'active' => 'boolean'
        ]);

        CapturePoint::create([
            'name' => $request->name,
            'value' => $this->uniqueValue($request->name),
            'active' => $request->has('active') ? $request->active : true
        ]);

        return redirect()->back()->with('success', 'Ponto de captação criado com sucesso.');
    }

    public function update(Request $request, CapturePoint $capturePoint)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:capture_points,name,' . $capturePoint->id,
            'active' => 'boolean'
        ]);

        // O "value" não muda para não desvincular os atendimentos já gravados
        $capturePoint->update($request->only(['name', 'active']));

        return redirect()->back()->with('success', 'Ponto de captação atualizado com sucesso.');
    }

    public function destroy(CapturePoint $capturePoint)
    {
        if (SalesService::where('local', $capturePoint->value)->exists()) {
            return redirect()->back()->with('error', 'Este ponto de captação possui atendimentos vinculados. Desative-o em vez de excluir.');
        }

        $capturePoint->delete();
        return redirect()->back()->with('success', 'Ponto de captação excluído com sucesso.');
    }

    private function uniqueValue(string $name): string
    {
        $base = Str::slug($name, '_') ?: 'ponto';
        $value = $base;
        $i = 2;
        while (CapturePoint::where('value', $value)->exists()) {
            $value = $base . '_' . $i++;
        }
        return $value;
    }
}
