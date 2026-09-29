<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TestSmtpMail;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class MailSettingsController extends Controller
{
    /**
     * Retorna os valores padrão para configuração de e-mail.
     */
    public static function getDefaultSettings(): array
    {
        return [
            'mailer'       => config('mail.default', 'smtp'),
            'host'         => config('mail.mailers.smtp.host') ?: 'smtp.gmail.com',
            'port'         => config('mail.mailers.smtp.port') ?: 587,
            'encryption'   => config('mail.mailers.smtp.encryption') ?: 'tls',
            'username'     => config('mail.mailers.smtp.username') ?: '',
            'password'     => config('mail.mailers.smtp.password') ?: '',
            'from_address' => config('mail.from.address') ?: 'nao-responda@dominio.com',
            'from_name'    => config('mail.from.name') ?: 'Portal do Sócio',
        ];
    }

    /**
     * Aplica dinamicamente as configurações de SMTP em tempo de execução.
     */
    public static function applyMailConfig(?array $settings = null): void
    {
        if (!$settings) {
            $record = Setting::where('key', 'mail_settings')->first();
            $settings = $record && is_array($record->value) ? $record->value : null;
        }

        if (!$settings || empty($settings['host'])) {
            return;
        }

        $encryption = (!empty($settings['encryption']) && $settings['encryption'] !== 'none') ? $settings['encryption'] : null;

        Config::set('mail.default', $settings['mailer'] ?? 'smtp');
        Config::set('mail.mailers.smtp.transport', 'smtp');
        Config::set('mail.mailers.smtp.host', $settings['host']);
        Config::set('mail.mailers.smtp.port', (int) ($settings['port'] ?? 587));
        Config::set('mail.mailers.smtp.encryption', $encryption);
        Config::set('mail.mailers.smtp.username', $settings['username'] ?? null);
        Config::set('mail.mailers.smtp.password', $settings['password'] ?? null);

        if (!empty($settings['from_address'])) {
            Config::set('mail.from.address', $settings['from_address']);
        }
        if (!empty($settings['from_name'])) {
            Config::set('mail.from.name', $settings['from_name']);
        }

        // Limpa a instância do transportador em cache para forçar a nova configuração
        Mail::purge('smtp');
    }

    /**
     * Exibe a página de configuração de SMTP.
     */
    public function index()
    {
        $setting = Setting::where('key', 'mail_settings')->first();

        $savedValues = $setting && is_array($setting->value) ? $setting->value : [];
        $settings = array_merge(self::getDefaultSettings(), $savedValues);

        // Se houver senha salva, indicamos que há senha cadastrada
        $hasSavedPassword = !empty($settings['password']);

        return Inertia::render('Admin/Settings/Mail/Index', [
            'settings'         => $settings,
            'hasSavedPassword' => $hasSavedPassword,
        ]);
    }

    /**
     * Salva as configurações de SMTP no banco de dados.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'mailer'       => 'nullable|string',
            'host'         => 'required|string|max:255',
            'port'         => 'required|numeric|min:1|max:65535',
            'encryption'   => 'nullable|string|in:tls,ssl,none',
            'username'     => 'nullable|string|max:255',
            'password'     => 'nullable|string',
            'from_address' => 'required|email|max:255',
            'from_name'    => 'required|string|max:255',
        ]);

        $setting = Setting::where('key', 'mail_settings')->first();
        $previousValue = $setting && is_array($setting->value) ? $setting->value : [];

        // Se a senha foi deixada vazia na submissão, mantém a senha salva anteriormente
        if (empty($validated['password']) && !empty($previousValue['password'])) {
            $validated['password'] = $previousValue['password'];
        }

        $validated['mailer'] = $validated['mailer'] ?: 'smtp';
        $validated['encryption'] = $validated['encryption'] ?: 'none';

        Setting::updateOrCreate(
            ['key' => 'mail_settings'],
            ['value' => $validated]
        );

        // Atualiza a configuração em tempo de execução
        self::applyMailConfig($validated);

        return redirect()->back()->with('success', 'Configurações de Servidor de E-mail (SMTP) salvas com sucesso!');
    }

    /**
     * Executa um teste de envio de e-mail com os parâmetros informados.
     */
    public function testConnection(Request $request)
    {
        $request->validate([
            'test_email'   => 'required|email',
            'host'         => 'required|string',
            'port'         => 'required|numeric',
            'encryption'   => 'nullable|string|in:tls,ssl,none',
            'username'     => 'nullable|string',
            'password'     => 'nullable|string',
            'from_address' => 'required|email',
            'from_name'    => 'nullable|string',
        ]);

        $setting = Setting::where('key', 'mail_settings')->first();
        $previousValue = $setting && is_array($setting->value) ? $setting->value : [];

        $password = $request->input('password');
        if (empty($password) && !empty($previousValue['password'])) {
            $password = $previousValue['password'];
        }

        $testConfig = [
            'mailer'       => 'smtp',
            'host'         => $request->input('host'),
            'port'         => (int) $request->input('port'),
            'encryption'   => $request->input('encryption') ?: 'none',
            'username'     => $request->input('username'),
            'password'     => $password,
            'from_address' => $request->input('from_address'),
            'from_name'    => $request->input('from_name') ?: 'Teste de E-mail',
        ];

        try {
            // Aplica dinamicamente as credenciais de teste
            self::applyMailConfig($testConfig);

            $recipient = $request->input('test_email');
            $user = auth()->user() ? auth()->user()->name : 'Administrador';

            Mail::to($recipient)->send(new TestSmtpMail($testConfig, $user));

            return response()->json([
                'success' => true,
                'message' => "E-mail de teste disparado com sucesso para '{$recipient}'! O servidor SMTP respondeu positivamente.",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Falha na conexão SMTP: ' . $e->getMessage(),
            ], 422);
        }
    }
}
