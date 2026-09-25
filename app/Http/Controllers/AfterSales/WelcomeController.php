<?php

namespace App\Http\Controllers\AfterSales;

use App\Http\Controllers\Controller;
use App\Mail\SocioWelcomeMail;
use App\Models\SalesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;

class WelcomeController extends Controller
{
    public function index(Request $request)
    {
        $query = SalesService::with(['client', 'proposal.product'])
            ->where('status', SalesService::STATUS_APROVADO);

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
                $client = $sale->client;
                return [
                    'id' => $sale->id,
                    'client_id' => $client ? $client->id : null,
                    'name' => $client ? $client->nome : 'Desconhecido',
                    'cpf' => $client ? $client->cpf : '',
                    'phone' => $client ? preg_replace('/[^0-9]/', '', $client->celular1) : '',
                    'formatted_phone' => $client ? $client->celular1 : '',
                    'email' => $client ? $client->email : '',
                    'contract' => $sale->proposal ? $sale->proposal->contract_number : 'S/N',
                    'product' => ($sale->proposal && $sale->proposal->product) ? $sale->proposal->product->name : 'Atendimento #' . $sale->id,
                    'date' => $sale->date,
                    'status' => $sale->welcome_status ?? 'pending',
                    'has_password' => $client && !empty($client->password),
                    'password_set_at' => ($client && $client->password_set_at) ? $client->password_set_at->format('d/m/Y H:i') : null,
                ];
            });

        $metrics = [
            'new_clients' => $sales->count(),
            'pending' => $sales->where('status', 'pending')->count(),
            'sent' => $sales->whereIn('status', ['sent_whatsapp', 'sent_email'])->count(),
            'access_created' => $sales->where('has_password', true)->count(),
        ];

        $welcomeSetting = \App\Models\Setting::where('key', 'welcome_access_settings')->first();
        $welcomeSettings = array_merge(
            \App\Http\Controllers\Admin\WelcomeSettingsController::getDefaultSettings(),
            $welcomeSetting && is_array($welcomeSetting->value) ? $welcomeSetting->value : []
        );

        return Inertia::render('AfterSales/Welcome/Index', [
            'clients' => $sales->values(),
            'welcomeMetrics' => $metrics,
            'filters' => [
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ],
            'portalUrl' => config('app.url'),
            'welcomeSettings' => $welcomeSettings,
        ]);
    }

    public function updateStatus(Request $request, SalesService $salesService)
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

    public function sendWelcomeEmail(Request $request, SalesService $salesService)
    {
        $client = $salesService->client;
        if (!$client || empty($client->email)) {
            return back()->with('error', 'O cliente não possui e-mail cadastrado.');
        }

        $message = $request->input('message', '');
        $tempPassword = $request->input('temp_password', null);

        try {
            Mail::to($client->email)->send(new SocioWelcomeMail($client, config('app.url'), $message, $tempPassword));
        } catch (\Throwable $e) {
            // Caso as configurações de SMTP falhem, ainda assim atualizamos o registro e informamos o operador
            $salesService->update([
                'welcome_status' => 'sent_email',
                'welcome_sent_at' => now(),
                'welcome_sent_by' => auth()->id(),
            ]);

            return back()->with('warning', 'Status atualizado, mas o envio de e-mail falhou (verifique a configuração de SMTP do servidor). Erro: ' . $e->getMessage());
        }

        $salesService->update([
            'welcome_status' => 'sent_email',
            'welcome_sent_at' => now(),
            'welcome_sent_by' => auth()->id(),
        ]);

        return back()->with('success', 'E-mail de boas-vindas disparado com sucesso para ' . $client->email);
    }

    public function generateTempPassword(Request $request, SalesService $salesService)
    {
        $client = $salesService->client;
        if (!$client) {
            return back()->with('error', 'Cliente não encontrado para esta venda.');
        }

        $request->validate([
            'custom_password' => 'nullable|string|min:6',
        ]);

        $tempPassword = $request->filled('custom_password') ? $request->custom_password : Str::random(8);

        $client->update([
            'password' => Hash::make($tempPassword),
            'password_set_at' => now(),
        ]);

        return back()->with([
            'success' => 'Senha temporária gerada com sucesso!',
            'temp_password' => $tempPassword,
            'client_name' => $client->nome,
        ]);
    }
}

