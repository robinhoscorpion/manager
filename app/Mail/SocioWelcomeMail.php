<?php

namespace App\Mail;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SocioWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $client;
    public $portalUrl;
    public $customMessage;
    public $tempPassword;

    public function __construct(Client $client, string $portalUrl = '', string $customMessage = '', ?string $tempPassword = null)
    {
        $this->client = $client;
        $this->portalUrl = $portalUrl ?: config('app.url');
        $this->customMessage = $customMessage;
        $this->tempPassword = $tempPassword;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bem-vindo(a) ao Portal do Sócio - Seu Acesso',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtml(),
        );
    }

    protected function buildHtml(): string
    {
        $name = htmlspecialchars($this->client->nome ?? 'Sócio');
        $cpf = htmlspecialchars($this->client->cpf ?? 'Não informado');
        $email = htmlspecialchars($this->client->email ?? '');
        $url = htmlspecialchars($this->portalUrl);
        $messageText = !empty($this->customMessage) ? nl2br(htmlspecialchars($this->customMessage)) : '';

        $passwordBlock = '';
        if ($this->tempPassword) {
            $pass = htmlspecialchars($this->tempPassword);
            $passwordBlock = "
                <div style='background-color: #fef3c7; border: 1px solid #f59e0b; padding: 12px; border-radius: 8px; margin: 16px 0; color: #92400e;'>
                    <strong>Sua Senha Provisória:</strong> <code style='font-size: 16px; background: #fff; padding: 2px 6px; border-radius: 4px;'>{$pass}</code><br>
                    <small>Recomendamos alterar sua senha após efetuar o primeiro login.</small>
                </div>
            ";
        } else {
            $passwordBlock = "
                <div style='background-color: #f0fdf4; border: 1px solid #22c55e; padding: 12px; border-radius: 8px; margin: 16px 0; color: #166534;'>
                    <strong>Instrução para Primeiro Acesso:</strong><br>
                    Acesse o portal abaixo, clique em <strong>'Primeiro Acesso'</strong>, informe seu CPF e crie sua senha com segurança.
                </div>
            ";
        }

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px; color: #333; }
                .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
                .header { background: #0f172a; color: #ffffff; padding: 24px; text-align: center; }
                .header h1 { margin: 0; font-size: 20px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
                .content { padding: 30px; }
                .btn { display: inline-block; background-color: #10b981; color: #ffffff; font-weight: bold; text-decoration: none; padding: 12px 24px; border-radius: 8px; margin-top: 20px; text-transform: uppercase; font-size: 14px; }
                .footer { background: #f8fafc; padding: 16px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0; }
                .info-table { width: 100%; border-collapse: collapse; margin: 16px 0; }
                .info-table td { padding: 8px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
                .info-table td.label { font-weight: bold; color: #475569; width: 120px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Portal do Sócio</h1>
                </div>
                <div class='content'>
                    <h2 style='color: #0f172a; margin-top: 0;'>Olá, {$name}!</h2>
                    <p style='font-size: 15px; line-height: 1.6; color: #475569;'>
                        Seja muito bem-vindo(a)! Estamos felizes em ter você conosco. Seu cadastro no Portal do Sócio já está disponível para acesso.
                    </p>
                    
                    " . ($messageText ? "<div style='background: #f8fafc; padding: 12px; border-left: 4px solid #3b82f6; margin: 16px 0; font-size: 14px;'>{$messageText}</div>" : "") . "

                    <table class='info-table'>
                        <tr>
                            <td class='label'>Seu Login:</td>
                            <td><strong>{$cpf}</strong> (ou seu E-mail)</td>
                        </tr>
                        " . ($email ? "<tr><td class='label'>E-mail:</td><td>{$email}</td></tr>" : "") . "
                    </table>

                    {$passwordBlock}

                    <div style='text-align: center; margin-top: 24px;'>
                        <a href='{$url}' class='btn' style='color: #ffffff;'>Acessar Portal do Sócio</a>
                    </div>
                </div>
                <div class='footer'>
                    Este é um e-mail automático enviado pela equipe de Pós-Venda.
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
