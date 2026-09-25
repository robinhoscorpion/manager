<?php

namespace App\Http\Controllers\Api\Socio;

use App\Http\Controllers\Controller;
use App\Models\PointTable\Accommodation;
use App\Models\PointTable\Resort;
use App\Models\PointTable\Score;
use App\Models\PointTable\Season;
use App\Models\SalesService;
use App\Models\Proposal;
use App\Models\ReservationRequest;
use App\Models\Client;
use Illuminate\Http\Request;

class SocioPointApiController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $client = $user instanceof Client ? $user : ($user ? $user->client : null);

        if (!$client) {
            return response()->json([
                'contract' => [
                    'proposal_number'  => 'Sem Contrato',
                    'total_points'     => 0,
                    'used_points'      => 0,
                    'available_points' => 0,
                ],
                'scores' => [],
            ]);
        }

        $proposal = Proposal::where('client_id', $client->id)
            ->with(['product.productType', 'salesService'])
            ->orderByRaw("CASE WHEN status = 'approved' THEN 1 WHEN status = 'pending' THEN 2 ELSE 3 END")
            ->latest('id')
            ->first();

        $totalPoints = $proposal ? (int)($proposal->quantity ?? 0) : 0;
        
        $serviceIds = SalesService::where('client_id', $client->id)->pluck('id');
        $usedPoints = (int) ReservationRequest::whereIn('sales_service_id', $serviceIds)
            ->whereNotIn('status', ['canceled', 'cancelled', 'reproved', 'rejected'])
            ->sum('points_used');

        $availablePoints = max(0, $totalPoints - $usedPoints);

        return response()->json([
            'contract' => [
                'proposal_number'  => $proposal ? $proposal->contract_number : 'Sem Contrato',
                'total_points'     => $totalPoints,
                'used_points'      => $usedPoints,
                'available_points' => $availablePoints,
            ],
            'scores' => Score::with(['accommodation.resort', 'season'])->limit(50)->get(),
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