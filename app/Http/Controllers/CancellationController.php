<?php

namespace App\Http\Controllers;

use App\Models\Cancellation;
use App\Models\Proposal;
use App\Models\Bill;
use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class CancellationController extends Controller
{
    /**
     * Display a listing of cancellations.
     */
    public function index(Request $request)
    {
        $cancellations = Cancellation::with(['proposal.client', 'user'])
            ->latest()
            ->paginate(10);

        return Inertia::render('Admin/Cancellations/Index', [
            'cancellations' => $cancellations
        ]);
    }

    /**
     * Show the form for creating a new cancellation.
     */
    public function create()
    {
        return Inertia::render('Admin/Cancellations/Create');
    }

    /**
     * API to search for a contract/proposal by contract_number or CPF.
     */
    public function apiSearch(Request $request)
    {
        $term = $request->query('term');
        
        if (empty($term)) {
            return response()->json(['error' => 'Termo de busca obrigatório.'], 400);
        }

        $term = trim($term);

        // Busca por CPF no Client ou Número de Contrato na Proposta
        $proposals = Proposal::with(['client', 'product'])
            ->where(function($query) use ($term) {
                $query->where('contract_number', 'like', "%{$term}%")
                      ->orWhereHas('client', function($q) use ($term) {
                          $cleanTerm = preg_replace('/[^0-9]/', '', $term);
                          
                          $q->where('nome', 'like', "%{$term}%")
                            ->orWhere('cpf', 'like', "%{$term}%");
                          
                          // Busca inteligente de CPF (compara os números digitados ignorando pontuação no banco)
                          if (strlen($cleanTerm) > 0) {
                              $q->orWhere(\Illuminate\Support\Facades\DB::raw("REPLACE(REPLACE(cpf, '.', ''), '-', '')"), 'like', "%{$cleanTerm}%");
                          }
                      });
            })
            ->where('status', '!=', 'cancelled') // Não pode cancelar o que já tá cancelado
            ->limit(10)
            ->get();

        if ($proposals->isEmpty()) {
            return response()->json(['error' => 'Nenhum contrato encontrado ou já cancelado.'], 404);
        }

        $results = $proposals->map(function ($proposal) {
            // Calcula os valores financeiros
            $totalPaid = Bill::where('proposal_id', $proposal->id)
                ->whereIn('status', ['paid', 'partially_paid'])
                ->sum('paid_amount');
                
            $totalPending = Bill::where('proposal_id', $proposal->id)
                ->whereIn('status', ['pending', 'overdue'])
                ->sum('amount');
                
            return [
                'proposal' => $proposal,
                'financial' => [
                    'total_paid' => $totalPaid,
                    'total_pending' => $totalPending
                ]
            ];
        });

        return response()->json($results);
    }

    /**
     * Store the cancellation and update proposal/bills.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'proposal_id' => 'required|exists:proposals,id',
            'reason' => 'required|string|max:255',
            'details' => 'nullable|string',
            'fine_amount' => 'required|numeric|min:0',
            'refund_amount' => 'required|numeric|min:0',
            'cancel_all_bills' => 'nullable|boolean',
        ]);

        $proposal = Proposal::findOrFail($validated['proposal_id']);

        if ($proposal->status === 'cancelled') {
            return redirect()->back()->withErrors(['proposal_id' => 'Este contrato já está cancelado.']);
        }

        DB::transaction(function () use ($validated, $proposal, $request) {
            // 1. Calcula o total pago real direto do banco por segurança
            $totalPaid = Bill::where('proposal_id', $proposal->id)
                ->whereIn('status', ['paid', 'partially_paid'])
                ->sum('paid_amount');

            // 2. Cria o registro de cancelamento
            Cancellation::create([
                'proposal_id' => $proposal->id,
                'user_id' => $request->user()->id,
                'reason' => $validated['reason'],
                'details' => $validated['details'],
                'total_paid' => $totalPaid,
                'fine_amount' => $validated['fine_amount'],
                'refund_amount' => $validated['refund_amount'],
                'status' => 'completed'
            ]);

            // 3. Atualiza o status da Proposta
            $proposal->update(['status' => 'cancelled']);
            
            // 4. Inativa parcelas
            $query = Bill::where('proposal_id', $proposal->id);
            
            if (empty($validated['cancel_all_bills'])) {
                $query->whereIn('status', ['pending', 'overdue']);
            }
            
            $billsToCancel = $query->get();
            $cancelNote = "Cancelado devido ao cancelamento do contrato por " . $request->user()->name;
            
            foreach ($billsToCancel as $bill) {
                $bill->status = 'cancelled';
                if (empty($bill->observations)) {
                    $bill->observations = $cancelNote;
                } else {
                    $bill->observations .= " | " . $cancelNote;
                }
                $bill->save();
            }
        });

        return redirect()->route('after-sales.cancellations.index')->with('success', 'Distrato realizado com sucesso!');
    }

    /**
     * Generate the PDF/Printable view for a cancellation.
     */
    public function pdf(Cancellation $cancellation)
    {
        $cancellation->load(['proposal.client', 'proposal.product']);

        return view('pdf.cancellation', [
            'cancellation' => $cancellation
        ]);
    }
}
