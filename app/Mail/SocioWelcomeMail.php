<?php

namespace App\Mail;

use App\Models\Client;
use App\Models\SalesService;
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
    public $customSubject;
    public $salesService;

    public function __construct(
        Client $client, 
        string $portalUrl = '', 
        string $customMessage = '', 
        ?string $tempPassword = null, 
        ?string $customSubject = null,
        ?SalesService $salesService = null
    ) {
        $this->client = $client;
        $this->portalUrl = $portalUrl ?: 'https://itacarevacationclub.com.br/';
        $this->tempPassword = $tempPassword;
        $this->salesService = $salesService;

        $this->customSubject = self::parseTags($customSubject ?: '', $client, $salesService, $this->portalUrl);
        $this->customMessage = self::parseTags($customMessage ?: '', $client, $salesService, $this->portalUrl);
    }

    public static function parseTags(string $text, Client $client, ?SalesService $salesService = null, string $portalUrl = ''): string
    {
        if (empty($text)) return '';

        $url = $portalUrl ?: 'https://itacarevacationclub.com.br/';
        $contract = 'S/N';
        $product = 'Serviço';

        if ($salesService) {
            if ($salesService->proposal) {
                $contract = $salesService->proposal->contract_number ?: 'S/N';
                if ($salesService->proposal->product) {
                    $product = $salesService->proposal->product->name ?: 'Serviço';
                }
            }
        }

        return strtr($text, [
            '{nome}' => $client->nome ?? 'Sócio',
            '{cpf}' => $client->cpf ?? 'Não informado',
            '{email}' => $client->email ?? '',
            '{contrato}' => $contract,
            '{produto}' => $product,
            '{link_portal}' => $url,
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->customSubject ?: 'Bem-vindo(a) ao Portal do Sócio - Seu Acesso',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.socio_welcome',
        );
    }

    protected function buildHtml(): string
    {
        $name = htmlspecialchars($this->client->nome ?? 'Sócio');
        $cpf = htmlspecialchars($this->client->cpf ?? 'Não informado');
        $email = htmlspecialchars($this->client->email ?? '');
        $url = htmlspecialchars($this->portalUrl);

        $passwordBlock = '';
        if ($this->tempPassword) {
            $pass = htmlspecialchars($this->tempPassword);
            $passwordBlock = "
                <div style='background-color: #fefce8; border: 1px solid rgba(162, 107, 69, 0.3); padding: 18px; border-radius: 14px; margin: 24px 0; color: #713f12;'>
                    <div style='font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #A26B45; margin-bottom: 6px;'>🔑 Sua Senha Provisória</div>
                    <div style='display: inline-block; background: #ffffff; padding: 6px 16px; border-radius: 8px; border: 1px solid #fde047; font-family: monospace; font-size: 18px; font-weight: bold; color: #A26B45;'>{$pass}</div>
                    <p style='margin: 10px 0 0 0; font-size: 12px; color: #854d0e;'>Recomendamos alterar a sua senha após efetuar o primeiro acesso ao portal.</p>
                </div>
            ";
        } else {
            $passwordBlock = "
                <div style='background-color: rgba(90, 107, 70, 0.05); border: 1px solid rgba(90, 107, 70, 0.25); padding: 18px; border-radius: 14px; margin: 24px 0;'>
                    <div style='font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #5A6B46; margin-bottom: 6px;'>📌 Instrução para Primeiro Acesso</div>
                    <div style='font-size: 14px; color: #475569; line-height: 1.6;'>
                        Acesse o portal abaixo, clique na opção <strong>'Primeiro Acesso'</strong>, informe seu CPF (<strong>{$cpf}</strong>) e crie sua senha de forma rápida e segura.
                    </div>
                </div>
            ";
        }

        $bodyContent = '';
        if (!empty($this->customMessage)) {
            $bodyContent = "<div style='font-size: 15px; line-height: 1.7; color: #334155; white-space: pre-wrap; margin-bottom: 24px;'>" . htmlspecialchars($this->customMessage) . "</div>";
        } else {
            $bodyContent = "
                <h2 style='color: #0f172a; margin-top: 0; font-size: 22px; font-weight: 700;'>Olá, {$name}!</h2>
                <p style='font-size: 15px; line-height: 1.7; color: #475569;'>
                    Seja muito bem-vindo(a)! Estamos felizes em ter você conosco no <strong>Itacaré Vacation Club</strong>. Seu cadastro no Portal do Sócio já está disponível para acesso.
                </p>
            ";
        }

        $logoSvg = "
            <svg viewBox='0 0 500 120' xmlns='http://www.w3.org/2000/svg' style='height: 56px; width: auto; max-width: 100%; display: block; margin: 0 auto;'>
                <g transform='translate(10, 10)'>
                    <path d='M 75 40 A 35 35 0 0 1 125 70' fill='none' stroke='#B47045' stroke-width='4.5' stroke-linecap='round'/>
                    <path d='M 60 88 C 80 50, 120 50, 150 88 Q 105 98 60 88 Z' fill='#5A6B46'/>
                    <path d='M 5 95 C 30 75, 70 75, 100 95 C 130 115, 160 85, 175 75 C 150 110, 110 125, 85 105 C 50 85, 20 95, 5 95 Z' fill='#2C5160'/>
                    <path d='M 35 25 Q 55 45 65 50 Q 75 45 82 46 Q 72 52 62 56 Q 50 53 35 25 Z' fill='#B47045'/>
                </g>
                <line x1='195' y1='20' x2='195' y2='100' stroke='#B47045' stroke-width='1.5' />
                <g transform='translate(225, 70)'>
                    <text x='0' y='0' font-family='Georgia, \"Times New Roman\", serif' font-size='48' font-weight='600' letter-spacing='9' fill='#B47045'>ITACARÉ</text>
                    <text x='6' y='32' font-family='sans-serif' font-size='14' font-weight='600' letter-spacing='12' fill='#B47045'>VACATION CLUB</text>
                </g>
            </svg>
        ";

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Portal do Sócio - Itacaré Vacation Club</title>
        </head>
        <body style='font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 32px 15px; color: #334155;'>
            
            <!-- Container Principal no Padrão do /atendimentos (rounded-[20px], border border-brand-green/20) -->
            <div style='max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05), 0 8px 10px -6px rgba(0,0,0,0.01); border: 1px solid rgba(90, 107, 70, 0.2);'>
                
                <!-- Topo com Faixa Verde de Atendimentos (#5A6B46) e Logomarca em Fundo Branco -->
                <div style='background: #ffffff; padding: 32px 20px 24px 20px; text-align: center; border-bottom: 1px solid rgba(90, 107, 70, 0.15); border-top: 5px solid #5A6B46;'>
                    <a href='{$url}' target='_blank' style='text-decoration: none; display: inline-block;'>
                        {$logoSvg}
                    </a>
                </div>

                <!-- Conteúdo -->
                <div style='padding: 32px 28px;'>
                    
                    <!-- Badge Padrão Atendimentos -->
                    <div style='display: inline-block; background-color: rgba(90, 107, 70, 0.1); color: #5A6B46; border: 1px solid rgba(90, 107, 70, 0.25); border-radius: 8px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; padding: 4px 12px; margin-bottom: 20px;'>
                        PÓS-VENDA • PORTAL DO SÓCIO
                    </div>

                    {$bodyContent}

                    <!-- Tabela de Credenciais (Estilo Card Atendimentos) -->
                    <div style='background: #f8fafc; border: 1px solid rgba(90, 107, 70, 0.2); border-radius: 14px; padding: 18px 22px; margin: 24px 0;'>
                        <div style='font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #5A6B46; margin-bottom: 12px;'>
                            📋 Credenciais de Acesso
                        </div>
                        <table style='width: 100%; border-collapse: collapse;'>
                            <tr>
                                <td style='padding: 6px 0; font-size: 14px; color: #64748b; width: 100px; font-weight: 600;'>Seu Login:</td>
                                <td style='padding: 6px 0; font-size: 14px; color: #0f172a;'><strong>{$cpf}</strong> <span style='color: #94a3b8; font-size: 12px;'>(CPF)</span></td>
                            </tr>
                            " . ($email ? "
                            <tr>
                                <td style='padding: 6px 0; font-size: 14px; color: #64748b; font-weight: 600;'>E-mail:</td>
                                <td style='padding: 6px 0; font-size: 14px; color: #0f172a;'>{$email}</td>
                            </tr>
                            " : "") . "
                        </table>
                    </div>

                    {$passwordBlock}

                    <!-- Botão de Ação no Padrão Atendimentos (bg-brand-green, rounded-[12px]) -->
                    <div style='text-align: center; margin-top: 32px;'>
                        <a href='{$url}' target='_blank' style='display: inline-block; background-color: #5A6B46; color: #ffffff; font-weight: 700; text-decoration: none; padding: 14px 36px; border-radius: 12px; text-transform: uppercase; font-size: 13px; letter-spacing: 0.5px; box-shadow: 0 4px 14px rgba(90, 107, 70, 0.35);'>
                            Acessar Portal do Sócio
                        </a>
                    </div>
                </div>

                <!-- Rodapé nas Cores da Marca -->
                <div style='background: #f8fafc; padding: 22px 20px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid rgba(90, 107, 70, 0.15);'>
                    <p style='margin: 0 0 6px 0; font-weight: 700; color: #5A6B46;'>Itacaré Vacation Club</p>
                    <p style='margin: 0; font-size: 11px; color: #94a3b8;'>Este é um e-mail automático enviado pela equipe de Pós-Venda.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
