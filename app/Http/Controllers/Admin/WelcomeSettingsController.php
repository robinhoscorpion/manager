<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WelcomeSettingsController extends Controller
{
    public static function getDefaultSettings(): array
    {
        return [
            'dispatch_mode' => 'manual', // 'manual' ou 'automatic'
            'auto_send_email' => true,
            'auto_send_whatsapp' => false,
            'notify_first_access' => true,
            'whatsapp_template' => "Olá {nome}! Seja muito bem-vindo(a) ao Portal do Sócio.\n\nEstamos felizes em ter você conosco no {produto}.\n\n🔑 *COMO ACESSAR SEU PORTAL DO SÓCIO:*\n🌐 *Link:* {link_portal}\n👤 *Seu Login:* {cpf} (ou seu e-mail)\n📌 *Primeiro Acesso:* Clique na opção \"Primeiro Acesso\" no portal, informe seu CPF e cadastre sua senha de forma rápida e segura.\n\nSe precisar de suporte, estamos à disposição por aqui!",
            'email_subject' => "Bem-vindo(a) ao Portal do Sócio, {nome}!",
            'email_template' => "Olá {nome},\n\nSeja muito bem-vindo(a)!\n\nSeu cadastro no serviço {produto} já está disponível no Portal do Sócio.\n\nPara acessar, acesse o portal ({link_portal}), vá na opção 'Primeiro Acesso', digite seu CPF ({cpf}) e crie sua senha com segurança.\n\nQualquer dúvida, estamos à disposição.\n\nAtenciosamente,\nEquipe de Pós-venda",
        ];
    }

    public function index()
    {
        $setting = Setting::where('key', 'welcome_access_settings')->first();
        
        $settings = array_merge(
            self::getDefaultSettings(),
            $setting && is_array($setting->value) ? $setting->value : []
        );

        return Inertia::render('Admin/Settings/WelcomeAccess/Index', [
            'settings' => $settings,
            'availableTags' => [
                '{nome}' => 'Nome completo do sócio',
                '{cpf}' => 'CPF cadastrado do sócio',
                '{email}' => 'E-mail do sócio',
                '{contrato}' => 'Número do contrato/proposta',
                '{produto}' => 'Nome do produto/serviço adquirido',
                '{link_portal}' => 'URL de acesso ao Portal do Sócio',
            ],
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'dispatch_mode' => 'required|in:manual,automatic',
            'auto_send_email' => 'boolean',
            'auto_send_whatsapp' => 'boolean',
            'notify_first_access' => 'boolean',
            'whatsapp_template' => 'required|string',
            'email_subject' => 'required|string',
            'email_template' => 'required|string',
        ]);

        Setting::updateOrCreate(
            ['key' => 'welcome_access_settings'],
            ['value' => $validated]
        );

        return redirect()->back()->with('success', 'Configurações de Boas-Vindas e Acessos salvas com sucesso!');
    }
}
