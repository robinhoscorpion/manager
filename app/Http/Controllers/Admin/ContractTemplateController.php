<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContractTemplate;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContractTemplateController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/ContractTemplates/Index', [
            'templates' => ContractTemplate::with('products')->orderBy('name')->get(),
            'products' => Product::with('productType')->orderBy('product_type_id')->orderBy('name')->get(['id', 'name', 'product_type_id', 'category', 'package'])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'nullable|string',
            'is_default' => 'boolean',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
            'file' => 'nullable|file|mimes:docx|max:10240', // 10MB max
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $validated['original_filename'] = $file->getClientOriginalName();
            $validated['file_path'] = $file->store('contract_templates');
        }

        if ($validated['is_default']) {
            ContractTemplate::where('is_default', true)->update(['is_default' => false]);
            // If it's global, we might want to clear specific links? 
            // Or keep them as "overrides". Let's clear for now to be safe.
            Product::whereNotNull('contract_template_id')->update(['contract_template_id' => null]);
        }

        $template = ContractTemplate::create($validated);

        if (!$validated['is_default'] && !empty($validated['product_ids'])) {
            Product::whereIn('id', $validated['product_ids'])->update(['contract_template_id' => $template->id]);
        }

        return redirect()->back()->with('success', 'Modelo de contrato criado com sucesso.');
    }

    public function update(Request $request, ContractTemplate $contractTemplate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'nullable|string',
            'is_default' => 'boolean',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
            'file' => 'nullable|file|mimes:docx|max:10240',
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $validated['original_filename'] = $file->getClientOriginalName();
            $validated['file_path'] = $file->store('contract_templates');
            
            // Delete old file if exists
            if ($contractTemplate->file_path) {
                \Illuminate\Support\Facades\Storage::delete($contractTemplate->file_path);
            }
        }

        if ($validated['is_default']) {
            ContractTemplate::where('id', '!=', $contractTemplate->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
            
            // Clear specific links if this becomes global
            Product::whereNotNull('contract_template_id')->update(['contract_template_id' => null]);
        }

        $contractTemplate->update($validated);

        // Sync products
        if (!$validated['is_default']) {
            // Remove from products not in list
            Product::where('contract_template_id', $contractTemplate->id)
                ->whereNotIn('id', $validated['product_ids'] ?? [])
                ->update(['contract_template_id' => null]);
            
            // Add to products in list
            if (!empty($validated['product_ids'])) {
                Product::whereIn('id', $validated['product_ids'])
                    ->update(['contract_template_id' => $contractTemplate->id]);
            }
        }

        return redirect()->back()->with('success', 'Modelo de contrato atualizado com sucesso.');
    }

    public function destroy(ContractTemplate $contractTemplate)
    {
        if ($contractTemplate->file_path) {
            \Illuminate\Support\Facades\Storage::delete($contractTemplate->file_path);
        }
        $contractTemplate->delete();
        return redirect()->back()->with('success', 'Modelo de contrato excluído com sucesso.');
    }

    public function download(ContractTemplate $contractTemplate)
    {
        if (!$contractTemplate->file_path || !\Illuminate\Support\Facades\Storage::exists($contractTemplate->file_path)) {
            return abort(404, 'Arquivo Word não encontrado no servidor.');
        }

        return \Illuminate\Support\Facades\Storage::download(
            $contractTemplate->file_path,
            $contractTemplate->original_filename ?? 'modelo_de_contrato.docx'
        );
    }

    public function testPrint(ContractTemplate $contractTemplate)
    {
        if (!$contractTemplate->file_path || !\Illuminate\Support\Facades\Storage::exists($contractTemplate->file_path)) {
            return abort(404, 'Arquivo Word não encontrado no servidor.');
        }

        $filePath = \Illuminate\Support\Facades\Storage::path($contractTemplate->file_path);
        
        try {
            $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($filePath);
        } catch (\Exception $e) {
            return abort(500, 'Erro ao processar o arquivo Word: ' . $e->getMessage());
        }

        $mockData = $this->getMockData();

        foreach ($mockData as $tag => $value) {
            // Remove the ${ and } from the tag since TemplateProcessor works with variables without braces
            $cleanTag = str_replace(['${', '}'], '', $tag);
            
            // Cria um TextRun com o valor e fundo amarelo para destacar
            $textRun = new \PhpOffice\PhpWord\Element\TextRun();
            $textRun->addText($value, ['bgColor' => 'FFFF00']);
            
            $templateProcessor->setComplexValue($cleanTag, $textRun);
        }

        $tempFileName = 'TESTE_' . ($contractTemplate->original_filename ?? 'modelo_de_contrato.docx');
        $tempPath = storage_path('app/temp/' . $tempFileName);
        
        if (!\Illuminate\Support\Facades\File::exists(storage_path('app/temp'))) {
            \Illuminate\Support\Facades\File::makeDirectory(storage_path('app/temp'), 0755, true);
        }
        
        $templateProcessor->saveAs($tempPath);

        return response()->download($tempPath)->deleteFileAfterSend(true);
    }

    public function downloadPdf(ContractTemplate $contractTemplate)
    {
        if (!$contractTemplate->file_path || !\Illuminate\Support\Facades\Storage::exists($contractTemplate->file_path)) {
            return abort(404, 'Arquivo Word não encontrado no servidor.');
        }

        $filePath = \Illuminate\Support\Facades\Storage::path($contractTemplate->file_path);
        
        try {
            $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($filePath);
        } catch (\Exception $e) {
            return abort(500, 'Erro ao processar o arquivo Word: ' . $e->getMessage());
        }

        $mockData = $this->getMockData();

        foreach ($mockData as $tag => $value) {
            $cleanTag = str_replace(['${', '}'], '', $tag);
            $textRun = new \PhpOffice\PhpWord\Element\TextRun();
            $textRun->addText($value, ['bgColor' => 'FFFF00']);
            $templateProcessor->setComplexValue($cleanTag, $textRun);
        }

        $tempFileName = 'TESTE_' . ($contractTemplate->original_filename ?? 'modelo_de_contrato.docx');
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

            '${CONTRATO_PLANO}' => 'Premium Plus',
            '${CONTRATO_CATEGORIA}' => 'Exclusive',
            '${CONTRATO_PACOTE}' => '7 Noites',
            '${CONTRATO_NUMERO}' => '2026/001',
            '${CONTRATO_PONTOS}' => '150.000 Pontos',
            '${CONTRATO_VALOR_TOTAL}' => 'R$ 45.000,00',
            '${CONTRATO_ENTRADA}' => 'R$ 5.000,00',
            '${CONTRATO_DATA_ENTRADA}' => '16/07/2026',
            '${CONTRATO_RESUMO_ENTRADA}' => "5x de R$ 1.000,00 no Cartão de Crédito (Início: 10/08/2026)\n + 5x de R$ 1.000,00 no Boleto (Início: 10/01/2027)",
            '${CONTRATO_SALDO}' => 'R$ 40.000,00',
            '${CONTRATO_DATA_SALDO}' => '16/08/2026',
            '${CONTRATO_RESUMO_SALDO}' => "20x de R$ 1.000,00 no Boleto Bancário (Início: 10/02/2027)\n + 15x de R$ 1.000,00 no PIX (Início: 10/10/2028)",
            '${CONTRATO_FORMA_PAGAMENTO_ENTRADA}' => 'PIX',
            '${CONTRATO_FORMA_PAGAMENTO_SALDO}' => 'Boleto Bancário',
            '${CONTRATO_FORMA_PAGAMENTO}' => 'Cartão de Crédito',
            '${CONTRATO_TAXA}' => 'R$ 250,00',
            '${CONTRATO_RESUMO_TAXA}' => "1x de R$ 125,00 no PIX (Início: 16/07/2026)\n + 1x de R$ 125,00 no Dinheiro (Início: 16/08/2026)",
            '${CONTRATO_TAXA_MANUTENCAO}' => 'R$ 1.200,00',
            '${CONTRATO_RESUMO_MANUTENCAO}' => "Anual de R$ 600,00 no Boleto Bancário\n + Anual de R$ 600,00 no Cartão de Crédito",
            '${CONTRATO_DATA}' => date('d/m/Y'),
            '${CONTRATO_DATA_EXTENSO}' => date('d') . ' de ' . \Carbon\Carbon::now()->locale('pt_BR')->translatedFormat('F') . ' de ' . date('Y'),
            '${CONTRATO_VIGENCIA}' => '5 (cinco) anos',

            '${EMPRESA_EMAIL}' => 'contato@itacare.com.br',
            '${EMPRESA_WHATSAPP}' => '(73) 9999-8888',
        ];
    }
}
