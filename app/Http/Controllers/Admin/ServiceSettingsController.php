<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ServiceSettingsController extends Controller
{
    public function index()
    {
        $setting = Setting::where('key', 'service_list_columns')->first();
        
        $columns = $setting ? $setting->value : [
            'id' => true,
            'date' => true,
            'time' => true,
            'clients' => true,
            'mkt' => true,
            'opc' => true,
            'liner' => true,
            'closer' => true,
            'qualification' => true,
            'status' => true,
            'actions' => true,
        ];

        return Inertia::render('Admin/Settings/ServiceColumns', [
            'columns' => $columns
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'columns' => 'required|array',
        ]);

        Setting::updateOrCreate(
            ['key' => 'service_list_columns'],
            ['value' => $validated['columns']]
        );

        return redirect()->back()->with('success', 'Configurações de colunas atualizadas!');
    }
}
