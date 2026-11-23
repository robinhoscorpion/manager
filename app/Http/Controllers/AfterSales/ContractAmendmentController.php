<?php

namespace App\Http\Controllers\AfterSales;

use App\Http\Controllers\Controller;
use App\Models\CommissionReversal;
use App\Models\ContractAmendment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Proposal;
use App\Services\Contracts\ContractAmendmentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Pós-venda > Upgrade / Downgrade de contrato (aditivo).
 * Mantém o número do contrato e o espelho de cada versão anterior.
 */
class ContractAmendmentController extends Controller
{
    public function __construct(private ContractAmendmentService $service)
    {
    }

    public function index(Request $request)
    {
        $amendments = ContractAmendment::with(['proposal.client', 'fromProduct', 'toProduct', 'user'])
            ->when($request->query('type'), fn ($q, $type) => $q->where('type', $type))
            ->when($request->query('search'), function ($q, $term) {
                $q->whereHas('proposal', function ($p) use ($term) {
                    $p->where('contract_number', 'like', "%{$term}%")
                        ->orWhereHas('client', fn ($c) => $c->where('nome', 'like', "%{$term}%")->orWhere('cpf', 'like', "%{$term}%"));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('AfterSales/ContractAmendments/Index', [
            'amendments' => $amendments,
            'filters' => $request->only(['type', 'search']),
        ]);
    }

    public function create(Request $request)
    {
        return Inertia::render('AfterSales/ContractAmendments/Create', [
            'products' => Product::where('is_active', true)->orderBy('name')
                ->get(['id', 'name', 'price', 'min_price', 'quantity', 'contract_fee', 'category', 'package']),
            'paymentMethods' => PaymentMethod::orderBy('name')->pluck('name'),
            'initialProposalId' => $request->query('proposal'),
        ]);
    }

    /** Busca de contrato por número, nome ou CPF (somente aprovados). */
    public function apiSearch(Request $request)
    {
        $term = trim((string) $request->query('term'));
        $id = $request->query('id');

        if ($term === '' && !$id) {
            return response()->json(['error' => 'Termo de busca obrigatório.'], 400);
        }

        $proposals = Proposal::with(['client:id,nome,cpf', 'product:id,name', 'payments'])
            ->where('status', 'approved')
            ->when($id, fn ($q) => $q->whereKey($id))
            ->when(!$id, function ($query) use ($term) {
                $query->where(function ($query) use ($term) {
                    $clean = preg_replace('/[^0-9]/', '', $term);
                    $query->where('contract_number', 'like', "%{$term}%")
                        ->orWhereHas('client', function ($q) use ($term, $clean) {
                            $q->where('nome', 'like', "%{$term}%")->orWhere('cpf', 'like', "%{$term}%");
                            if (strlen($clean) > 0) {
                                $q->orWhereRaw("REPLACE(REPLACE(cpf, '.', ''), '-', '') like ?", ["%{$clean}%"]);
                            }
                        });
                });
            })
            ->limit(10)
            ->get();

        if ($proposals->isEmpty()) {
            return response()->json(['error' => 'Nenhum contrato aprovado encontrado.'], 404);
        }

        return response()->json($proposals->map(fn ($p) => [
            'proposal' => $p,
            'financial' => $this->service->financialSummary($p),
        ]));
    }

    /** Pré-visualização do cálculo (crédito, sobra e restante a negociar). */
    public function preview(Request $request, Proposal $proposal)
    {
        $validated = $request->validate(['total_value' => 'required|numeric|min:0']);

        return response()->json($this->service->preview($proposal, (float) $validated['total_value']));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'proposal_id' => 'required|exists:proposals,id',
            'type' => ['required', Rule::in([ContractAmendment::TYPE_UPGRADE, ContractAmendment::TYPE_DOWNGRADE])],
            'product_id' => 'required|exists:products,id',
            'total_value' => 'required|numeric|min:0',
            'quantity' => 'nullable|integer|min:0',
            'reason' => 'required|string|max:255',
            'details' => 'nullable|string',
            'charge_contract_fee' => 'boolean',
            'commission_reversal_responsible' => ['nullable', Rule::in([
                ContractAmendment::RESPONSIBLE_EMPLOYEE,
                ContractAmendment::RESPONSIBLE_CLIENT,
                ContractAmendment::RESPONSIBLE_UNDEFINED,
            ])],
            'payments' => 'array',
            'payments.*.category' => ['required', Rule::in(['entrada', 'saldo', 'taxa_contrato'])],
            'payments.*.payment_method' => 'required|string',
            'payments.*.installments' => 'required|integer|min:1',
            'payments.*.installment_value' => 'required|numeric|min:0',
            'payments.*.total_value' => 'required|numeric|min:0',
            'payments.*.start_date' => 'required|date',
        ]);

        $proposal = Proposal::findOrFail($validated['proposal_id']);
        $amendment = $this->service->apply($proposal, $validated, $request->user());

        return redirect()
            ->route('after-sales.contract-amendments.show', $amendment)
            ->with('success', "{$amendment->type_label} do contrato #{$proposal->contract_number} registrado (versão {$amendment->to_version}).");
    }

    /** Espelho do contrato: versão anterior x nova versão. */
    public function show(ContractAmendment $amendment)
    {
        $amendment->load([
            'proposal.client:id,nome,cpf',
            'proposal.product:id,name',
            'fromProduct:id,name',
            'toProduct:id,name',
            'user:id,name',
            'payments',
            'bills',
            'replacedBills',
            'commissions.user:id,name',
            'commissionReversals.user:id,name',
        ]);

        $history = ContractAmendment::with(['fromProduct:id,name', 'toProduct:id,name', 'user:id,name'])
            ->where('proposal_id', $amendment->proposal_id)
            ->orderBy('to_version')
            ->get();

        return Inertia::render('AfterSales/ContractAmendments/Show', [
            'amendment' => $amendment,
            'history' => $history,
        ]);
    }

    /** Atualiza o responsável / situação de um estorno de comissão. */
    public function updateReversal(Request $request, CommissionReversal $reversal)
    {
        $validated = $request->validate([
            'responsible' => ['required', Rule::in([
                ContractAmendment::RESPONSIBLE_EMPLOYEE,
                ContractAmendment::RESPONSIBLE_CLIENT,
                ContractAmendment::RESPONSIBLE_UNDEFINED,
            ])],
            'status' => ['required', Rule::in([
                CommissionReversal::STATUS_PENDING,
                CommissionReversal::STATUS_RESOLVED,
                CommissionReversal::STATUS_WAIVED,
            ])],
            'notes' => 'nullable|string',
        ]);

        $resolved = $validated['status'] !== CommissionReversal::STATUS_PENDING;
        $reversal->update(array_merge($validated, [
            'resolved_by' => $resolved ? $request->user()->id : null,
            'resolved_at' => $resolved ? now() : null,
        ]));

        return back()->with('success', 'Estorno de comissão atualizado.');
    }
}
