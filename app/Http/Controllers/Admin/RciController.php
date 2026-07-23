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

    public function generate(Request $request)
    {
        // Aceita as tags que o front enviar
        $request->validate([
            'tags' => 'required|array', // ex: ['${CLIENTE_NOME}' => 'Joao']
        ]);
        
        $tagsData = $request->input('tags');

        // Se for um teste do mapeador, ele pode mandar um template_id
        if ($request->has('template_id')) {
            $template = RciTemplate::findOrFail($request->template_id);
        } else {
            $template = RciTemplate::where('is_default', true)->first();
            if (!$template) {
                return abort(404, 'Nenhum modelo RCI padrão foi configurado.');
            }
        }

        $pdfPath = storage_path('app/' . $template->file_path);
        
        if (!file_exists($pdfPath)) {
            return abort(404, 'Arquivo base do modelo não encontrado no servidor.');
        }

        $mappingConfig = $template->mapping_config ?? [];

        try {
            $pdf = new Fpdi();
            $pageCount = $pdf->setSourceFile($pdfPath);
            
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);

                // Aplica as marcações dinâmicas para esta página
                foreach ($mappingConfig as $marker) {
                    $mPage = $marker['page'] ?? 1;
                    if ($mPage == $pageNo) {
                        $tag = $marker['tag'];
                        if (isset($tagsData[$tag])) {
                            $fontSize = $marker['fontSize'] ?? 9;
                            $fontStyle = $marker['fontStyle'] ?? ''; // B, I, U
                            $pdf->SetFont('Arial', $fontStyle, $fontSize);
                            
                            $pdf->SetXY($marker['x'], $marker['y']);
                            // uppercase se configurado? Por padrão vamos deixar assim.
                            // Mas na tela o usuario pode configurar uppercase
                            $text = $tagsData[$tag];
                            if (!empty($marker['uppercase'])) {
                                $text = strtoupper($text);
                            }
                            $pdf->Write(0, utf8_decode($text));
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            return abort(500, 'Erro ao processar o PDF: ' . $e->getMessage());
        }

        $fileName = 'RCI_' . time() . '.pdf';
        
        return response($pdf->Output('I'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"'
        ]);
    }

    public function getFile(RciTemplate $template)
    {
        $path = \Illuminate\Support\Facades\Storage::disk('local')->path($template->file_path);
        if (!file_exists($path)) {
            abort(404);
        }
        return response()->file($path);
    }
}
