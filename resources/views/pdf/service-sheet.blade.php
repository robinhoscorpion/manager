<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha de Atendimento - {{ $client->nome }}</title>
    <style>
        :root {
            --paper-width: 210mm;
            --paper-height: 297mm;
        }
        @page {
            size: A4 portrait;
            margin: 15mm;
        }
        body {
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #fff;
        }
        .container {
            width: 100%;
            max-width: var(--paper-width);
            margin: auto;
        }
        .header {
            border-bottom: 3px solid #1a1f2e;
            padding-bottom: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #1a1f2e;
        }
        .header-meta {
            text-align: right;
            font-size: 11px;
            color: #666;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #fff;
            background-color: #1a1f2e;
            padding: 6px 12px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            padding: 0 5px;
        }
        .field {
            margin-bottom: 8px;
        }
        .label {
            display: block;
            font-size: 10px;
            font-weight: bold;
            color: #888;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .value {
            font-size: 13px;
            color: #111;
            font-weight: 500;
        }
        .address-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 15px;
            border-radius: 8px;
        }
        .obs-box {
            min-height: 100px;
            border: 1px dashed #cbd5e1;
            padding: 15px;
            font-style: italic;
            color: #475569;
        }
        .footer {
            margin-top: 50px;
            border-top: 1px solid #eee;
            padding-top: 20px;
            text-align: center;
            font-size: 10px;
            color: #999;
        }
        @media print {
            body { background: none; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>Ficha de Atendimento</h1>
                <p style="margin: 5px 0 0; font-size: 14px; color: #64748b;">Protocolo: #{{ str_pad($service->id, 5, '0', STR_PAD_LEFT) }}</p>
            </div>
            <div class="header-meta">
                <p>Data: <strong>{{ $service_date }}</strong></p>
                <p>Hora: <strong>{{ $service->time }}</strong></p>
                <p>Local: <strong>{{ $service->local }}</strong></p>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Informações do Titular</div>
            <div class="grid">
                <div class="field">
                    <span class="label">Nome Completo</span>
                    <span class="value">{{ $client->nome }}</span>
                </div>
                <div class="field">
                    <span class="label">Data de Nascimento</span>
                    <span class="value">{{ $client_birth }}</span>
                </div>
                <div class="field">
                    <span class="label">Profissão</span>
                    <span class="value">{{ $client->profissao }}</span>
                </div>
                <div class="field">
                    <span class="label">Estado Civil</span>
                    <span class="value">{{ $client->estado_civil }}</span>
                </div>
                <div class="field">
                    <span class="label">Celular Principal</span>
                    <span class="value">{{ $client->celular1 }}</span>
                </div>
                <div class="field">
                    <span class="label">E-mail</span>
                    <span class="value">{{ $client->email }}</span>
                </div>
            </div>
        </div>

        @if($service->tem_conjuge)
        <div class="section">
            <div class="section-title">Informações do Cônjuge</div>
            <div class="grid">
                <div class="field">
                    <span class="label">Nome do Cônjuge</span>
                    <span class="value">{{ $service->nome_conjuge }}</span>
                </div>
                <div class="field">
                    <span class="label">Data de Nascimento</span>
                    <span class="value">{{ $spouse_birth }}</span>
                </div>
                <div class="field">
                    <span class="label">Profissão</span>
                    <span class="value">{{ $service->profissao_conjuge }}</span>
                </div>
                <div class="field">
                    <span class="label">Tempo Juntos</span>
                    <span class="value">{{ $service->tempo_juntos }}</span>
                </div>
            </div>
        </div>
        @endif

        <div class="section">
            <div class="section-title">Endereço de Origem</div>
            <div class="address-box">
                @if($client->address)
                    <div class="grid" style="grid-template-columns: 2fr 1fr;">
                        <div class="field">
                            <span class="label">Logradouro</span>
                            <span class="value">{{ $client->address->rua }}, {{ $client->address->numero }}</span>
                        </div>
                        <div class="field">
                            <span class="label">CEP</span>
                            <span class="value">{{ $client->address->cep }}</span>
                        </div>
                    </div>
                    <div class="grid" style="grid-template-columns: 1fr 1fr 1fr; margin-top: 10px;">
                        <div class="field">
                            <span class="label">Bairro</span>
                            <span class="value">{{ $client->address->bairro }}</span>
                        </div>
                        <div class="field">
                            <span class="label">Cidade</span>
                            <span class="value">{{ $client->address->cidade }}</span>
                        </div>
                        <div class="field">
                            <span class="label">Estado</span>
                            <span class="value">{{ $client->address->estado }}</span>
                        </div>
                    </div>
                @else
                    <p style="text-align: center; color: #94a3b8; font-size: 12px;">Endereço não cadastrado no sistema.</p>
                @endif
            </div>
        </div>

        <div class="section">
            <div class="section-title">Observações e Logística</div>
            <div class="grid" style="margin-bottom: 15px;">
                <div class="field">
                    <span class="label">Qualificação</span>
                    <span class="value">{{ $service->qualification ?? 'N/A' }}</span>
                </div>
                <div class="field">
                    <span class="label">OPC / Atendente</span>
                    <span class="value">{{ $service->opc_avatar ?? 'Não informado' }}</span>
                </div>
            </div>
            <div class="obs-box">
                <span class="label" style="margin-bottom: 10px;">Notas Adicionais:</span>
                {{ $service->observacoes ?: 'Sem observações registradas.' }}
            </div>
        </div>

        <div class="footer">
            <p>Gerado em {{ date('d/m/Y H:i:s') }} • Manager 2026 Core Engine</p>
        </div>
    </div>

    <script>
        window.onload = function() {
            setTimeout(() => { window.print(); }, 500);
        };
    </script>
</body>
</html>
