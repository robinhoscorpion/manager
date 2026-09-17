<?php

namespace App\Http\Controllers\Api\Socio;

use App\Http\Controllers\Controller;
use App\Models\ReservationRequest;
use App\Models\SalesService;
use App\Models\Client;
use Illuminate\Http\Request;

class SocioReservationApiController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $client = $user instanceof Client ? $user : ($user ? $user->client : null);

        if (!$client) {
            return response()->json(['reservations' => []]);
        }

        $serviceIds = SalesService::where('client_id', $client->id)->pluck('id');

        $reservations = ReservationRequest::whereIn('sales_service_id', $serviceIds)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['reservations' => $reservations]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'destination' => 'required|string',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'adults' => 'required|integer|min:1',
            'children' => 'nullable|integer|min:0',
            'observations' => 'nullable|string',
        ]);

        $user = $request->user();
        $client = $user instanceof Client ? $user : ($user ? $user->client : null);
        $service = $client ? SalesService::where('client_id', $client->id)->first() : null;

        $reservation = ReservationRequest::create([
            'sales_service_id' => $service ? $service->id : null,
            'user_id' => $user instanceof Client ? null : $user->id,
            'destination' => $request->destination,
            'check_in' => $request->check_in,
            'check_out' => $request->check_out,
            'adults' => $request->adults,
            'children' => $request->children ?? 0,
            'status' => 'PENDENTE',
            'observations' => $request->observations,
        ]);

        return response()->json([
            'message' => 'Solicitação de reserva criada com sucesso!',
            'reservation' => $reservation,
        ], 201);
    }
}