<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Protocol;

class ProtocolReplyController extends Controller
{
    public function store(Request $request, Protocol $protocol)
    {
        if ($protocol->status === 'fechado') {
            return redirect()->back()->withErrors(['error' => 'Não é possível responder a um protocolo fechado.']);
        }

        $validated = $request->validate([
            'message' => 'required|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240', // max 10MB
            'status' => 'nullable|string|in:aberto,em_andamento,fechado',
        ]);

        $attachmentPaths = [];

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('protocol_replies', 'public');
                $attachmentPaths[] = $path;
            }
        }

        $protocol->replies()->create([
            'user_id' => auth()->id(),
            'message' => $validated['message'],
            'attachments' => $attachmentPaths,
        ]);

        // Se o usuário solicitou uma mudança de status na mesma ação
        if (!empty($validated['status'])) {
            $protocol->update(['status' => $validated['status']]);
        }

        return redirect()->back()->with([
            'success' => 'Resposta enviada com sucesso!',
        ]);
    }
}
