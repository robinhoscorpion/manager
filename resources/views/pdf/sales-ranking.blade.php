<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Ranking de Vendas</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            font-size: 11px;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #00c689;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            color: #1a202c;
            font-size: 24px;
            text-transform: uppercase;
        }
        .header p {
            margin: 5px 0 0 0;
            color: #718096;
            font-size: 12px;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #2d3748;
            margin-top: 30px;
            margin-bottom: 10px;
            text-transform: uppercase;
            border-left: 4px solid #00c689;
            padding-left: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #e2e8f0;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f7fafc;
            color: #4a5568;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
        }
        .col-name {
            text-align: left;
            width: 25%;
            font-weight: bold;
        }
        .highlight-col {
            background-color: #f0fff4;
            color: #22543d;
            font-weight: bold;
        }
        .badge {
            display: inline-block;
            width: 18px;
            height: 18px;
            line-height: 18px;
            border-radius: 50%;
            text-align: center;
            color: #fff;
            font-size: 10px;
            margin-right: 5px;
        }
        .rank-1 { background-color: #ecc94b; color: #744210; }
        .rank-2 { background-color: #e2e8f0; color: #4a5568; }
        .rank-3 { background-color: #fbd38d; color: #7b341e; }
        .rank-other { background-color: #edf2f7; color: #718096; }
        
        .empty-state {
            text-align: center;
            padding: 20px;
            color: #a0aec0;
            font-style: italic;
            border: 1px dashed #cbd5e0;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Ranking de Vendas</h1>
        <p>Resumo Mensal da Equipe • Período: {{ $rankingData['period'] }}</p>
    </div>

    <!-- PROMOTORES -->
    <div class="section-title">Ranking de Promotores</div>
    @if(count($rankingData['opcs']) > 0)
        <table>
            <thead>
                <tr>
                    <th class="col-name">Promotor</th>
                    <th>Show</th>
                    @foreach($rankingData['active_qualifications'] as $qual)
                        <th>{{ $qual['code'] }}</th>
                    @endforeach
                    <th class="highlight-col">Vendidos</th>
                    <th>% Aprov.</th>
                    <th>Volume Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rankingData['opcs'] as $index => $item)
                    <tr>
                        <td class="col-name">
                            <span class="badge {{ $index == 0 ? 'rank-1' : ($index == 1 ? 'rank-2' : ($index == 2 ? 'rank-3' : 'rank-other')) }}">{{ $index + 1 }}</span>
                            {{ $item['name'] }}
                        </td>
                        <td>{{ $item['qualificacao']['show'] }}</td>
                        @foreach($rankingData['active_qualifications'] as $qual)
                            <td>{{ $item['qualificacao'][$qual['code']] ?? 0 }}</td>
                        @endforeach
                        <td class="highlight-col">{{ $item['vendidos'] }}</td>
                        <td>{{ number_format($item['aproveitamento'], 2, ',', '.') }}%</td>
                        <td>R$ {{ number_format($item['total'], 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty-state">Nenhum promotor pontuou neste período.</div>
    @endif

    <!-- CONSULTORES -->
    <div class="section-title">Ranking de Consultores</div>
    @if(count($rankingData['liners']) > 0)
        <table>
            <thead>
                <tr>
                    <th class="col-name">Consultor</th>
                    <th>Show</th>
                    @foreach($rankingData['active_qualifications'] as $qual)
                        <th>{{ $qual['code'] }}</th>
                    @endforeach
                    <th class="highlight-col">Vendidos</th>
                    <th>% Aprov.</th>
                    <th>Volume Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rankingData['liners'] as $index => $item)
                    <tr>
                        <td class="col-name">
                            <span class="badge {{ $index == 0 ? 'rank-1' : ($index == 1 ? 'rank-2' : ($index == 2 ? 'rank-3' : 'rank-other')) }}">{{ $index + 1 }}</span>
                            {{ $item['name'] }}
                        </td>
                        <td>{{ $item['qualificacao']['show'] }}</td>
                        @foreach($rankingData['active_qualifications'] as $qual)
                            <td>{{ $item['qualificacao'][$qual['code']] ?? 0 }}</td>
                        @endforeach
                        <td class="highlight-col">{{ $item['vendidos'] }}</td>
                        <td>{{ number_format($item['aproveitamento'], 2, ',', '.') }}%</td>
                        <td>R$ {{ number_format($item['total'], 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty-state">Nenhum consultor pontuou neste período.</div>
    @endif

    <!-- SUPERVISORES -->
    <div class="section-title">Ranking de Supervisores</div>
    @if(count($rankingData['closers']) > 0)
        <table>
            <thead>
                <tr>
                    <th class="col-name">Supervisor</th>
                    <th>Show</th>
                    @foreach($rankingData['active_qualifications'] as $qual)
                        <th>{{ $qual['code'] }}</th>
                    @endforeach
                    <th class="highlight-col">Vendidos</th>
                    <th>% Aprov.</th>
                    <th>Volume Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rankingData['closers'] as $index => $item)
                    <tr>
                        <td class="col-name">
                            <span class="badge {{ $index == 0 ? 'rank-1' : ($index == 1 ? 'rank-2' : ($index == 2 ? 'rank-3' : 'rank-other')) }}">{{ $index + 1 }}</span>
                            {{ $item['name'] }}
                        </td>
                        <td>{{ $item['qualificacao']['show'] }}</td>
                        @foreach($rankingData['active_qualifications'] as $qual)
                            <td>{{ $item['qualificacao'][$qual['code']] ?? 0 }}</td>
                        @endforeach
                        <td class="highlight-col">{{ $item['vendidos'] }}</td>
                        <td>{{ number_format($item['aproveitamento'], 2, ',', '.') }}%</td>
                        <td>R$ {{ number_format($item['total'], 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty-state">Nenhum supervisor pontuou neste período.</div>
    @endif

</body>
</html>
