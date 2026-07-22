<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha de Atendimento</title>
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
        @media print {
            body { background: none; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="container">
        {!! $html !!}
    </div>

    <script>
        window.onload = function() {
            setTimeout(() => { window.print(); }, 500);
        };
    </script>
</body>
</html>
