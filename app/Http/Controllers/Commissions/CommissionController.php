<?php

namespace App\Http\Controllers\Commissions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Proposal;

class CommissionController extends Controller
{
    public function index(Request $request)
    {
        $query = Proposal::with([
            'client',
            'product',
            'salesService.opcUser',
            'salesService.linerUser',
            'salesService.closerUser',
            'commissions.user'
        ])
        ->whereHas('commissions');

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        if ($startDate && $endDate) {
            $start = \Carbon\Carbon::parse($startDate)->startOfDay();
            $end = \Carbon\Carbon::parse($endDate)->endOfDay();
            $query->whereBetween('created_at', [$start, $end]);
        }

        // Metrics Calculation
        $baseQuery = clone $query;
        $totalVGV = $baseQuery->sum('total_value');
        $totalContracts = $baseQuery->count();
        
        // Sum all commissions total_amount across all filtered proposals
        $totalCommissions = $baseQuery->with('commissions')->get()->sum(function($proposal) {
            return $proposal->commissions->sum('total_amount');
        });

        $proposals = $query->orderBy('created_at', 'desc')->paginate(15);

        return Inertia::render('Commissions/Index', [
            'proposals' => $proposals,
            'metrics' => [
                'total_vgv' => $totalVGV,
                'total_contracts' => $totalContracts,
                'total_commissions' => $totalCommissions,
            ],
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]
        ]);
    }
}
