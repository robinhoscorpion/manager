<?php

namespace App\Http\Controllers\Api\Socio;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Proposal;
use App\Models\Bill;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SocioAuthController extends Controller
{
    /**
     * Autentica o cliente (socio) utilizando E-mail ou CPF + Senha do Cliente.
     */
    public function login(Request $request)
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);

        $login    = trim($request->login);
        $cleanCpf = preg_replace('/\D/', '', $login);

        // CPF formatado para busca alternativa (ex: "123.456.789-00")
        $formattedCpf = strlen($cleanCpf) === 11
            ? substr($cleanCpf, 0, 3) . '.' . substr($cleanCpf, 3, 3) . '.' . substr($cleanCpf, 6, 3) . '-' . substr($cleanCpf, 9, 2)
            : null;

        // Busca por CPF (com ou sem formatacao) OU por e-mail
        $client = Client::where(function ($query) use ($login, $cleanCpf, $formattedCpf) {
            // Busca por e-mail
            if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
                $query->where('email', $login);
                return;
            }

            // Busca por CPF (aceita digitado sem formatacao ou com formatacao)
            if (strlen($cleanCpf) === 11) {
                $query->where('cpf', $cleanCpf);
                if ($formattedCpf) {
                    $query->orWhere('cpf', $formattedCpf);
                }
            } else {
                // Fallback: tenta como e-mail mesmo assim
                $query->where('email', $login);
            }
        })->first();

        if (!$client) {
            return response()->json([
                'message' => 'Cliente não encontrado com as credenciais informadas.',
                'errors'  => ['login' => ['Cliente não encontrado com as credenciais informadas.']],
            ], 422);
        }

        if (empty($client->password)) {
            return response()->json([
                'message' => 'Sua senha ainda não foi cadastrada. Utilize a opção "Primeiro Acesso" ou entre em contato com o Pós-Venda.',
                'errors'  => ['login' => ['Sua senha ainda não foi cadastrada. Utilize a opção "Primeiro Acesso" ou entre em contato com o Pós-Venda.']],
            ], 422);
        }

        if (!Hash::check($request->password, $client->password)) {
            return response()->json([
                'message' => 'Senha incorreta. Verifique os dados e tente novamente.',
                'errors'  => ['password' => ['Senha incorreta. Verifique os dados e tente novamente.']],
            ], 422);
        }

        // Cria um novo token Sanctum para o modelo Client
        $token = $client->createToken('portal_socio_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $this->formatUserData($client),
        ]);
    }

    /**
     * Retorna os dados do socio autenticado.
     */
    public function me(Request $request)
    {
        $client = $request->user();
        if (!$client || !($client instanceof Client)) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $client->load(['address', 'services']);

        return response()->json([
            'user' => $this->formatUserData($client),
        ]);
    }

    /**
     * Formata os dados completos do socio para a API.
     */
    private function formatUserData(Client $client): array
    {
        // Busca a proposta ativa (aprovada) ou mais recente do cliente
        $proposal = Proposal::where('client_id', $client->id)
            ->with(['product.productType', 'salesService', 'bills'])
            ->orderByRaw("CASE WHEN status = 'approved' THEN 1 WHEN status = 'pending' THEN 2 ELSE 3 END")
            ->latest('id')
            ->first();

        // Fallback: buscar via sales_services
        if (!$proposal) {
            $proposal = Proposal::whereHas('salesService', function ($q) use ($client) {
                $q->where('client_id', $client->id);
            })->with(['product.productType', 'salesService', 'bills'])
              ->orderByRaw("CASE WHEN status = 'approved' THEN 1 WHEN status = 'pending' THEN 2 ELSE 3 END")
              ->latest('id')
              ->first();
        }

        $product = $proposal?->product;
        $productType = $product?->productType?->name ?? 'Pontos';

        // Empreendimento / Resort
        $resortName = 'Itacaré Vacation Club';
        if ($product && !empty($product->name) && strtoupper($product->name) !== 'TESTE') {
            $resortName = $product->name;
        }

        // Numero do Contrato
        $contractNumber = $proposal?->contract_number;

        // Quantidade de Pontos e Regra Financeira de Liberação Proporcional (Idêntica ao Manager 2.0)
        $totalPoints = $proposal ? (int)($proposal->quantity ?? 0) : 0;
        $totalValue = $proposal ? (float)($proposal->total_value ?? 0) : 0;

        $bills = $proposal ? $proposal->bills : collect();
        $totalPaid = $bills->filter(function($b) {
            return in_array($b->category, ['entrada', 'saldo']) && $b->status === 'paid';
        })->reduce(function($acc, $b) {
            return $acc + (float) ($b->paid_amount ?: $b->amount);
        }, 0);

        $amountOpen = max(0, $totalValue - $totalPaid);
        $percentPaid = $totalValue > 0 ? ($totalPaid / $totalValue) : 0;
        $percentPending = max(0, 1 - $percentPaid);

        // Pontos liberados proporcionalmente ao valor pago (considerando entrada e saldo)
        $releasedPoints = ($totalValue > 0 && $totalPoints > 0)
            ? (int) floor($totalPoints * ($totalPaid / $totalValue))
            : $totalPoints;

        $serviceIds = \App\Models\SalesService::where('client_id', $client->id)->pluck('id');
        $pointsUsed = (int) \App\Models\ReservationRequest::whereIn('sales_service_id', $serviceIds)
            ->whereNotIn('status', ['canceled', 'cancelled', 'reproved', 'rejected'])
            ->sum('points_used');

        // Pontos disponíveis = Pontos Liberados - Pontos Usados
        $pointsAvailable = max(0, $releasedPoints - $pointsUsed);

        // Cota / Fracao
        if ($productType === 'Cotas') {
            $cota = 'Cota ' . ($contractNumber ?: '01');
        } elseif ($totalPoints > 0) {
            $cota = number_format($totalPoints, 0, ',', '.') . ' pts';
        } else {
            $cota = 'Membro VIP';
        }

        // Categoria
        $prodTitle = ($product && strtoupper($product->name) !== 'TESTE') ? $product->name : 'Platinum';
        $category = 'Sócio VIP ' . $prodTitle;

        // Semanas disponiveis estimadas
        $availableWeeks = $totalPoints > 0 ? max(1, (int)floor($totalPoints / 2500)) : 2;

        // Calculo da validade e vigencia real do contrato
        $startDate = $proposal ? ($proposal->salesService?->date ?? $proposal->created_at) : now();
        // A data do atendimento (sales_services.date) é texto e pode estar em "d/m/Y"
        try {
            $startCarbon = is_string($startDate) && str_contains($startDate, '/')
                ? Carbon::createFromFormat('d/m/Y', trim($startDate))->startOfDay()
                : Carbon::parse($startDate);
        } catch (\Throwable $e) {
            $startCarbon = $proposal?->created_at ? Carbon::parse($proposal->created_at) : now();
        }

        $durationYears = 10;
        if ($product && !empty($product->duration)) {
            $parsedDuration = (int) preg_replace('/\D/', '', $product->duration);
            if ($parsedDuration > 0) {
                $durationYears = $parsedDuration;
            }
        }

        $meses = [
            1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr', 5 => 'Mai', 6 => 'Jun',
            7 => 'Jul', 8 => 'Ago', 9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez'
        ];

        $validExact = (clone $startCarbon)->addYears($durationYears);
        $validExactFormatted = $validExact->format('d') . '/' . ($meses[$validExact->month] ?? $validExact->format('m')) . '/' . $validExact->year;
        $validEndOfYearFormatted = '31/Dez/' . $validExact->year;

        // Verifica se ha boletos/faturas vencidas (adimplencia)
        $hasOverdue = Bill::where('client_id', $client->id)
            ->where(function ($q) {
                $q->where('status', 'PENDENTE')
                  ->orWhere('status', 'pending');
            })
            ->where('due_date', '<', date('Y-m-d'))
            ->exists();

        return [
            'id'                      => $client->id,
            'name'                    => $client->nome,
            'email'                   => $client->email,
            'cpf'                     => $client->cpf,
            'contract_number'         => $contractNumber,
            'resort'                  => $resortName,
            'cota'                    => $cota,
            'category'                => $category,
            'points'                  => $pointsAvailable,
            'total_points'            => $totalPoints,
            'released_points'         => $releasedPoints,
            'points_used'             => $pointsUsed,
            'points_available'        => $pointsAvailable,
            'total_value'             => $totalValue,
            'total_paid'              => $totalPaid,
            'amount_open'             => $amountOpen,
            'percent_pending'         => round($percentPending * 100, 1),
            'percent_paid'            => round($percentPaid * 100, 1),
            'available_weeks'         => $availableWeeks,
            'status'                  => $proposal?->status ?? 'approved',
            'is_adimplente'           => !$hasOverdue,
            'contract_validity'       => $validEndOfYearFormatted,
            'contract_validity_exact' => $validExactFormatted,
            'contract_duration_years' => $durationYears,
            'contract_start_date'     => $startCarbon->format('d/m/Y'),
            'profile_photo_url'       => null,
            'proposal'                => $proposal ? [
                'id'              => $proposal->id,
                'contract_number' => $proposal->contract_number,
                'status'          => $proposal->status,
                'quantity'        => $proposal->quantity,
                'total_value'     => $proposal->total_value,
                'product_name'    => $product?->name,
                'product_type'    => $productType,
            ] : null,
            'client'                  => [
                'id'       => $client->id,
                'nome'     => $client->nome,
                'cpf'      => $client->cpf,
                'celular1' => $client->celular1,
                'email'    => $client->email,
            ],
        ];
    }

    /**
     * Endpoint de Primeiro Acesso para criacao de senha pelo cliente.
     */
    public function firstAccess(Request $request)
    {
        $request->validate([
            'cpf'                   => 'required|string',
            'password'              => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required|string|min:6',
        ]);

        $cleanCpf = preg_replace('/\D/', '', $request->cpf);

        if (strlen($cleanCpf) !== 11) {
            return response()->json([
                'message' => 'CPF inválido. Informe um CPF com 11 dígitos.',
                'errors'  => ['cpf' => ['CPF inválido. Informe um CPF com 11 dígitos.']],
            ], 422);
        }

        $client = Client::where(function ($q) use ($cleanCpf) {
            $q->where('cpf', $cleanCpf)
              ->orWhere('cpf', substr($cleanCpf, 0, 3) . '.' . substr($cleanCpf, 3, 3) . '.' . substr($cleanCpf, 6, 3) . '-' . substr($cleanCpf, 9, 2));
        })->first();

        if (!$client) {
            return response()->json([
                'message' => 'Nenhum cadastro de sócio encontrado com o CPF informado. Verifique o CPF e tente novamente.',
                'errors'  => ['cpf' => ['Nenhum cadastro de sócio encontrado com o CPF informado.']],
            ], 422);
        }

        $client->update([
            'password'        => Hash::make($request->password),
            'password_set_at' => now(),
        ]);

        try {
            \App\Models\AuditLog::create([
                'user_id'        => null,
                'event'          => 'first_access',
                'auditable_type' => Client::class,
                'auditable_id'   => $client->id,
                'description'    => "O sócio {$client->nome} (CPF: {$client->cpf}) realizou o Primeiro Acesso e cadastrou sua senha.",
                'ip_address'     => $request->ip(),
                'user_agent'     => $request->userAgent(),
            ]);
        } catch (\Throwable $e) {
            // Log de auditoria nao deve interromper a resposta da API
        }

        return response()->json([
            'message' => 'Senha cadastrada com sucesso! Agora você já pode fazer login no Portal do Sócio.',
        ]);
    }

    /**
     * Encerra a sessao atual.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sessão encerrada com sucesso.',
        ]);
    }
}