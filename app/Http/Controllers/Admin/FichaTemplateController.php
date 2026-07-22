<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FichaTemplateController extends Controller
{
    public function edit()
    {
        $setting = Setting::where('key', 'ficha_atendimento_template')->first();
        
        $template = $setting ? $setting->value : [
            'content' => '<h1>Ficha de Atendimento</h1><p>Cliente: {{cliente_nome}}</p>'
        ];

        return Inertia::render('Admin/Settings/FichaTemplate', [
            'template' => $template
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        Setting::updateOrCreate(
            ['key' => 'ficha_atendimento_template'],
            ['value' => ['content' => $validated['content']]]
        );

        return redirect()->back()->with('success', 'Ficha de Atendimento atualizada com sucesso!');
    }
}
