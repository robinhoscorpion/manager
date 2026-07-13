<?php

namespace App\Http\Controllers\AfterSales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\SalesService;
use Illuminate\Support\Facades\Storage;

class ContractDeliveryController extends Controller
{
    public function index(Request $request)
    {
        $query = SalesService::with(['client', 'proposal.product'])
            ->where('status', SalesService::STATUS_APROVADO);

        if ($request->filled('start_date')) {
            $query->whereRaw("STR_TO_DATE(date, '%d/%m/%Y') >= ?", [$request->start_date]);
        } else {
            // Mês atual por padrão se não houver filtro
            $query->whereRaw("STR_TO_DATE(date, '%d/%m/%Y') >= ?", [now()->startOfMonth()->toDateString()]);
        }

        if ($request->filled('end_date')) {
            $query->whereRaw("STR_TO_DATE(date, '%d/%m/%Y') <= ?", [$request->end_date]);
        } else {
            $query->whereRaw("STR_TO_DATE(date, '%d/%m/%Y') <= ?", [now()->endOfMonth()->toDateString()]);
        }

        $sales = $query->orderByRaw("STR_TO_DATE(date, '%d/%m/%Y') desc")
            ->orderBy('time', 'desc')
            ->take(500)
            ->get()
            ->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'name' => $sale->client ? $sale->client->nome : 'Desconhecido',
                    'phone' => $sale->client ? preg_replace('/[^0-9]/', '', $sale->client->celular1) : '',
                    'formatted_phone' => $sale->client ? $sale->client->celular1 : '',
                    'email' => $sale->client ? $sale->client->email : '',
                    'contract' => $sale->proposal ? $sale->proposal->contract_number : 'S/N',
                    'product' => ($sale->proposal && $sale->proposal->product) ? $sale->proposal->product->name : 'Atendimento #' . $sale->id,
                    'date' => $sale->date,
                    'status' => $sale->contract_delivery_status ?? 'pending',
                    'method' => $sale->contract_delivery_method,
                    'delivered_at' => $sale->contract_delivered_at ? \Carbon\Carbon::parse($sale->contract_delivered_at)->format('d/m/Y H:i') : null,
                    'signature_status' => $sale->contract_signature_status ?? 'pending',
                    'signed_at' => $sale->contract_signed_at ? \Carbon\Carbon::parse($sale->contract_signed_at)->format('d/m/Y H:i') : null,
                    'has_file' => !empty($sale->contract_file_path),
                ];
            });

        $metrics = [
            'total' => $sales->count(),
            'pending' => $sales->where('status', 'pending')->count(),
            'delivered' => $sales->where('status', 'delivered')->where('signature_status', 'pending')->count(),
            'signed' => $sales->where('signature_status', 'signed')->count(),
        ];

        return Inertia::render('AfterSales/ContractDelivery/Index', [
            'contracts' => $sales->values(),
            'metrics' => $metrics,
            'filters' => [
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]
        ]);
    }

    public function updateStatus(Request $request, SalesService $salesService)
    {
        $request->validate([
            'method' => 'required|in:whatsapp,email,physical'
        ]);

        $salesService->update([
            'contract_delivery_status' => 'delivered',
            'contract_delivery_method' => $request->method,
            'contract_delivered_at' => now(),
            'contract_delivered_by' => auth()->id(),
        ]);

        return back()->with('success', 'Entrega do contrato registrada com sucesso!');
    }

    public function uploadSignedContract(Request $request, SalesService $salesService)
    {
        $request->validate([
            'contract_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240' // max 10MB
        ]);

        if ($request->hasFile('contract_file')) {
            $file = $request->file('contract_file');
            // O método store() do Laravel já gera um hash aleatório automaticamente para o nome do arquivo
            $path = $file->store('contracts', 'local');

            $salesService->update([
                'contract_signature_status' => 'signed',
                'contract_signed_at' => now(),
                'contract_file_path' => $path,
            ]);

            return back()->with('success', 'Contrato anexado e marcado como assinado com sucesso!');
        }

        return back()->with('error', 'Falha ao enviar o arquivo.');
    }

    public function downloadContract(SalesService $salesService)
    {
        if (!$salesService->contract_file_path || !Storage::disk('local')->exists($salesService->contract_file_path)) {
            abort(404, 'Arquivo de contrato não encontrado.');
        }

        return Storage::disk('local')->download(
            $salesService->contract_file_path, 
            'Contrato_' . ($salesService->client ? mb_convert_case(str_replace(' ', '_', $salesService->client->nome), MB_CASE_TITLE) : $salesService->id) . '.' . pathinfo($salesService->contract_file_path, PATHINFO_EXTENSION)
        );
    }
}
