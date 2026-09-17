<?php

namespace App\Http\Controllers\Api\Socio;

use App\Http\Controllers\Controller;
use App\Models\PointTable\Accommodation;
use App\Models\PointTable\Resort;
use App\Models\PointTable\Score;
use App\Models\PointTable\Season;
use App\Models\SalesService;
use App\Models\Client;
use Illuminate\Http\Request;

class SocioPointApiController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $client = $user instanceof Client ? $user : ($user ? $user->client : null);

        $service = $client ? SalesService::where('client_id', $client->id)->with('proposal')->first() : null;

        $totalPoints = $service && $service->proposal ? ($service->proposal->pontos_total ?? 100000) : 100000;
        $usedPoints = $service ? (int) \App\Models\ReservationRequest::where('sales_service_id', $service->id)->where('status', '!=', 'canceled')->sum('points_used') : 0;

        return response()->json([
            'contract' => [
                'proposal_number' => $service ? ($service->contrato_numero ?? 'CNT-' . $service->id) : 'CONTRATO-SOCIO-001',
                'total_points' => $totalPoints,
                'used_points' => $usedPoints,
                'available_points' => max(0, $totalPoints - $usedPoints),
            ],
            'scores' => Score::with(['resort', 'season', 'accommodation'])->limit(50)->get(),
        ]);
    }

    public function accommodations()
    {
        return response()->json([
            'resorts' => Resort::all(),
            'seasons' => Season::all(),
            'accommodations' => Accommodation::all(),
        ]);
    }
}