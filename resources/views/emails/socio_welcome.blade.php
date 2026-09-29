<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal do Sócio - Itacaré Vacation Club</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 24px 12px; color: #334155;">
    
    <!-- Container Principal no Padrão do /atendimentos (rounded-[20px], border border-brand-green/20) -->
    <div style="max-width: 580px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px -2px rgba(0,0,0,0.06); border: 1px solid rgba(90, 107, 70, 0.2);">
        
        <!-- Topo com Logomarca em Fundo Branco e Faixa Superior Verde (#5A6B46) -->
        <div style="background: #ffffff; padding: 22px 20px 18px 20px; text-align: center; border-bottom: 1px solid rgba(90, 107, 70, 0.12); border-top: 4px solid #5A6B46;">
            <a href="{{ $portalUrl }}" target="_blank" style="text-decoration: none; display: inline-block;">
                @if(file_exists(public_path('images/custom-logo-small.png')))
                    <img src="{{ $message->embed(public_path('images/custom-logo-small.png')) }}" alt="Itacaré Vacation Club" style="max-height: 75px; width: auto; max-width: 100%; display: block; margin: 0 auto; border: 0;">
                @elseif(file_exists(public_path('images/custom-logo.png')))
                    <img src="{{ $message->embed(public_path('images/custom-logo.png')) }}" alt="Itacaré Vacation Club" style="max-height: 75px; width: auto; max-width: 100%; display: block; margin: 0 auto; border: 0;">
                @elseif(file_exists(public_path('images/logo-itacare.png')))
                    <img src="{{ $message->embed(public_path('images/logo-itacare.png')) }}" alt="Itacaré Vacation Club" style="max-height: 75px; width: auto; max-width: 100%; display: block; margin: 0 auto; border: 0;">
                @else
                    <span style="font-family: Georgia, serif; font-size: 20px; font-weight: bold; color: #B47045; letter-spacing: 2px;">ITACARÉ VACATION CLUB</span>
                @endif
            </a>
        </div>

        <!-- Conteúdo Interno com Espaçamentos Harmoniosos -->
        <div style="padding: 24px 24px 28px 24px;">
            
            <!-- Badge Padrão Atendimentos -->
            <div style="display: inline-block; background-color: rgba(90, 107, 70, 0.08); color: #5A6B46; border: 1px solid rgba(90, 107, 70, 0.2); border-radius: 6px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; padding: 3px 10px; margin-bottom: 16px;">
                PÓS-VENDA • PORTAL DO SÓCIO
            </div>

            <!-- Corpo da Mensagem (Espaçamento Ajustado sem Duplicação de Quebras) -->
            @if(!empty($customMessage))
                <div style="font-size: 14px; line-height: 1.55; color: #334155; margin-bottom: 18px;">{!! nl2br(e($customMessage)) !!}</div>
            @else
                <h2 style="color: #0f172a; margin-top: 0; margin-bottom: 10px; font-size: 18px; font-weight: 700;">Olá, {{ $client->nome ?? 'Sócio' }}!</h2>
                <p style="font-size: 14px; line-height: 1.55; color: #475569; margin: 0 0 16px 0;">
                    Seja muito bem-vindo(a)! Estamos felizes em ter você conosco no <strong>Itacaré Vacation Club</strong>. Seu cadastro no Portal do Sócio já está disponível para acesso.
                </p>
            @endif

            <!-- Card de Credenciais de Acesso -->
            <div style="background: #f8fafc; border: 1px solid rgba(90, 107, 70, 0.18); border-radius: 12px; padding: 14px 18px; margin: 16px 0;">
                <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #5A6B46; margin-bottom: 8px;">
                    📋 Credenciais de Acesso
                </div>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 4px 0; font-size: 13px; color: #64748b; width: 95px; font-weight: 600;">Seu Login:</td>
                        <td style="padding: 4px 0; font-size: 13px; color: #0f172a;"><strong>{{ $client->cpf ?? 'Não informado' }}</strong> <span style="color: #94a3b8; font-size: 11px;">(CPF)</span></td>
                    </tr>
                    @if($client->email)
                    <tr>
                        <td style="padding: 4px 0; font-size: 13px; color: #64748b; font-weight: 600;">E-mail:</td>
                        <td style="padding: 4px 0; font-size: 13px; color: #0f172a;">{{ $client->email }}</td>
                    </tr>
                    @endif
                </table>
            </div>

            <!-- Bloco de Senha Provisória ou Instrução -->
            @if($tempPassword)
                <div style="background-color: #fefce8; border: 1px solid rgba(162, 107, 69, 0.25); padding: 14px 18px; border-radius: 12px; margin: 16px 0; color: #713f12;">
                    <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #A26B45; margin-bottom: 4px;">🔑 Sua Senha Provisória</div>
                    <div style="display: inline-block; background: #ffffff; padding: 4px 14px; border-radius: 6px; border: 1px solid #fde047; font-family: monospace; font-size: 16px; font-weight: bold; color: #A26B45; margin: 4px 0;">{{ $tempPassword }}</div>
                    <p style="margin: 6px 0 0 0; font-size: 11px; color: #854d0e;">Recomendamos alterar a sua senha após efetuar o primeiro acesso ao portal.</p>
                </div>
            @else
                <div style="background-color: rgba(90, 107, 70, 0.04); border: 1px solid rgba(90, 107, 70, 0.2); padding: 14px 18px; border-radius: 12px; margin: 16px 0;">
                    <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #5A6B46; margin-bottom: 4px;">📌 Instrução para Primeiro Acesso</div>
                    <div style="font-size: 13px; color: #475569; line-height: 1.5;">
                        Acesse o portal abaixo, clique na opção <strong>'Primeiro Acesso'</strong>, informe seu CPF (<strong>{{ $client->cpf ?? 'Não informado' }}</strong>) e crie sua senha com segurança.
                    </div>
                </div>
            @endif

            <!-- Botão de Ação -->
            <div style="text-align: center; margin-top: 24px; margin-bottom: 8px;">
                <a href="{{ $portalUrl }}" target="_blank" style="display: inline-block; background-color: #5A6B46; color: #ffffff; font-weight: 700; text-decoration: none; padding: 12px 32px; border-radius: 10px; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; box-shadow: 0 3px 10px rgba(90, 107, 70, 0.3);">
                    Acessar Portal do Sócio
                </a>
            </div>
        </div>

        <!-- Rodapé Discreto nas Cores da Marca -->
        <div style="background: #f8fafc; padding: 16px 20px; text-align: center; font-size: 11px; color: #64748b; border-top: 1px solid rgba(90, 107, 70, 0.12);">
            <p style="margin: 0 0 3px 0; font-weight: 700; color: #5A6B46;">Itacaré Vacation Club</p>
            <p style="margin: 0; font-size: 10px; color: #94a3b8;">Este é um e-mail automático enviado pela equipe de Pós-Venda.</p>
        </div>
    </div>
</body>
</html>
