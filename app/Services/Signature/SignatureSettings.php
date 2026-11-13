<?php

namespace App\Services\Signature;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * Configurações de assinatura digital (Configurações → Assinatura Digital).
 * As credenciais dos provedores ficam criptografadas com a APP_KEY e nunca são enviadas para a tela.
 */
class SignatureSettings
{
    public const KEY = 'signature';

    public static function defaults(): array
    {
        return [
            'provider' => 'manual',
            'sandbox' => true,
            'default_delivery' => 'email',
            'include_second_holder' => true,
            'message' => 'Olá! Segue o seu contrato para assinatura digital.',
            'company_signer' => [
                'enabled' => false,
                'name' => '',
                'email' => '',
                'cpf' => '',
            ],
            'webhook_token' => '',
            'webhook_secret' => '',
        ];
    }

    /**
     * Configurações completas, com credenciais descriptografadas (uso interno).
     */
    public static function get(): array
    {
        $stored = Setting::where('key', self::KEY)->value('value');
        $stored = is_array($stored) ? $stored : [];

        $settings = array_replace_recursive(self::defaults(), $stored);
        $settings['credentials'] = self::decryptCredentials($stored['credentials_encrypted'] ?? null);
        unset($settings['credentials_encrypted']);

        return $settings;
    }

    /**
     * Credenciais de um provedor.
     */
    public static function credentials(string $provider): array
    {
        return self::get()['credentials'][$provider] ?? [];
    }

    /**
     * Versão segura para a tela: sem valores de credenciais, só se cada campo está preenchido.
     */
    public static function forDisplay(): array
    {
        $settings = self::get();

        $filled = [];
        foreach ($settings['credentials'] as $provider => $fields) {
            foreach ($fields as $field => $value) {
                $filled[$provider][$field] = filled($value);
            }
        }

        unset($settings['credentials'], $settings['webhook_secret']);
        $settings['credentials_filled'] = $filled;
        $settings['has_webhook_secret'] = filled(self::get()['webhook_secret']);

        return $settings;
    }

    /**
     * Salva as configurações. Credenciais vazias mantêm o valor atual (o campo de senha vem em branco da tela).
     */
    public static function save(array $data, array $credentials = []): void
    {
        $current = self::get();

        $mergedCredentials = $current['credentials'];
        foreach ($credentials as $provider => $fields) {
            foreach ((array) $fields as $field => $value) {
                if (filled($value)) {
                    $mergedCredentials[$provider][$field] = $value;
                }
            }
        }

        $value = array_replace_recursive(
            array_diff_key($current, ['credentials' => true]),
            array_diff_key($data, ['credentials' => true])
        );

        if (empty($value['webhook_token'])) {
            $value['webhook_token'] = Str::random(40);
        }

        $value['credentials_encrypted'] = Crypt::encryptString(json_encode($mergedCredentials));

        Setting::updateOrCreate(['key' => self::KEY], ['value' => $value]);
    }

    /**
     * Garante que exista um token para a URL do webhook e o devolve.
     */
    public static function webhookToken(): string
    {
        $token = self::get()['webhook_token'];
        if (!$token) {
            self::save([]);
            $token = self::get()['webhook_token'];
        }

        return $token;
    }

    private static function decryptCredentials(?string $encrypted): array
    {
        if (!$encrypted) {
            return [];
        }

        try {
            $decoded = json_decode(Crypt::decryptString($encrypted), true);
            return is_array($decoded) ? $decoded : [];
        } catch (\Throwable $e) {
            // APP_KEY trocada: credenciais precisam ser informadas de novo
            return [];
        }
    }
}
