<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ServiceFormSettingsController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Settings/ServiceForm', [
            'settings' => Setting::serviceForm(),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'relationship_field' => 'required|boolean',
        ]);

        Setting::updateOrCreate(
            ['key' => 'service_form'],
            ['value' => array_merge(Setting::serviceForm(), $validated)]
        );

        return redirect()->back()->with('success', 'Configurações da ficha de atendimento atualizadas!');
    }
}
