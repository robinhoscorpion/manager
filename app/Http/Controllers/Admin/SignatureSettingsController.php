<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Signature\SignatureManager;
use App\Services\Signature\SignatureSettings;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SignatureSettingsController extends Controller
{
    public function index(SignatureManager $manager)
    {
        $settings = SignatureSettings::forDisplay();

        return Inertia::render('Admin/Settings/Signature', [
            'settings' => $settings,
            'providers' => $manager->catalog(),
            'webhookUrls' => collect($manager->registered())->keys()
                ->reject(fn ($key) => $key === 'manual')
                ->mapWithKeys(fn ($key) => [$key => route('webhooks.signature', ['provider' => $key, 'token' => SignatureSettings::webhookToken()])]),
        ]);
    }

    public function update(Request $request, SignatureManager $manager)
    {
        $validated = $request->validate([
            'provider' => ['required', 'string', function ($attribute, $value, $fail) use ($manager) {
                if (!$manager->has($value)) {
                    $fail('Provedor de assinatura inválido.');
                }
            }],
            'sandbox' => 'required|boolean',
            'default_delivery' => 'required|string|in:email,whatsapp,link',
            'include_second_holder' => 'required|boolean',
            'message' => 'nullable|string|max:500',
            'company_signer.enabled' => 'required|boolean',
            'company_signer.name' => 'nullable|required_if:company_signer.enabled,true|string|max:255',
            'company_signer.email' => 'nullable|required_if:company_signer.enabled,true|email|max:255',
            'company_signer.cpf' => 'nullable|string|max:20',
            'webhook_secret' => 'nullable|string|max:255',
            'credentials' => 'nullable|array',
        ], [
            'required_if' => 'O campo :attribute é obrigatório quando o representante da empresa assina.',
            'email' => 'Informe um e-mail válido.',
        ], [
            'company_signer.name' => 'nome do representante',
            'company_signer.email' => 'e-mail do representante',
        ]);

        $data = collect($validated)->except(['credentials', 'webhook_secret'])->all();

        // Segredo do webhook: em branco mantém o atual; "-" remove
        if ($request->filled('webhook_secret')) {
            $data['webhook_secret'] = $request->webhook_secret === '-' ? '' : $request->webhook_secret;
        }

        SignatureSettings::save($data, $request->input('credentials', []));

        return redirect()->back()->with('success', 'Configurações de assinatura digital salvas!');
    }

    /**
     * Testa a conexão com o provedor selecionado usando as credenciais salvas.
     */
    public function test(SignatureManager $manager)
    {
        $result = $manager->provider()->testConnection();

        return redirect()->back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }
}
