<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ComplimentaryItem;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ComplimentaryItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Admin/ComplimentaryItems/Index', [
            'items' => ComplimentaryItem::orderBy('name')->get()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:complimentary_items,code',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'type' => 'required|in:atendimento,contrato',
            'is_active' => 'required|boolean',
            'template_type' => 'required|in:html,image,docx',
            'file' => 'nullable|file|mimes:jpeg,png,jpg,webp,docx|max:10240',
            'metadata' => 'nullable|array',
        ]);

        if ($request->hasFile('file')) {
            $validated['file_path'] = $request->file('file')->store('complimentary-templates', 'public');
        }

        ComplimentaryItem::create($validated);

        return redirect()->back()->with('success', 'Cortesia criada com sucesso!');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ComplimentaryItem $complimentaryItem)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:complimentary_items,code,' . $complimentaryItem->id,
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'type' => 'required|in:atendimento,contrato',
            'is_active' => 'required|boolean',
            'template_type' => 'required|in:html,image,docx',
            'file' => 'nullable|file|mimes:jpeg,png,jpg,webp,docx|max:10240',
            'metadata' => 'nullable|array',
        ]);

        if ($request->hasFile('file')) {
            $validated['file_path'] = $request->file('file')->store('complimentary-templates', 'public');
        }

        $complimentaryItem->update($validated);

        return redirect()->back()->with('success', 'Cortesia atualizada com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ComplimentaryItem $complimentaryItem)
    {
        // Verifica se a cortesia já está em uso em algum atendimento/contrato
        $isUsed = \App\Models\SalesService::where('cortesia', 'like', '%' . $complimentaryItem->code . '%')->exists();

        if ($isUsed) {
            $complimentaryItem->update(['is_active' => false]);
            return redirect()->back()->with('success', 'Cortesia em uso! Ela não pode ser excluída, mas foi inativada para manter o histórico.');
        }

        $complimentaryItem->delete();

        return redirect()->back()->with('success', 'Cortesia excluída com sucesso!');
    }

    /**
     * Upload image for the rich text editor.
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $path = $request->file('image')->store('complimentary-items', 'public');

        return response()->json([
            'url' => asset('storage/' . $path),
        ]);
    }

    /**
     * Display a native HTML print view of the complimentary item to be saved as PDF.
     */
    public function pdf(ComplimentaryItem $complimentaryItem)
    {
        // Para visualização individual (Admin), apenas mostramos o conteúdo original.
        $complimentaryItem->processed_content = $complimentaryItem->content;
        $items = collect([$complimentaryItem]);

        return view('pdf.complimentary-item', compact('items'));
    }
}
