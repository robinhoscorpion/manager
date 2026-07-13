<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Proposal;
use App\Models\ProposalPayment;
use Illuminate\Support\Facades\DB;

class SalesControlController extends Controller
{
    public function index(Request $request)
    {
        // Paginating proposals with relationships
        $query = Proposal::with([
            'client', 
            'product',
            'payments' => function ($q) {
                $q->orderBy('start_date', 'asc');
            },
            'salesService.opcUser',
            'salesService.linerUser',
            'salesService.closerUser'
        ]);

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('contract_number', 'like', "%{$search}%")
                  ->orWhereHas('client', function($cq) use ($search) {
                      $cq->where('nome', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('only_discrepancies') && $request->only_discrepancies === 'true') {
            $query->whereRaw('
                CASE 
                    WHEN (COALESCE(received_down_payment, 0) + COALESCE(received_taxes, 0) + COALESCE(received_balance, 0)) = 0 
                    THEN ABS(COALESCE(gross_value, total_value, 0) - (COALESCE(base_value, 0) + COALESCE(taxes, 0))) > 1
                    ELSE ABS(COALESCE(gross_value, total_value, 0) - (COALESCE(received_down_payment, 0) + COALESCE(received_taxes, 0) + COALESCE(received_balance, 0))) > 1
                END
            ');
        }

        $proposals = $query->orderBy('created_at', 'desc')->paginate(15);

        // Aggregating metrics
        // Here we consider "approved" or "active" statuses as valid for the Net Total, adjust as needed.
        $totalLiquido = Proposal::whereNotIn('status', ['Cancelado', 'cancelado'])->sum('total_value');
        $totalBase = Proposal::whereNotIn('status', ['Cancelado', 'cancelado'])->sum('base_value');
        $taxas = Proposal::whereNotIn('status', ['Cancelado', 'cancelado'])->sum('taxes');
        
        $totalPendente = Proposal::whereIn('status', ['Pendente', 'pendente', 'Aguardando Pagamento'])->sum('total_value');
        $totalCancelado = Proposal::whereIn('status', ['Cancelado', 'cancelado'])->sum('total_value');
        
        $entradas = ProposalPayment::where('category', 'entrada')->sum('total_value');

        return Inertia::render('Finance/SalesControl/Index', [
            'proposals' => $proposals,
            'filters' => $request->only(['search', 'only_discrepancies']),
            'metrics' => [
                'total_liquido' => $totalLiquido,
                'total_base' => $totalBase,
                'entradas' => $entradas,
                'taxas' => $taxas,
                'total_pendente' => $totalPendente,
                'total_cancelado' => $totalCancelado
            ]
        ]);
    }

    public function auditProposal(Request $request, Proposal $proposal)
    {
        $request->validate([
            'audit_status' => 'required|in:pending,approved,rejected',
            'audit_reason' => 'nullable|string'
        ]);

        $proposal->audit_status = $request->audit_status;
        if ($request->has('audit_reason')) {
            $proposal->audit_reason = $request->audit_reason;
        }
        $proposal->save();

        return response()->json([
            'success' => true, 
            'audit_status' => $proposal->audit_status,
            'audit_reason' => $proposal->audit_reason
        ]);
    }

    public function saveConciliation(Request $request, Proposal $proposal)
    {
        $request->validate([
            'received_down_payment' => 'nullable|numeric|min:0',
            'received_taxes' => 'nullable|numeric|min:0',
            'received_balance' => 'nullable|numeric|min:0',
        ]);

        $proposal->update($request->only([
            'received_down_payment',
            'received_taxes',
            'received_balance'
        ]));

        return response()->json(['success' => true]);
    }
}
