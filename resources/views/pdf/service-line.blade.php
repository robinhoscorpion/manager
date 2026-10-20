<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Linha de Atendimento</title>
    <style>
        @page {
            margin: 1.2cm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            font-size: 11px;
            margin: 0;
            padding: 0;
        }
        .header {
            border-bottom: 2px solid #00c689;
            padding-bottom: 8px;
            margin-bottom: 18px;
        }
        .header table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }
        .header td {
            border: none;
            padding: 0;
            text-align: left;
            vertical-align: top;
        }
        .header td.issued {
            text-align: right;
            width: 25%;
        }
        .header h1 {
            margin: 0;
            color: #1a202c;
            font-size: 16px;
            text-transform: uppercase;
        }
        .header p {
            margin: 4px 0 0 0;
            color: #4a5568;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .header .issued-label {
            font-weight: bold;
            color: #2d3748;
            font-size: 10px;
        }
        .header .issued-date {
            color: #718096;
            font-size: 10px;
            margin-top: 2px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        th, td {
            border: 1px solid #cbd5e0;
            padding: 6px 8px;
            text-align: center;
        }
        th {
            background-color: #e2e8f0;
            color: #2d3748;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
        }
        tr {
            page-break-inside: avoid;
        }
        .summary td {
            padding: 8px;
            width: 25%;
        }
        .summary .label {
            display: block;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            color: #718096;
        }
        .summary .value {
            display: block;
            font-size: 16px;
            font-weight: bold;
            color: #1a202c;
            margin-top: 2px;
        }
        .summary .total { background-color: #f7fafc; }
        .summary .available { background-color: #f0fff4; border-color: #9ae6b4; }
        .summary .available .label, .summary .available .value { color: #276749; }
        .summary .busy { background-color: #fffaf0; border-color: #fbd38d; }
        .summary .busy .label, .summary .busy .value { color: #975a16; }
        .summary .absent { background-color: #edf2f7; }
        .summary .absent .label, .summary .absent .value { color: #4a5568; }
        .section-title {
            background-color: #edf2f7;
            border-left: 4px solid #00c689;
            padding: 6px 10px;
            margin-bottom: 6px;
            margin-top: 6px;
            page-break-after: avoid;
        }
        .section-title .name {
            font-size: 12px;
            font-weight: bold;
            color: #1a202c;
            text-transform: uppercase;
        }
        .section-title .sub {
            font-size: 9px;
            color: #718096;
            font-weight: normal;
            text-transform: none;
        }
        .section-title .stats {
            font-size: 9px;
            font-weight: bold;
            color: #4a5568;
            margin-top: 2px;
        }
        .col-pos {
            width: 8%;
            font-weight: bold;
            color: #4a5568;
        }
        .col-name {
            text-align: left;
            font-weight: bold;
            color: #1a202c;
        }
        .col-role {
            text-align: left;
            color: #4a5568;
            font-size: 10px;
            width: 28%;
        }
        .col-status {
            width: 20%;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            border: 1px solid;
        }
        .badge-available { background-color: #c6f6d5; color: #22543d; border-color: #9ae6b4; }
        .badge-busy { background-color: #feebc8; color: #7b341e; border-color: #fbd38d; }
        .badge-absent { background-color: #e2e8f0; color: #4a5568; border-color: #cbd5e0; }
        .empty-state {
            text-align: center;
            padding: 12px;
            color: #a0aec0;
            font-style: italic;
            border: 1px dashed #cbd5e0;
            margin-bottom: 14px;
        }
        .footer {
            margin-top: 16px;
            padding-top: 6px;
            border-top: 1px solid #cbd5e0;
            font-size: 9px;
            color: #718096;
        }
    </style>
</head>
<body>

    <div class="header">
        <table>
            <tr>
                <td>
                    <h1>Relatório Detalhado da Linha de Atendimento</h1>
                    <p>Sala de Vendas • Itacaré Vacation Club</p>
                </td>
                <td class="issued">
                    <div class="issued-label">Data e Hora da Emissão</div>
                    <div class="issued-date">{{ $issuedAt->format('d/m/Y H:i') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="summary">
        <tr>
            <td class="total">
                <span class="label">Total em Fila</span>
                <span class="value">{{ $totals['total'] }}</span>
            </td>
            <td class="available">
                <span class="label">Disponíveis</span>
                <span class="value">{{ $totals['available'] }}</span>
            </td>
            <td class="busy">
                <span class="label">Em Atendimento</span>
                <span class="value">{{ $totals['busy'] }}</span>
            </td>
            <td class="absent">
                <span class="label">Ausentes</span>
                <span class="value">{{ $totals['absent'] }}</span>
            </td>
        </tr>
    </table>

    @foreach($sections as $section)
        <div class="section-title">
            <div class="name">{{ $section['label'] }} <span class="sub">({{ $section['sublabel'] }})</span></div>
            <div class="stats">
                Total: {{ $section['total'] }} | Disponíveis: {{ $section['available'] }} | Em Atendimento: {{ $section['busy'] }} | Ausentes: {{ $section['absent'] }}
            </div>
        </div>

        @if($section['positions']->count() > 0)
            <table>
                <thead>
                    <tr>
                        <th class="col-pos">Pos.</th>
                        <th style="text-align: left;">Colaborador / Funcionário</th>
                        <th style="text-align: left;">Função / Cargos</th>
                        <th class="col-status">Status Atual</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($section['positions'] as $index => $position)
                        <tr>
                            <td class="col-pos">{{ $index + 1 }}º</td>
                            <td class="col-name">{{ $position->user?->name ?? '-' }}</td>
                            <td class="col-role">{{ $position->user?->roles->pluck('name')->join(', ') ?: $section['sublabel'] }}</td>
                            <td class="col-status">
                                <span class="badge badge-{{ $position->status }}">{{ $statusLabels[$position->status] ?? $position->status }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty-state">Nenhum colaborador registrado nesta fila.</div>
        @endif
    @endforeach

    <div class="footer">
        Manager 2.0 • Relatório Operacional da Linha de Atendimento • Gerado automaticamente
    </div>

</body>
</html>
