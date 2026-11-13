<?php

namespace App\Services\Signature;

use App\Services\Signature\Contracts\SignatureProvider;

/**
 * Resolve o provedor de assinatura configurado (ou um específico) já com as credenciais aplicadas.
 */
class SignatureManager
{
    /**
     * @return array<string, class-string<SignatureProvider>>
     */
    public function registered(): array
    {
        return config('signature.providers', []);
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->registered());
    }

    /**
     * Provedor pelo nome; sem nome, usa o escolhido em Configurações.
     */
    public function provider(?string $key = null): SignatureProvider
    {
        $settings = SignatureSettings::get();
        $key = $key ?: $settings['provider'];

        if (!$this->has($key)) {
            throw new SignatureException("Provedor de assinatura \"{$key}\" não está disponível.");
        }

        /** @var SignatureProvider $provider */
        $provider = app($this->registered()[$key]);

        return $provider->configure($settings['credentials'][$key] ?? [], (bool) $settings['sandbox']);
    }

    /**
     * Lista para a tela de configurações (sem credenciais).
     */
    public function catalog(): array
    {
        return collect($this->registered())->map(function ($class, $key) {
            /** @var SignatureProvider $provider */
            $provider = app($class);

            return [
                'key' => $key,
                'label' => $provider->label(),
                'supports_sending' => $provider->supportsSending(),
                'credential_fields' => $provider->credentialFields(),
                'delivery_methods' => $provider->deliveryMethods(),
            ];
        })->values()->all();
    }
}
