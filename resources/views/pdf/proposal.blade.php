<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proposta Comercial - {{ $service->client->nome ?? '' }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.5;
            margin: 0;
            padding: 0;
            background-color: #fff;
            color: #000;
            font-size: 13px;
        }

        .pdf-container {
            width: 100%;
            margin: auto;
        }

        .header-info {
            text-align: right;
            font-size: 9px;
            color: #888;
            margin-bottom: 20px;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .content-area {
            word-wrap: break-word;
        }
        
        .content-area img {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 10px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        th, td {
            border: 1px solid #eee;
            padding: 8px;
            text-align: left;
        }

        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="pdf-container">
        <div class="header-info no-print">
            Documento Gerado em {{ date('d/m/Y H:i') }} • Proposta #{{ str_pad($service->id, 5, '0', STR_PAD_LEFT) }}
        </div>

        <div class="content-area">
            {!! $content !!}
        </div>
    </div>

    <script>
        window.onload = function() {
            setTimeout(() => {
                window.print();
            }, 800);
        };
    </script>
</body>
</html>
