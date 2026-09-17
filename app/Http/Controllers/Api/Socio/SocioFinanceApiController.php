<?php

namespace App\Http\Controllers\Api\Socio;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Client;
use Illuminate\Http\Request;

class SocioFinanceApiController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $client = $user instanceof Client ? $user : ($user ? $user->client : null);

        if (!$client) {
            return response()->json([
                'summary' => ['total' => 0, 'paid' => 0, 'pending' => 0, 'overdue' => 0],
                'bills' => []
            ]);
        }

        $bills = Bill::where('client_id', $client->id)
            ->orderBy('due_date', 'asc')
            ->get();

        $summary = [
            'total' => $bills->count(),
            'paid' => $bills->where('status', 'PAGO')->count(),
            'pending' => $bills->where('status', 'PENDENTE')->count(),
            'overdue' => $bills->filter(function($b) {
                return $b->status === 'PENDENTE' && $b->due_date < date('Y-m-d');
            })->count(),
        ];

        return response()->json([
            'summary' => $summary,
            'bills' => $bills,
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $client = $user instanceof Client ? $user : ($user ? $user->client : null);

        $bill = Bill::where('id', $id)
            ->where('client_id', $client ? $client->id : 0)
            ->firstOrFail();

        return response()->json(['bill' => $bill]);
    }
}