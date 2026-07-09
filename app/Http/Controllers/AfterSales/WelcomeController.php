<?php

namespace App\Http\Controllers\AfterSales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WelcomeController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\SalesService::with(['client', 'proposal.product'])
            ->where('status', \App\Models\SalesService::STATUS_APROVADO);

        if ($request->filled('start_date')) {
            $query->whereRaw("STR_TO_DATE(date, '%d/%m/%Y') >= ?", [$request->start_date]);
        } else {
            $query->whereRaw("STR_TO_DATE(date, '%d/%m/%Y') >= ?", [now()->toDateString()]);
        }

        if ($request->filled('end_date')) {
            $query->whereRaw("STR_TO_DATE(date, '%d/%m/%Y') <= ?", [$request->end_date]);
        } else {
            $query->whereRaw("STR_TO_DATE(date, '%d/%m/%Y') <= ?", [now()->toDateString()]);
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
                    'status' => $sale->welcome_status ?? 'pending',
                ];
            });

        $metrics = [
            'new_clients' => $sales->count(),
            'pending' => $sales->where('status', 'pending')->count(),
            'sent' => $sales->whereIn('status', ['sent_whatsapp', 'sent_email'])->count(),
        ];

        return Inertia::render('AfterSales/Welcome/Index', [
            'clients' => $sales->values(),
            'welcomeMetrics' => $metrics,
            'filters' => [
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ]
        ]);
    }

    public function updateStatus(Request $request, \App\Models\SalesService $salesService)
    {
        $request->validate([
            'status' => 'required|in:sent_whatsapp,sent_email'
        ]);

        $salesService->update([
            'welcome_status' => $request->status,
            'welcome_sent_at' => now(),
            'welcome_sent_by' => auth()->id(),
        ]);

        return back()->with('success', 'Status de boas-vindas atualizado!');
    }
}
