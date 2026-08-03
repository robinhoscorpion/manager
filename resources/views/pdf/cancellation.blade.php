<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Termo de Distrato - #{{ $cancellation->proposal->contract_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 40px; color: #333; }
        .header { text-align: center; margin-bottom: 40px; border-bottom: 2px solid #ccc; padding-bottom: 20px; }
        .header h1 { margin: 0; font-size: 24px; text-transform: uppercase; }
        .header p { margin: 5px 0 0; font-size: 14px; color: #666; }
        .section { margin-bottom: 30px; }
        .section-title { font-size: 16px; font-weight: bold; text-transform: uppercase; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
        .row { display: flex; flex-wrap: wrap; margin-bottom: 10px; }
        .col { flex: 1; min-width: 50%; }
        .label { font-weight: bold; font-size: 12px; text-transform: uppercase; color: #555; display: block; }
        .value { font-size: 14px; margin-top: 2px; }
        .text-content { font-size: 14px; line-height: 1.6; text-align: justify; }
        
        .financial-box { border: 1px solid #ddd; padding: 20px; border-radius: 8px; margin-top: 20px; }
        .financial-row { display: flex; justify-content: space-between; border-bottom: 1px solid #eee; padding: 8px 0; }
        .financial-row:last-child { border-bottom: none; }
        .financial-row.total { font-weight: bold; font-size: 16px; border-top: 2px solid #333; margin-top: 10px; padding-top: 15px; }
        
        .signatures { margin-top: 80px; display: flex; justify-content: space-around; }
        .signature-line { text-align: center; width: 40%; }
        .signature-line div { border-top: 1px solid #333; padding-top: 10px; font-weight: bold; }
        .signature-line span { font-size: 12px; color: #666; }
        
        @media print {
            body { padding: 0; }
            @page { margin: 2cm; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Termo de Distrato</h1>
        <p>Contrato Nº: <strong>{{ $cancellation->proposal->contract_number ?? 'S/N' }}</strong> | Data: <strong>{{ $cancellation->created_at->format('d/m/Y') }}</strong></p>
    </div>

    <div class="section">
        <div class="section-title">Dados do Cliente</div>
        <div class="row">
            <div class="col">
                <span class="label">Nome</span>
                <span class="value">{{ $cancellation->proposal->client->nome ?? '-' }}</span>
            </div>
            <div class="col">
                <span class="label">CPF</span>
                <span class="value">{{ $cancellation->proposal->client->cpf ?? '-' }}</span>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <span class="label">E-mail</span>
                <span class="value">{{ $cancellation->proposal->client->email ?? '-' }}</span>
            </div>
            <div class="col">
                <span class="label">Telefone</span>
                <span class="value">{{ $cancellation->proposal->client->celular1 ?? '-' }}</span>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Informações do Cancelamento</div>
        <div class="row">
            <div class="col">
                <span class="label">Motivo do Distrato</span>
                <span class="value">{{ $cancellation->reason }}</span>
            </div>
        </div>
        <div class="row" style="margin-top: 15px;">
            <div class="col">
                <span class="label">Detalhes / Observações</span>
                <div class="text-content" style="margin-top: 5px;">
                    {!! nl2br(e($cancellation->details ?? 'Nenhuma observação informada.')) !!}
                </div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Acerto Financeiro</div>
        <div class="financial-box">
            <div class="financial-row">
                <span>Total Pago (Até o momento)</span>
                <span>R$ {{ number_format($cancellation->total_paid, 2, ',', '.') }}</span>
            </div>
            <div class="financial-row text-red">
                <span>(-) Multa / Retenção Aplicada</span>
                <span>R$ {{ number_format($cancellation->fine_amount, 2, ',', '.') }}</span>
            </div>
            <div class="financial-row total">
                <span>(=) Valor a ser Estornado / Devolvido</span>
                <span>R$ {{ number_format($cancellation->refund_amount, 2, ',', '.') }}</span>
            </div>
        </div>
        <p style="font-size: 12px; color: #777; margin-top: 10px; text-align: justify;">
            Pelo presente instrumento, as partes acima qualificadas declaram distratar o contrato mencionado, não restando qualquer obrigação futura entre ambas, dando-se plena, geral e irrevogável quitação.
        </p>
    </div>

    <div class="signatures">
        <div class="signature-line">
            <div>{{ $cancellation->proposal->client->nome ?? 'Cliente' }}</div>
            <span>CONTRATANTE</span>
        </div>
        <div class="signature-line">
            <div>MANAGER</div>
            <span>CONTRATADA (Cancelado por: {{ $cancellation->user->name ?? 'Sistema' }})</span>
        </div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
