<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FichaTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FichaTemplateController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Settings/FichaTemplates/Index', [
            'templates' => FichaTemplate::orderBy('name')->get()
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Settings/FichaTemplates/Form', [
            'template' => null
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($request->is_default) {
            FichaTemplate::where('is_default', true)->update(['is_default' => false]);
        }

        FichaTemplate::create($validated);

        return redirect()->route('admin.settings.ficha_templates.index')->with('success', 'Modelo de ficha criado com sucesso!');
    }

    public function edit(FichaTemplate $fichaTemplate)
    {
        return Inertia::render('Admin/Settings/FichaTemplates/Form', [
            'template' => $fichaTemplate
        ]);
    }

    public function update(Request $request, FichaTemplate $fichaTemplate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($request->is_default && !$fichaTemplate->is_default) {
            FichaTemplate::where('id', '!=', $fichaTemplate->id)->update(['is_default' => false]);
        }

        $fichaTemplate->update($validated);

        return redirect()->route('admin.settings.ficha_templates.index')->with('success', 'Modelo de ficha atualizado com sucesso!');
    }

    public function destroy(FichaTemplate $fichaTemplate)
    {
        $fichaTemplate->delete();
        return redirect()->back()->with('success', 'Modelo excluído com sucesso!');
    }

    public function previewPdf(FichaTemplate $fichaTemplate)
    {
        $html = $fichaTemplate->content;
        
        $replacements = [
            '{{lead_id}}' => '9999',
            '{{hora_entrada}}' => '10:00',
            '{{hora_saida}}' => '12:00',
            '{{nome_cliente}}' => 'João da Silva (Exemplo)',
            '{{data_nascimento_cliente}}' => '01/01/1980',
            '{{ocupacao_cliente}}' => 'Engenheiro',
            '{{area_cliente}}' => 'Exemplo',
            '{{cpf_cliente}}' => '123.456.789-00',
            '{{email_cliente}}' => 'joao.exemplo@email.com',
            '{{celular_cliente}}' => '(11) 99999-9999',
            '{{nome_conjuge}}' => 'Maria Souza da Silva',
            '{{data_nascimento_conjuge}}' => '15/05/1982',
            '{{ocupacao_conjuge}}' => 'Arquiteta',
            '{{area_conjuge}}' => 'Exemplo',
            '{{qtd_filhos}}' => '2',
            '{{nomes_filhos}}' => 'Pedro, Ana',
            '{{endereco_cliente}}' => 'Rua Exemplo, 123',
            '{{bairro_cliente}}' => 'Centro',
            '{{cidade_cliente}}' => 'São Paulo',
            '{{uf_cliente}}' => 'SP',
            '{{cep_cliente}}' => '01000-000',
            '{{consultor}}' => 'Consultor Exemplo',
            '{{supervisor}}' => 'Supervisor Exemplo',
            '{{data_atendimento}}' => date('d/m/Y'),
            '{{local_atendimento}}' => 'Escritório Matriz',
            '{{promotor}}' => 'Promotor Exemplo',
            '{{brindes}}' => 'Kit Boas Vindas, Garrafa Térmica',
        ];

        
        foreach ($replacements as $tag => $val) {
            $html = str_replace($tag, $val, $html);
        }
        
        return view('pdf.custom-sheet', ['html' => $html]);
    }
}
