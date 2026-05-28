<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cortesias @isset($service) - {{ $service->client->nome ?? '' }} @endisset</title>
    <style>
        /* PDF specific styles to emulate A4 Paper and remove print browser defaults */
        :root {
            --paper-width: 210mm;
            --paper-height: 297mm;
        }

        @page {
            size: A4 portrait;
            margin: 20mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #fff;
            color: #000;
        }

        .pdf-container {
            width: 100%;
            max-width: var(--paper-width);
            margin: auto;
            position: relative;
        }

        /* Enforcing print bounds so the background color or shadow trickles correctly */
        @media print {
            body { 
                background-color: #fff;
            }
            .no-print {
                display: none !important;
            }
        }
        
        .header-info {
            text-align: right;
            font-size: 10px;
            color: #666;
            margin-bottom: 20px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }

        .content-area {
            word-wrap: break-word;
        }
        
        /* Enforce user's HTML payload standard sizing if not specified inline */
        .content-area img {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
    <div class="pdf-container">
        
        <div class="header-info no-print">
            Visualizador de Impressão • Motor Nativo PDF • {{ count($items) }} Cortesia(s)
        </div>

        @foreach($items as $item)
            <div class="content-area" style="{{ !$loop->last ? 'page-break-after: always;' : '' }}">
                {!! $item->processed_content !!}
            </div>
        @endforeach

    </div>

    <!-- The magic that automatically pops open the PDF export dialogue! -->
    <script>
        window.onload = function() {
            setTimeout(() => {
                window.print();
            }, 600); // Short delay to allow custom fonts or huge images to load in the browser memory before freezing for print.
        };
    </script>
</body>
</html>
