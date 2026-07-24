<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RciTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use setasign\Fpdi\Fpdi;
use Symfony\Component\Process\Process;

class RciController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Rci/Index', [
            'templates' => RciTemplate::orderBy('name')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf|max:10240', // 10MB
        ]);

        $isFirst = RciTemplate::count() === 0;
        $file = $request->file('file');
        
        $relativePath = $file->store('rci_templates');
        
        // Agora não usamos mais Ghostscript no backend, apenas salvamos o arquivo.
        // O preview será renderizado diretamente pelo navegador no frontend (via pdf.js).

        RciTemplate::create([
            'name' => $validated['name'],
            'file_path' => $relativePath,
            'is_default' => $isFirst,
            'preview_image_path' => null,
            'mapping_config' => [] // array vazio
        ]);

        return redirect()->back()->with('success', 'Modelo cadastrado com sucesso!');
    }

    public function setAsDefault(RciTemplate $template)
    {
        RciTemplate::query()->update(['is_default' => false]);
        $template->update(['is_default' => true]);

        return redirect()->back()->with('success', 'Modelo definido como padrão!');
    }

    public function updateMapping(Request $request, RciTemplate $template)
    {
        $validated = $request->validate([
            'mapping_config' => 'required|array'
        ]);

        $template->update(['mapping_config' => $validated['mapping_config']]);

        return redirect()->back()->with('success', 'Mapeamento salvo com sucesso!');
    }

    public function destroy(RciTemplate $template)
    {
        if ($template->file_path) {
            Storage::delete($template->file_path);
        }
        if ($template->preview_image_path) {
            // Remove storage/
            $rel = str_replace('storage/', '', $template->preview_image_path);
            Storage::disk('public')->delete($rel);
        }
        $template->delete();

        if ($template->is_default) {
            $newDefault = RciTemplate::latest()->first();
            if ($newDefault) {
                $newDefault->update(['is_default' => true]);
            }
        }

        return redirect()->back()->with('success', 'Modelo excluído com sucesso.');
    }

    public function preview(Request $request, RciTemplate $template)
    {
        $pdfPath = \Illuminate\Support\Facades\Storage::disk('local')->path($template->file_path);
        
        if (!file_exists($pdfPath)) {
            return abort(404, 'Arquivo base do modelo não encontrado no servidor.');
        }

        // Usa o mapeamento vindo do request, ou fallback para o do banco
        $mappingConfig = $request->input('mapping_config', $template->mapping_config ?? []);
        
        // Generate realistic fake data
        $fakeDataMapping = [
            'resort_nome' => 'Resort Paraíso Tropical',
            'resort_id' => 'RPT-998877',
            'data_atual' => date('d/m/Y'),
            'assoc1_nome_completo' => 'João da Silva Sauro',
            'assoc1_sobrenome' => 'da Silva Sauro',
            'assoc1_nomes' => 'João',
            'assoc1_rg_cpf' => '123.456.789-00',
            'assoc2_nome_completo' => 'Maria Oliveira Sauro',
            'assoc2_sobrenome' => 'Oliveira Sauro',
            'assoc2_nomes' => 'Maria',
            'assoc2_rg_cpf' => '987.654.321-11',
            'assoc1_data_nascimento' => '15/08/1985',
            'assoc2_data_nascimento' => '22/11/1988',
            'assoc1_nacionalidade' => 'Brasileira',
            'assoc2_nacionalidade' => 'Brasileira',
            'endereco_residencial' => 'Rua das Flores, 123, Apto 45',
            'endereco_pais' => 'Brasil',
            'endereco_cidade' => 'Belo Horizonte',
            'endereco_estado' => 'MG',
            'endereco_bairro' => 'Centro',
            'endereco_cep' => '01234-567',
            'telefone_1' => '(11) 98765-4321',
            'telefone_2' => '(11) 91234-5678',
            'telefone_3' => '(11) 3210-9876',
            'email_1' => 'joao.sauro@email.com',
            'email_2' => 'maria.oliveira@email.com',
        ];
        
        $pdfFieldsData = [];
        foreach ($mappingConfig as $mapping) {
            $pdfFieldName = $mapping['pdf_field'] ?? null;
            $systemVar = $mapping['system_var'] ?? null;
            
            if ($pdfFieldName && $systemVar) {
                if (str_starts_with($systemVar, 'CUSTOM:')) {
                    $pdfFieldsData[$pdfFieldName] = substr($systemVar, 7);
                } else {
                    $pdfFieldsData[$pdfFieldName] = $fakeDataMapping[$systemVar] ?? 'Dado Simulado';
                }
            }
        }

        try {
            $outputFilename = 'rci_preview_' . time() . '_' . rand(1000, 9999) . '.pdf';
            $outputPath = storage_path('app/public/temp/' . $outputFilename);
            $jsonFilename = 'rci_data_' . time() . '_' . rand(1000, 9999) . '.json';
            $jsonPath = storage_path('app/public/temp/' . $jsonFilename);
            
            if (!file_exists(storage_path('app/public/temp'))) {
                mkdir(storage_path('app/public/temp'), 0755, true);
            }
            
            file_put_contents($jsonPath, json_encode($pdfFieldsData));

            $scriptPath = base_path('fill_pdf_fields.py');
            $pythonPath = env('PYTHON_PATH', 'python');
            $process = new Process([$pythonPath, $scriptPath, $pdfPath, $outputPath, $jsonPath]);
            $process->run();
            
            @unlink($jsonPath);

            if (!$process->isSuccessful()) {
                throw new \Exception('Erro ao executar o preenchimento do PDF: ' . $process->getErrorOutput() . ' | STDOUT: ' . $process->getOutput());
            }

            if (file_exists($outputPath)) {
                return response()->file($outputPath)->deleteFileAfterSend(true);
            } else {
                throw new \Exception('O arquivo final não foi gerado.');
            }
        } catch (\Exception $e) {
            \Log::error("Erro na geração de RCI (Preview): " . $e->getMessage());
            return abort(500, "Erro ao gerar o PDF RCI: " . $e->getMessage());
        }
    }

    public function generate(Request $request)
    {
        $request->validate([
            'tags' => 'required|array',
        ]);
        
        $tagsData = $request->input('tags');

        if ($request->has('template_id')) {
            $template = RciTemplate::findOrFail($request->template_id);
        } else {
            $template = RciTemplate::where('is_default', true)->first();
            if (!$template) {
                return abort(404, 'Nenhum modelo RCI padrão foi configurado.');
            }
        }

        $pdfPath = \Illuminate\Support\Facades\Storage::disk('local')->path($template->file_path);
        
        if (!file_exists($pdfPath)) {
            return abort(404, 'Arquivo base do modelo não encontrado no servidor.');
        }

        $mappingConfig = $template->mapping_config ?? [];
        
        // Mapear os dados recebidos ($tagsData que possui as variáveis do sistema, ex: cliente_nome)
        // para os campos reais do PDF (AcroForm), baseando-se no mapeamento do banco de dados.
        $pdfFieldsData = [];
        foreach ($mappingConfig as $mapping) {
            $pdfFieldName = $mapping['pdf_field'] ?? null;
            $systemVar = $mapping['system_var'] ?? null;
            
            if ($pdfFieldName && $systemVar) {
                if (str_starts_with($systemVar, 'CUSTOM:')) {
                    $pdfFieldsData[$pdfFieldName] = substr($systemVar, 7);
                } elseif (isset($tagsData[$systemVar])) {
                    $pdfFieldsData[$pdfFieldName] = $tagsData[$systemVar];
                }
            }
        }

        try {
            $outputFilename = 'rci_generated_' . time() . '_' . rand(1000, 9999) . '.pdf';
            $outputPath = storage_path('app/public/temp/' . $outputFilename);
            $jsonFilename = 'rci_data_' . time() . '_' . rand(1000, 9999) . '.json';
            $jsonPath = storage_path('app/public/temp/' . $jsonFilename);
            
            if (!file_exists(storage_path('app/public/temp'))) {
                mkdir(storage_path('app/public/temp'), 0755, true);
            }
            
            file_put_contents($jsonPath, json_encode($pdfFieldsData));

            $scriptPath = base_path('fill_pdf_fields.py');
            $pythonPath = env('PYTHON_PATH', 'python');
            $process = new Process([$pythonPath, $scriptPath, $pdfPath, $outputPath, $jsonPath]);
            $process->run();
            
            @unlink($jsonPath);

            if (!$process->isSuccessful()) {
                throw new \Exception('Erro ao executar o preenchimento do PDF: ' . $process->getErrorOutput());
            }

            if (file_exists($outputPath)) {
                return response()->download($outputPath, 'RCI_Preenchido.pdf')->deleteFileAfterSend(true);
            } else {
                throw new \Exception('O arquivo final não foi gerado.');
            }
        } catch (\Exception $e) {
            \Log::error("Erro na geração de RCI: " . $e->getMessage());
            return abort(500, "Erro ao gerar o PDF RCI: " . $e->getMessage());
        }
    }
    public function getFile(RciTemplate $template)
    {
        $path = \Illuminate\Support\Facades\Storage::disk('local')->path($template->file_path);
        if (!file_exists($path)) {
            return response()->json(['error' => 'File not found at: ' . $path], 404);
        }
        return response()->file($path);
    }

    public function getFields(RciTemplate $template)
    {
        $path = \Illuminate\Support\Facades\Storage::disk('local')->path($template->file_path);
        
        if (!file_exists($path)) {
            return response()->json(['error' => 'Arquivo PDF não encontrado no servidor.'], 404);
        }

        $scriptPath = base_path('extract_pdf_fields.cjs');
        
        $nodePath = env('NODE_PATH', 'node');
        $process = new Process([$nodePath, $scriptPath, $path]);
        $process->run();

        \Log::info('getFields Process Output: ' . $process->getOutput());
        \Log::info('getFields Process Error: ' . $process->getErrorOutput());

        if (!$process->isSuccessful()) {
            return response()->json([
                'error' => 'Erro ao extrair campos do PDF.',
                'details' => $process->getErrorOutput()
            ], 500);
        }

        $output = json_decode($process->getOutput(), true);
        
        if (isset($output['error'])) {
            return response()->json(['error' => $output['error']], 500);
        }

        return response()->json(['fields' => $output['fields'] ?? []]);
    }
}
