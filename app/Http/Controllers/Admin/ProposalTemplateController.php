<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProposalTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProposalTemplateController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/ProposalTemplates/Index', [
            'templates' => ProposalTemplate::orderBy('name')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
            'file' => 'nullable|file|max:10240', // 10MB max
        ]);

        try {
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                if (!$file->isValid()) {
                    return back()->withErrors(['file' => 'O upload do arquivo falhou. Código do erro: ' . $file->getError()]);
                }
                
                $extension = strtolower($file->getClientOriginalExtension());
                if ($extension !== 'docx') {
                    return back()->withErrors(['file' => 'O arquivo deve obrigatoriamente ter a extensão .docx (Word).']);
                }

                $validated['original_filename'] = $file->getClientOriginalName();
                $validated['file_path'] = $file->store('proposal_templates');
            }

            ProposalTemplate::create($validated);

            return redirect()->back()->with('success', 'Modelo de proposta criado com sucesso.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erro ao salvar proposal template: ' . $e->getMessage());
            return back()->withErrors(['file' => 'Erro interno ao salvar o arquivo: ' . $e->getMessage()]);
        }
    }

    public function update(Request $request, ProposalTemplate $proposalTemplate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
            'file' => 'nullable|file|max:10240',
        ]);

        try {
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                if (!$file->isValid()) {
                    return back()->withErrors(['file' => 'O upload do arquivo falhou. Código do erro: ' . $file->getError()]);
                }

                $extension = strtolower($file->getClientOriginalExtension());
                if ($extension !== 'docx') {
                    return back()->withErrors(['file' => 'O arquivo deve obrigatoriamente ter a extensão .docx (Word).']);
                }

                $validated['original_filename'] = $file->getClientOriginalName();
                $validated['file_path'] = $file->store('proposal_templates');

                // Delete old file if exists
                if ($proposalTemplate->file_path) {
                    \Illuminate\Support\Facades\Storage::delete($proposalTemplate->file_path);
                }
            }

            $proposalTemplate->update($validated);

            return redirect()->back()->with('success', 'Modelo de proposta atualizado com sucesso.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Erro ao atualizar proposal template: ' . $e->getMessage());
            return back()->withErrors(['file' => 'Erro interno ao atualizar o arquivo: ' . $e->getMessage()]);
        }
    }

    public function destroy(ProposalTemplate $proposalTemplate)
    {
        if ($proposalTemplate->file_path) {
            \Illuminate\Support\Facades\Storage::delete($proposalTemplate->file_path);
        }
        $proposalTemplate->delete();
        return redirect()->back()->with('success', 'Modelo de proposta excluído com sucesso.');
    }

    public function download(ProposalTemplate $proposalTemplate)
    {
        if (!$proposalTemplate->file_path || !\Illuminate\Support\Facades\Storage::exists($proposalTemplate->file_path)) {
            return abort(404, 'Arquivo Word não encontrado no servidor.');
        }

        return \Illuminate\Support\Facades\Storage::download(
            $proposalTemplate->file_path,
            $proposalTemplate->original_filename ?? 'modelo_de_proposta.docx'
        );
    }

    public function testPrint(ProposalTemplate $proposalTemplate)
    {
        if (!$proposalTemplate->file_path || !\Illuminate\Support\Facades\Storage::exists($proposalTemplate->file_path)) {
            return abort(404, 'Arquivo Word não encontrado no servidor.');
        }

        $filePath = \Illuminate\Support\Facades\Storage::path($proposalTemplate->file_path);

        try {
            $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($filePath);
        } catch (\Exception $e) {
            return abort(500, 'Erro ao processar o arquivo Word: ' . $e->getMessage());
        }

        $mockData = $this->getMockData();

        $variablesCount = $templateProcessor->getVariableCount();
        foreach ($mockData as $tag => $value) {
            $cleanTag = str_replace(['${', '}'], '', $tag);
            $count = $variablesCount[$cleanTag] ?? 1;
            
            for ($i = 0; $i < $count; $i++) {
                $textRun = new \PhpOffice\PhpWord\Element\TextRun();
                $textRun->addText($value, ['bgColor' => 'FFFF00']);
                $templateProcessor->setComplexValue($cleanTag, $textRun);
            }
        }

        $tempFileName = 'TESTE_' . ($proposalTemplate->original_filename ?? 'modelo_de_proposta.docx');
        $tempPath = storage_path('app/temp/' . $tempFileName);

        if (!\Illuminate\Support\Facades\File::exists(storage_path('app/temp'))) {
            \Illuminate\Support\Facades\File::makeDirectory(storage_path('app/temp'), 0755, true);
        }

        $templateProcessor->saveAs($tempPath);

        return response()->download($tempPath)->deleteFileAfterSend(true);
    }

    public function downloadPdf(ProposalTemplate $proposalTemplate)
    {
        if (!$proposalTemplate->file_path || !\Illuminate\Support\Facades\Storage::exists($proposalTemplate->file_path)) {
            return abort(404, 'Arquivo Word não encontrado no servidor.');
        }

        $filePath = \Illuminate\Support\Facades\Storage::path($proposalTemplate->file_path);

        try {
            $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($filePath);
        } catch (\Exception $e) {
            return abort(500, 'Erro ao processar o arquivo Word: ' . $e->getMessage());
        }

        $mockData = $this->getMockData();

        $variablesCount = $templateProcessor->getVariableCount();
        foreach ($mockData as $tag => $value) {
            $cleanTag = str_replace(['${', '}'], '', $tag);
            $count = $variablesCount[$cleanTag] ?? 1;
            
            for ($i = 0; $i < $count; $i++) {
                $textRun = new \PhpOffice\PhpWord\Element\TextRun();
                $textRun->addText($value, ['bgColor' => 'FFFF00']);
                $templateProcessor->setComplexValue($cleanTag, $textRun);
            }
        }

        $tempFileName = 'TESTE_' . ($proposalTemplate->original_filename ?? 'modelo_de_proposta.docx');
        $tempPath = storage_path('app/temp/' . $tempFileName);

        if (!\Illuminate\Support\Facades\File::exists(storage_path('app/temp'))) {
            \Illuminate\Support\Facades\File::makeDirectory(storage_path('app/temp'), 0755, true);
        }

        $templateProcessor->saveAs($tempPath);

        // Convert to PDF
        $pdfFileName = str_replace('.docx', '.pdf', $tempFileName);
        $pdfPath = storage_path('app/temp/' . $pdfFileName);

        try {
            $converter = new \NcJoes\OfficeConverter\OfficeConverter($tempPath, storage_path('app/temp'), 'soffice', false);
            $converter->convertTo($pdfFileName);
        } catch (\Exception $e) {
            return response()->download($tempPath, $tempFileName)->deleteFileAfterSend(true);
        }

        @unlink($tempPath);

        if (!\Illuminate\Support\Facades\File::exists($pdfPath)) {
            return back()->with('error', 'Falha ao gerar o arquivo PDF.');
        }

        return response()->download($pdfPath, $pdfFileName)->deleteFileAfterSend(true);
    }

    private function getMockData(): array
    {
        return [
            '${CLIENTE_NOME}' => 'João Carlos da Silva',
            '${CLIENTE_CPF}' => '123.456.789-00',
            '${CLIENTE_RG}' => '12.345.678-9',
            '${CLIENTE_NASCIMENTO}' => '15/04/1985',
            '${CLIENTE_ENDERECO}' => 'Av. Paulista, 1000, Apto 2, Centro, São Paulo/SP, 01310-100',
            '${CLIENTE_ESTADO_CIVIL}' => 'Casado(a)',
            '${CLIENTE_PROFISSAO}' => 'Engenheiro',
            '${CLIENTE_NACIONALIDADE}' => 'Brasileiro(a)',
            '${CLIENTE_EMAIL}' => 'joao@email.com',
            '${CLIENTE_TELEFONE}' => '(11) 98765-4321',
            '${CLIENTE_CIDADE_UF}' => 'São Paulo / SP',

            '${CONJUNGE_NOME}' => 'Maria Oliveira da Silva',
            '${CONJUNGE_CPF}' => '987.654.321-00',
            '${CONJUNGE_RG}' => '98.765.432-1',
            '${CONJUNGE_NASCIMENTO}' => '20/10/1988',
            '${CONJUNGE_ESTADO_CIVIL}' => 'Casada',
            '${CONJUNGE_PROFISSAO}' => 'Arquiteta',
            '${CONJUNGE_NACIONALIDADE}' => 'Brasileira',

            '${PROPOSTA_NUMERO}' => '2026/001',
            '${PROPOSTA_PLANO}' => 'Premium Plus',
            '${PROPOSTA_CATEGORIA}' => 'Exclusive',
            '${PROPOSTA_PACOTE}' => '7 Noites',
            '${PROPOSTA_PONTOS}' => '150.000 Pontos',
            '${PROPOSTA_USO_INICIAL}' => '2027',
            '${PROPOSTA_VIGENCIA}' => '12 Meses',
            '${PROPOSTA_VALOR_TOTAL}' => 'R$ 45.000,00',
            '${PROPOSTA_ENTRADA}' => 'R$ 10.000,00',
            '${PROPOSTA_RESUMO_ENTRADA}' => "5x de R$ 1.000,00 no Cartão de Crédito (Início: 10/08/2026)\n + 5x de R$ 1.000,00 no Boleto (Início: 10/01/2027)",
            '${PROPOSTA_SALDO}' => 'R$ 35.000,00',
            '${PROPOSTA_RESUMO_SALDO}' => "20x de R$ 1.000,00 no Boleto Bancário (Início: 10/02/2027)\n + 15x de R$ 1.000,00 no PIX (Início: 10/10/2028)",
            '${PROPOSTA_RESUMO_TAXA}' => "1x de R$ 125,00 no PIX (Início: 16/07/2026)\n + 1x de R$ 125,00 no Dinheiro (Início: 16/08/2026)",
            '${PROPOSTA_RESUMO_MANUTENCAO}' => "Anual de R$ 600,00 no Boleto Bancário\n + Anual de R$ 600,00 no Cartão de Crédito",

            '${VENDEDOR_NOME}' => 'Marcos Sales',
            '${PROMOTOR_NOME}' => 'Lucas Silva',
            '${CONSULTOR_NOME}' => 'Mariana Costa',
            '${SUPERVISOR_NOME}' => 'Carlos Alberto',
            '${GERENTE_NOME}' => 'Ana Beatriz',
            '${DATA_ATUAL}' => date('d/m/Y'),
            '${HORA_ATUAL}' => date('H:i:s'),
            '${PROPOSTA_DATA}' => date('d/m/Y'),
            '${USUARIO_IMPRESSAO}' => auth()->user()->name ?? 'Administrador',
        ];
    }
}
