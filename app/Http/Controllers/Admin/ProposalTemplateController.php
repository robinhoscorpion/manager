<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProposalTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProposalTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Admin/ProposalTemplates/Index', [
            'items' => ProposalTemplate::orderBy('name')->get()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        ProposalTemplate::create($validated);

        return redirect()->back()->with('success', 'Modelo de proposta criado com sucesso!');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProposalTemplate $proposalTemplate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        $proposalTemplate->update($validated);

        return redirect()->back()->with('success', 'Modelo de proposta atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProposalTemplate $proposalTemplate)
    {
        $proposalTemplate->delete();

        return redirect()->back()->with('success', 'Modelo de proposta excluído com sucesso!');
    }

    /**
     * Upload image for the rich text editor.
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $path = $request->file('image')->store('proposal-templates', 'public');

        return response()->json([
            'url' => asset('storage/' . $path),
        ]);
    }
}
