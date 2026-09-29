<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestSmtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $smtpConfig;
    public string $triggeredBy;

    public function __construct(array $smtpConfig, string $triggeredBy = 'Administrador')
    {
        $this->smtpConfig = $smtpConfig;
        $this->triggeredBy = $triggeredBy;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Teste de Envio de E-mail - Conexão SMTP Bem-Sucedida',
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
        $host = htmlspecialchars($this->smtpConfig['host'] ?? 'N/A');
        $port = htmlspecialchars((string) ($this->smtpConfig['port'] ?? 'N/A'));
        $encryption = htmlspecialchars(strtoupper($this->smtpConfig['encryption'] ?? 'NENHUMA'));
        $from = htmlspecialchars($this->smtpConfig['from_address'] ?? 'N/A');
        $fromName = htmlspecialchars($this->smtpConfig['from_name'] ?? 'N/A');
        $sentAt = now()->format('d/m/Y H:i:s');
        $user = htmlspecialchars($this->triggeredBy);

        return "
        <!DOCTYPE html>
        <html lang='pt-BR'>
        <head>
            <meta charset='UTF-8'>
            <title>Teste de SMTP</title>
        </head>
        <body style='margin: 0; padding: 0; background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif;'>
            <table width='100%' cellpadding='0' cellspacing='0' style='background-color: #f8fafc; padding: 40px 10px;'>
                <tr>
                    <td align='center'>
                        <table width='100%' style='max-width: 600px; background-color: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);'>
                            <!-- Top Banner -->
                            <tr>
                                <td style='background-color: #2e3a23; padding: 32px 24px; text-align: center;'>
                                    <div style='display: inline-block; background-color: rgba(255, 255, 255, 0.15); border-radius: 50%; padding: 12px; margin-bottom: 12px;'>
                                        <svg width='36' height='36' viewBox='0 0 24 24' fill='none' stroke='#ffffff' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'>
                                            <polyline points='20 6 9 17 4 12'></polyline>
                                        </svg>
                                    </div>
                                    <h1 style='color: #ffffff; margin: 0; font-size: 22px; font-weight: 700; letter-spacing: -0.025em;'>Conexão SMTP Bem-Sucedida!</h1>
                                    <p style='color: #e2e8f0; margin: 6px 0 0; font-size: 14px;'>Disparo de teste efetuado com êxito</p>
                                </td>
                            </tr>

                            <!-- Content -->
                            <tr>
                                <td style='padding: 32px 28px;'>
                                    <p style='color: #334155; font-size: 15px; line-height: 1.6; margin: 0 0 20px;'>
                                        Olá, <strong>{$user}</strong>!
                                    </p>
                                    <p style='color: #334155; font-size: 15px; line-height: 1.6; margin: 0 0 24px;'>
                                        Se você está visualizando esta mensagem, significa que as credenciais do seu <strong>Servidor de E-mail (SMTP)</strong> foram validadas com sucesso pelo sistema e estão totalmente operacionais para envio de e-mails transacionais (como boas-vindas ao sócio, notificações e relatórios).
                                    </p>

                                    <!-- Details Box -->
                                    <table width='100%' style='background-color: #f1f5f9; border-radius: 12px; padding: 16px; margin-bottom: 24px;'>
                                        <tr>
                                            <td colspan='2' style='padding-bottom: 10px; font-weight: 700; font-size: 13px; color: #475569; text-transform: uppercase; letter-spacing: 0.05em;'>
                                                Parâmetros Utilizados no Teste
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style='padding: 4px 0; font-size: 13px; color: #64748b;'>Servidor Host:</td>
                                            <td style='padding: 4px 0; font-size: 13px; color: #0f172a; font-weight: 600; text-align: right;'>{$host}</td>
                                        </tr>
                                        <tr>
                                            <td style='padding: 4px 0; font-size: 13px; color: #64748b;'>Porta:</td>
                                            <td style='padding: 4px 0; font-size: 13px; color: #0f172a; font-weight: 600; text-align: right;'>{$port}</td>
                                        </tr>
                                        <tr>
                                            <td style='padding: 4px 0; font-size: 13px; color: #64748b;'>Criptografia:</td>
                                            <td style='padding: 4px 0; font-size: 13px; color: #0f172a; font-weight: 600; text-align: right;'>{$encryption}</td>
                                        </tr>
                                        <tr>
                                            <td style='padding: 4px 0; font-size: 13px; color: #64748b;'>Remetente:</td>
                                            <td style='padding: 4px 0; font-size: 13px; color: #0f172a; font-weight: 600; text-align: right;'>{$fromName} &lt;{$from}&gt;</td>
                                        </tr>
                                        <tr>
                                            <td style='padding: 4px 0; font-size: 13px; color: #64748b;'>Data e Hora:</td>
                                            <td style='padding: 4px 0; font-size: 13px; color: #0f172a; font-weight: 600; text-align: right;'>{$sentAt}</td>
                                        </tr>
                                    </table>

                                    <div style='border-top: 1px solid #e2e8f0; padding-top: 20px; font-size: 12px; color: #94a3b8; text-align: center;'>
                                        Mensagem gerada automaticamente pelo painel administrativo do sistema.
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ";
    }
}
