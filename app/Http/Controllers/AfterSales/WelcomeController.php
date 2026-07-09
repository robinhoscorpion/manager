<?php

namespace App\Http\Controllers\AfterSales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WelcomeController extends Controller
{
    public function index()
    {
        $sales = \App\Models\SalesService::with('client')
            ->where('status', \App\Models\SalesService::STATUS_APROVADO)
            ->orderBy('date', 'desc')
            ->orderBy('time', 'desc')
            ->take(100)
            ->get()
            ->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'name' => $sale->client ? $sale->client->nome : 'Desconhecido',
                    'phone' => $sale->client ? preg_replace('/[^0-9]/', '', $sale->client->celular1) : '',
                    'formatted_phone' => $sale->client ? $sale->client->celular1 : '',
                    'email' => $sale->client ? $sale->client->email : '',
                    'product' => 'Atendimento #'.$sale->id, // Mocked product for now as there's no direct product field in SalesService
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
