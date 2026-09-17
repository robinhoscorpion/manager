<?php

namespace App\Http\Controllers\Api\Socio;

use App\Http\Controllers\Controller;
use App\Models\Protocol;
use App\Models\ProtocolReply;
use App\Models\ProtocolSubject;
use App\Models\SalesService;
use App\Models\Client;
use Illuminate\Http\Request;

class SocioProtocolApiController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $client = $user instanceof Client ? $user : ($user ? $user->client : null);

        $serviceIds = $client ? SalesService::where('client_id', $client->id)->pluck('id') : collect([]);

        $protocols = Protocol::where(function ($q) use ($serviceIds, $user) {
            if ($serviceIds->isNotEmpty()) {
                $q->whereIn('sales_service_id', $serviceIds);
            }
            if ($user && !($user instanceof Client)) {
                $q->orWhere('user_id', $user->id);
            }
        })
        ->with(['replies.user'])
        ->orderBy('updated_at', 'desc')
        ->get();

        $subjects = ProtocolSubject::all();

        return response()->json([
            'protocols' => $protocols,
            'subjects' => $subjects,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string',
            'message' => 'required|string',
            'priority' => 'nullable|string',
        ]);

        $user = $request->user();
        $client = $user instanceof Client ? $user : ($user ? $user->client : null);
        $service = $client ? SalesService::where('client_id', $client->id)->first() : null;

        $protocol = Protocol::create([
            'sales_service_id' => $service ? $service->id : null,
            'user_id' => $user instanceof Client ? null : $user->id,
            'subject' => $request->subject,
            'message' => $request->message,
            'priority' => $request->priority ?? 'MEDIA',
            'status' => 'ABERTO',
        ]);

        return response()->json([
            'message' => 'Protocolo de atendimento criado com sucesso!',
            'protocol' => $protocol,
        ], 201);
    }

    public function reply(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $user = $request->user();
        $protocol = Protocol::findOrFail($id);

        $reply = ProtocolReply::create([
            'protocol_id' => $protocol->id,
            'user_id' => $user instanceof Client ? null : $user->id,
            'message' => $request->message,
        ]);

        $protocol->touch();

        return response()->json([
            'message' => 'Resposta enviada com sucesso.',
            'reply' => $reply,
        ]);
    }
}