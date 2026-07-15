<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SalesService;
use App\Models\Protocol;

class ProtocolController extends Controller
{
    public function store(Request $request, SalesService $service)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'priority' => 'required|string|in:baixa,media,alta,urgente',
            'message' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240', // max 10MB
        ]);

        $attachmentPaths = [];

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('protocols', 'public');
                $attachmentPaths[] = $path;
            }
        }

        $protocol = $service->protocols()->create([
            'user_id' => auth()->id(),
            'subject' => $validated['subject'],
            'priority' => $validated['priority'],
            'message' => $validated['message'],
            'attachments' => $attachmentPaths,
        ]);

        return redirect()->back()->with([
            'success' => 'Protocolo gerado com sucesso!',
            'generated_protocol_number' => $protocol->protocol_number
        ]);
    }

    public function updateStatus(Request $request, Protocol $protocol)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:aberto,em_andamento,fechado',
        ]);

        $protocol->update([
            'status' => $validated['status'],
        ]);

        return redirect()->back()->with([
            'success' => 'Status do protocolo atualizado com sucesso!',
        ]);
    }

    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'protocol_ids' => 'required|array|min:1',
            'protocol_ids.*' => 'exists:protocols,id',
            'action' => 'required|string|in:status,priority',
            'value' => 'required|string',
        ]);

        if ($validated['action'] === 'status' && !in_array($validated['value'], ['aberto', 'em_andamento', 'fechado'])) {
            return back()->withErrors(['value' => 'Status inválido.']);
        }

        if ($validated['action'] === 'priority' && !in_array($validated['value'], ['baixa', 'media', 'alta', 'urgente'])) {
            return back()->withErrors(['value' => 'Prioridade inválida.']);
        }

        Protocol::whereIn('id', $validated['protocol_ids'])->update([
            $validated['action'] => $validated['value'],
        ]);

        return redirect()->back()->with([
            'success' => count($validated['protocol_ids']) . ' protocolos atualizados com sucesso!',
        ]);
    }
}
