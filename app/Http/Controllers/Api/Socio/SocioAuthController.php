<?php

namespace App\Http\Controllers\Api\Socio;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SocioAuthController extends Controller
{
    /**
     * Autentica o cliente (sócio) utilizando E-mail ou CPF + Senha do Cliente.
     */
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $login = trim($request->login);
        $cleanCpf = preg_replace('/\D/', '', $login);

        // Busca o cliente diretamente na tabela clients por CPF ou E-mail
        $client = Client::where(function ($query) use ($login, $cleanCpf) {
            if (!empty($cleanCpf)) {
                $query->where('cpf', $cleanCpf);
            }
            if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
                $query->orWhere('email', $login);
            } else {
                $query->orWhere('email', $login);
            }
        })->first();

        if (!$client) {
            throw ValidationException::withMessages([
                'login' => ['Cliente não encontrado com as credenciais informadas.'],
            ]);
        }

        if (empty($client->password)) {
            throw ValidationException::withMessages([
                'login' => ['Sua senha ainda não foi cadastrada. Utilize a opção de Primeiro Acesso ou entre em contato com o Pós-Venda.'],
            ]);
        }

        if (!Hash::check($request->password, $client->password)) {
            throw ValidationException::withMessages([
                'login' => ['As credenciais informadas estão incorretas.'],
            ]);
        }

        // Cria um novo token Sanctum para o modelo Client
        $token = $client->createToken('portal_socio_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $client->id,
                'name' => $client->nome,
                'email' => $client->email,
                'cpf' => $client->cpf,
                'profile_photo_url' => null,
                'client' => [
                    'id' => $client->id,
                    'nome' => $client->nome,
                    'cpf' => $client->cpf,
                    'celular1' => $client->celular1,
                    'email' => $client->email,
                ],
            ],
        ]);
    }

    /**
     * Retorna os dados do sócio autenticado.
     */
    public function me(Request $request)
    {
        $client = $request->user();
        if ($client instanceof Client) {
            $client->load(['address', 'services']);
        }

        return response()->json([
            'user' => [
                'id' => $client->id,
                'name' => $client->nome,
                'email' => $client->email,
                'cpf' => $client->cpf,
                'client' => $client,
            ],
        ]);
    }

    /**
     * Endpoint de Primeiro Acesso para criação de senha pelo cliente.
     */
    public function firstAccess(Request $request)
    {
        $request->validate([
            'cpf' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $cleanCpf = preg_replace('/\D/', '', $request->cpf);

        $client = Client::where('cpf', $cleanCpf)->first();

        if (!$client) {
            throw ValidationException::withMessages([
                'cpf' => ['Nenhum cadastro de sócio encontrado com o CPF informado.'],
            ]);
        }

        $client->update([
            'password' => Hash::make($request->password),
            'password_set_at' => now(),
        ]);

        return response()->json([
            'message' => 'Senha cadastrada com sucesso! Agora você já pode fazer login no Portal do Sócio.',
        ]);
    }

    /**
     * Encerra a sessão atual.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sessão encerrada com sucesso.',
        ]);
    }
}