<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ComplimentaryItem;
use Illuminate\Support\Facades\DB;

class ComplimentaryItemsSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        ComplimentaryItem::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $item = new ComplimentaryItem();
        $item->name = 'ENSAIO DE FOTOS';
        $item->code = 'aten';
        $item->template_type = 'html';
        $item->metadata = '';
        $item->content = <<<'HTML'
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Cortesia - Formato Ofício A4 (Inline)</title>
    <style>
        /* Apenas reset mínimo e regras de impressão - o estilo visual principal é inline */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background-color: #e6e9ef;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 24px 16px;
        }
        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
            }
            @page {
                size: A4;
                margin: 0mm;
            }
        }
        @media (max-width: 220mm) {
            .a4-paper-inline {
                width: 95% !important;
                padding: 15mm !important;
            }
        }
    </style>
</head>
<body>
    <div class="a4-paper-inline" style="width: 210mm; min-height: 297mm; background: white; box-shadow: 0 12px 30px rgba(0,0,0,0.15); margin: 0 auto; padding: 20mm 18mm 25mm 18mm; position: relative; border-radius: 2px; page-break-after: avoid; break-inside: avoid;">
        <div style="display: flex; flex-direction: column; height: 100%; font-size: 12pt; line-height: 1.5; color: #1e2a3e; font-family: 'Segoe UI', 'Roboto', 'Times New Roman', Georgia, serif;">
            
            <!-- Cabeçalho -->
            <div style="text-align: center; margin-bottom: 20px; border-bottom: 2px solid #2c3e66; padding-bottom: 12px;">
                <div style="font-size: 22pt; font-weight: bold; letter-spacing: 1px; color: #1f3a5f; font-family: 'Georgia', 'Times New Roman', serif;">MINISTÉRIO DA CORTESIA E RELAÇÕES HUMANAS</div>
                <div style="font-size: 11pt; color: #3a5a7a; margin-top: 4px; font-weight: 500;">Gabinete de Excelência e Atendimento Respeitoso</div>
                <div style="font-size: 16pt; font-weight: 600; margin-top: 6px; text-transform: uppercase; color: #2c3e66; letter-spacing: 2px;">Nota de Cortesia Oficial</div>
            </div>

            <!-- Referência / código do documento -->
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #bdc4d0; padding-bottom: 8px; margin-bottom: 28px; font-size: 11pt; font-weight: 500; color: #2c3e66;">
                <span style="text-align: left;">Nº 002/2025 - GAB/CORT</span>
                <span style="text-align: right;">Processo: 12345.000678/2025-99</span>
            </div>

            <!-- Local e data -->
            <div style="text-align: right; margin-bottom: 28px; font-size: 12pt;">
                Brasília - DF, 28 de março de 2026.
            </div>

            <!-- Destinatário -->
            <div style="margin-bottom: 28px; line-height: 1.5;">
                <p style="margin: 3px 0;"><strong style="font-weight: 700;">Ilustríssimo(a) Senhor(a)</strong></p>
                <p style="margin: 3px 0;"><strong style="font-weight: 700;">Dr(a). Rodrigo Almeida Mendes</strong></p>
                <p style="margin: 3px 0;">Coordenador do Comitê de Integração e Boas Práticas</p>
                <p style="margin: 3px 0;">Av. das Nações Unidas, 1440 – Sala 512</p>
                <p style="margin: 3px 0;">CEP: 01000-000 – São Paulo/SP</p>
                <div style="font-weight: 600; margin-top: 12px; margin-bottom: 8px;">Prezado(a) Dr(a). Mendes,</div>
            </div>

            <!-- Assunto / referência rápida -->
            <div style="margin: 18px 0 8px 0; font-weight: 600; border-left: 4px solid #8aa3c0; padding-left: 12px; color: #1f3a5f;">
                Assunto: Manifestação de apreço e colaboração institucional
            </div>

            <!-- Corpo da carta: texto de cortesia completo e formal -->
            <div style="text-align: justify; margin-bottom: 28px; text-indent: 3em;">
                <p style="margin-bottom: 12px;">Com nossos cordiais cumprimentos, servimo-nos do presente para externar os mais elevados votos de estima e consideração, bem como para registrar formalmente nosso reconhecimento pela dedicada atuação de Vossa Senhoria e de toda a equipe sob sua liderança, na promoção de um ambiente de trabalho pautado pelo respeito, empatia e excelência.</p>
                <p style="margin-bottom: 12px;">Sabemos que a rotina institucional, por vezes marcada por desafios e prazos exíguos, demanda não apenas competência técnica, mas também sensibilidade humana. Neste sentido, o trabalho desenvolvido pelo Comitê de Integração e Boas Práticas tem se revelado exemplo inspirador para os demais setores da administração pública e privada, reforçando valores essenciais à convivência harmoniosa e ao progresso coletivo.</p>
                <p style="margin-bottom: 12px;">É com satisfação que reconhecemos publicamente as iniciativas coordenadas por Vossa Senhoria, incluindo as campanhas de acolhimento, a mediação construtiva de conflitos e a implantação de canais de escuta ativa. Tais ações transcendem o mero cumprimento normativo, constituindo verdadeiro diferencial ético que fortalece a credibilidade e a confiança na gestão contemporânea.</p>
                <p style="margin-bottom: 12px;">Desejamos, portanto, expressar nossa inteira disposição para colaborar com eventuais projetos conjuntos que visem ampliar as práticas de cortesia e humanização no serviço público. Estamos certos de que o diálogo permanente entre nossas equipes renderá frutos significativos para a sociedade como um todo.</p>
                <p style="margin-bottom: 12px;">Reiteramos nossos protestos de elevada estima e consideração, colocando-nos à disposição para quaisquer esclarecimentos ou articulações que se façam necessárias.</p>
            </div>

            <!-- Fechamento e assinatura -->
            <div style="margin-top: 32px; margin-bottom: 40px; text-align: left;">
                <p style="margin: 0;">Atenciosamente,</p>
            </div>
            <div style="margin-top: 40px; text-align: center;">
                <div style="margin-top: 48px; border-top: 1px solid #000; width: 220px; margin-left: auto; margin-right: auto; text-align: center; padding-top: 8px; font-weight: 500;">
                    <div style="font-weight: bold; font-size: 12pt;">Profa. Dra. Ana Beatriz Montenegro</div>
                    <div style="font-size: 11pt; color: #2c3e66;">Ministra de Estado da Cortesia e Relações Humanas</div>
                    <div style="font-size: 9pt; margin-top: 5px;">Portaria nº 891/2024 • Gestão 2024-2026</div>
                </div>
            </div>

            <!-- Rodapé: informações de contato e selo de cortesia -->
            <div style="margin-top: auto; border-top: 1px solid #dce2ec; padding-top: 14px; font-size: 9pt; text-align: center; color: #4a627a;">
                <span>Ministério da Cortesia e Relações Humanas – Esplanada dos Ministérios, Bloco O – Sala 215 • Brasília/DF • CEP: 70150-000</span><br>
                <span>Tel.: (61) 3411-9000 • E-mail: cortesia@mcrh.gov.br • www.mcrh.gov.br/cortesia</span><br>
                <span style="font-style: italic;">"Cortesia gera reciprocidade: um gesto respeitoso transforma relações."</span>
            </div>
        </div>
    </div>

    <!-- Instrução sutil para impressão -->
    <div style="display: flex; justify-content: center; margin-top: 16px; font-family: 'Segoe UI', sans-serif; gap: 20px; font-size: 12px; color: #2c3e66; background: #f4f7fc; padding: 8px 20px; border-radius: 40px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <span>📄 Formato A4 (ofício) • Cortesia institucional</span>
        <span>🖨️ Use <strong>Ctrl+P</strong> ou menu "Imprimir" → Salvar como PDF para dimensões exatas</span>
    </div>
</body>
</html>
HTML;
        $item->save();

        $item = new ComplimentaryItem();
        $item->name = 'Teste';
        $item->code = 'teste';
        $item->template_type = 'html';
        $item->metadata = '';
        $item->content = <<<'HTML'
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Cortesia - Formato Ofício A4 (Inline)</title>
    <style>
        /* Apenas reset mínimo e regras de impressão - o estilo visual principal é inline */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background-color: #e6e9ef;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 24px 16px;
        }
        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
            }
            @page {
                size: A4;
                margin: 0mm;
            }
        }
        @media (max-width: 220mm) {
            .a4-paper-inline {
                width: 95% !important;
                padding: 15mm !important;
            }
        }
    </style>
</head>
<body>
    <div class="a4-paper-inline" style="width: 210mm; min-height: 297mm; background: white; box-shadow: 0 12px 30px rgba(0,0,0,0.15); margin: 0 auto; padding: 20mm 18mm 25mm 18mm; position: relative; border-radius: 2px; page-break-after: avoid; break-inside: avoid;">
        <div style="display: flex; flex-direction: column; height: 100%; font-size: 12pt; line-height: 1.5; color: #1e2a3e; font-family: 'Segoe UI', 'Roboto', 'Times New Roman', Georgia, serif;">
            
            <!-- Cabeçalho -->
            <div style="text-align: center; margin-bottom: 20px; border-bottom: 2px solid #2c3e66; padding-bottom: 12px;">
                <div style="font-size: 22pt; font-weight: bold; letter-spacing: 1px; color: #1f3a5f; font-family: 'Georgia', 'Times New Roman', serif;">MINISTÉRIO DA CORTESIA E RELAÇÕES HUMANAS</div>
                <div style="font-size: 11pt; color: #3a5a7a; margin-top: 4px; font-weight: 500;">Gabinete de Excelência e Atendimento Respeitoso</div>
                <div style="font-size: 16pt; font-weight: 600; margin-top: 6px; text-transform: uppercase; color: #2c3e66; letter-spacing: 2px;">Nota de Cortesia Oficial</div>
            </div>

            <!-- Referência / código do documento -->
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #bdc4d0; padding-bottom: 8px; margin-bottom: 28px; font-size: 11pt; font-weight: 500; color: #2c3e66;">
                <span style="text-align: left;">Nº 002/2025 - GAB/CORT</span>
                <span style="text-align: right;">Processo: 12345.000678/2025-99</span>
            </div>

            <!-- Local e data -->
            <div style="text-align: right; margin-bottom: 28px; font-size: 12pt;">
                Brasília - DF, 28 de março de 2026.
            </div>

            <!-- Destinatário -->
            <div style="margin-bottom: 28px; line-height: 1.5;">
                <p style="margin: 3px 0;"><strong style="font-weight: 700;">Ilustríssimo(a) Senhor(a)</strong></p>
                <p style="margin: 3px 0;"><strong style="font-weight: 700;">Dr(a). Rodrigo Almeida Mendes</strong></p>
                <p style="margin: 3px 0;">Coordenador do Comitê de Integração e Boas Práticas</p>
                <p style="margin: 3px 0;">Av. das Nações Unidas, 1440 – Sala 512</p>
                <p style="margin: 3px 0;">CEP: 01000-000 – São Paulo/SP</p>
                <div style="font-weight: 600; margin-top: 12px; margin-bottom: 8px;">Prezado(a) Dr(a). Mendes,</div>
            </div>

            <!-- Assunto / referência rápida -->
            <div style="margin: 18px 0 8px 0; font-weight: 600; border-left: 4px solid #8aa3c0; padding-left: 12px; color: #1f3a5f;">
                Assunto: Manifestação de apreço e colaboração institucional
            </div>

            <!-- Corpo da carta: texto de cortesia completo e formal -->
            <div style="text-align: justify; margin-bottom: 28px; text-indent: 3em;">
                <p style="margin-bottom: 12px;">Com nossos cordiais cumprimentos, servimo-nos do presente para externar os mais elevados votos de estima e consideração, bem como para registrar formalmente nosso reconhecimento pela dedicada atuação de Vossa Senhoria e de toda a equipe sob sua liderança, na promoção de um ambiente de trabalho pautado pelo respeito, empatia e excelência.</p>
                <p style="margin-bottom: 12px;">Sabemos que a rotina institucional, por vezes marcada por desafios e prazos exíguos, demanda não apenas competência técnica, mas também sensibilidade humana. Neste sentido, o trabalho desenvolvido pelo Comitê de Integração e Boas Práticas tem se revelado exemplo inspirador para os demais setores da administração pública e privada, reforçando valores essenciais à convivência harmoniosa e ao progresso coletivo.</p>
                <p style="margin-bottom: 12px;">É com satisfação que reconhecemos publicamente as iniciativas coordenadas por Vossa Senhoria, incluindo as campanhas de acolhimento, a mediação construtiva de conflitos e a implantação de canais de escuta ativa. Tais ações transcendem o mero cumprimento normativo, constituindo verdadeiro diferencial ético que fortalece a credibilidade e a confiança na gestão contemporânea.</p>
                <p style="margin-bottom: 12px;">Desejamos, portanto, expressar nossa inteira disposição para colaborar com eventuais projetos conjuntos que visem ampliar as práticas de cortesia e humanização no serviço público. Estamos certos de que o diálogo permanente entre nossas equipes renderá frutos significativos para a sociedade como um todo.</p>
                <p style="margin-bottom: 12px;">Reiteramos nossos protestos de elevada estima e consideração, colocando-nos à disposição para quaisquer esclarecimentos ou articulações que se façam necessárias.</p>
            </div>

            <!-- Fechamento e assinatura -->
            <div style="margin-top: 32px; margin-bottom: 40px; text-align: left;">
                <p style="margin: 0;">Atenciosamente,</p>
            </div>
            <div style="margin-top: 40px; text-align: center;">
                <div style="margin-top: 48px; border-top: 1px solid #000; width: 220px; margin-left: auto; margin-right: auto; text-align: center; padding-top: 8px; font-weight: 500;">
                    <div style="font-weight: bold; font-size: 12pt;">Profa. Dra. Ana Beatriz Montenegro</div>
                    <div style="font-size: 11pt; color: #2c3e66;">Ministra de Estado da Cortesia e Relações Humanas</div>
                    <div style="font-size: 9pt; margin-top: 5px;">Portaria nº 891/2024 • Gestão 2024-2026</div>
                </div>
            </div>

            <!-- Rodapé: informações de contato e selo de cortesia -->
            <div style="margin-top: auto; border-top: 1px solid #dce2ec; padding-top: 14px; font-size: 9pt; text-align: center; color: #4a627a;">
                <span>Ministério da Cortesia e Relações Humanas – Esplanada dos Ministérios, Bloco O – Sala 215 • Brasília/DF • CEP: 70150-000</span><br>
                <span>Tel.: (61) 3411-9000 • E-mail: cortesia@mcrh.gov.br • www.mcrh.gov.br/cortesia</span><br>
                <span style="font-style: italic;">"Cortesia gera reciprocidade: um gesto respeitoso transforma relações."</span>
            </div>
        </div>
    </div>

    <!-- Instrução sutil para impressão -->
    <div style="display: flex; justify-content: center; margin-top: 16px; font-family: 'Segoe UI', sans-serif; gap: 20px; font-size: 12px; color: #2c3e66; background: #f4f7fc; padding: 8px 20px; border-radius: 40px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <span>📄 Formato A4 (ofício) • Cortesia institucional</span>
        <span>🖨️ Use <strong>Ctrl+P</strong> ou menu "Imprimir" → Salvar como PDF para dimensões exatas</span>
    </div>
</body>
</html>
HTML;
        $item->save();

        $item = new ComplimentaryItem();
        $item->name = 'CASINHA';
        $item->code = 'CASINHA';
        $item->template_type = 'html';
        $item->metadata = '';
        $item->content = <<<'HTML'
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cortesia Personalizada - 2 Cópias por Folha A4</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #e0e4ec;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
        }

        /* Painel de controle - personalização */
        .control-panel {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            padding: 20px 28px;
            margin-bottom: 24px;
            width: 100%;
            max-width: 900px;
        }

        .control-panel h2 {
            font-size: 1.4rem;
            color: #1f3a5f;
            margin-bottom: 16px;
            border-left: 4px solid #2c3e66;
            padding-left: 12px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 16px 24px;
        }

        .input-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .input-group label {
            font-weight: 600;
            font-size: 0.8rem;
            color: #2c3e66;
            letter-spacing: 0.3px;
        }

        .input-group input, .input-group textarea {
            padding: 8px 12px;
            border: 1px solid #bdc4d0;
            border-radius: 8px;
            font-size: 0.85rem;
            font-family: inherit;
            transition: 0.2s;
        }

        .input-group input:focus, .input-group textarea:focus {
            outline: none;
            border-color: #2c3e66;
            box-shadow: 0 0 0 2px rgba(44,62,102,0.2);
        }

        .input-group textarea {
            resize: vertical;
            min-height: 60px;
        }

        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            justify-content: flex-end;
        }

        button {
            background: #2c3e66;
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 40px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            font-size: 0.85rem;
        }

        button:hover {
            background: #1f2c48;
            transform: scale(0.98);
        }

        button.print-btn {
            background: #1f6e43;
        }

        button.print-btn:hover {
            background: #155a38;
        }

        button.reset-btn {
            background: #8a6e3b;
        }

        /* Folha A4 com duas cópias lado a lado */
        .a4-sheet {
            width: 210mm;
            background: white;
            box-shadow: 0 12px 30px rgba(0,0,0,0.2);
            margin: 0 auto 30px auto;
            border-radius: 4px;
            overflow: hidden;
        }

        /* Container das duas cópias: flex horizontal */
        .two-copies {
            display: flex;
            flex-direction: row;
            width: 100%;
            min-height: 297mm;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* Cada cortesia ocupa exatamente metade da largura */
        .courtesy-card {
            flex: 1;
            width: 50%;
            padding: 12mm 10mm 12mm 10mm;
            border-right: 1px dashed #ccd2df;
            position: relative;
            background: white;
            page-break-inside: avoid;
            break-inside: avoid;
            display: flex;
            flex-direction: column;
        }

        /* A última cópia não tem borda direita */
        .courtesy-card:last-child {
            border-right: none;
        }

        /* Linha de corte sutil entre as cópias */
        .cut-marker {
            position: absolute;
            left: 50%;
            top: 10%;
            transform: translateX(-50%);
            font-size: 8px;
            color: #aaa;
            writing-mode: horizontal-tb;
            background: #f8f9fc;
            padding: 2px 6px;
            border-radius: 20px;
            font-family: monospace;
            pointer-events: none;
            z-index: 2;
        }

        /* Estilos internos da cortesia */
        .cort-header {
            text-align: center;
            margin-bottom: 12px;
            border-bottom: 1px solid #2c3e66;
            padding-bottom: 8px;
        }
        .org-name {
            font-size: 14pt;
            font-weight: bold;
            color: #1f3a5f;
            font-family: 'Georgia', serif;
        }
        .doc-type {
            font-size: 10pt;
            font-weight: 600;
            text-transform: uppercase;
            color: #2c3e66;
            letter-spacing: 1px;
            margin-top: 4px;
        }
        .ref-line {
            display: flex;
            justify-content: space-between;
            font-size: 8pt;
            border-bottom: 1px solid #e2e6ef;
            padding-bottom: 4px;
            margin-bottom: 12px;
            color: #3a5a7a;
        }
        .place-date {
            text-align: right;
            font-size: 9pt;
            margin-bottom: 12px;
        }
        .recipient {
            margin-bottom: 12px;
            font-size: 9pt;
            line-height: 1.4;
        }
        .greeting {
            font-weight: 600;
            margin: 6px 0 4px;
        }
        .assunto {
            font-weight: 600;
            font-size: 9pt;
            border-left: 3px solid #8aa3c0;
            padding-left: 8px;
            margin: 10px 0 8px;
            color: #1f3a5f;
        }
        .body-text {
            text-align: justify;
            font-size: 9pt;
            line-height: 1.45;
            margin-bottom: 14px;
            flex: 1;
        }
        .body-text p {
            margin-bottom: 8px;
            text-indent: 2em;
        }
        .closing {
            margin: 12px 0 4px;
            font-size: 9pt;
        }
        .signature-area {
            margin-top: 16px;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 160px;
            margin: 16px auto 4px auto;
            padding-top: 4px;
        }
        .signature-name {
            font-weight: bold;
            font-size: 9pt;
        }
        .signature-title {
            font-size: 8pt;
            color: #2c3e66;
        }
        .footer-note {
            font-size: 7pt;
            text-align: center;
            color: #6c7a8e;
            border-top: 1px solid #e2e6ef;
            margin-top: 12px;
            padding-top: 8px;
        }

        /* Responsividade e impressão */
        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
            }
            .control-panel, .button-group, .print-btn, .reset-btn, .cut-marker {
                display: none;
            }
            .a4-sheet {
                box-shadow: none;
                margin: 0;
                width: 100%;
            }
            .two-copies {
                min-height: 0;
            }
            .courtesy-card {
                border-right: 1px dashed #ccc !important;
            }
            @page {
                size: A4;
                margin: 0mm;
            }
        }

        @media (max-width: 220mm) {
            .a4-sheet {
                width: 100%;
                transform: scale(0.98);
            }
        }
    </style>
</head>
<body>

<div class="control-panel">
    <h2>📝 Personalizar Cortesia (duas cópias idênticas por folha)</h2>
    <div class="form-grid">
        <div class="input-group">
            <label>🏢 Nome da Instituição / Órgão</label>
            <input type="text" id="orgName" value="MINISTÉRIO DA CORTESIA E RELAÇÕES HUMANAS">
        </div>
        <div class="input-group">
            <label>📄 Tipo do Documento</label>
            <input type="text" id="docType" value="Nota de Cortesia Oficial">
        </div>
        <div class="input-group">
            <label>🔢 Número / Referência</label>
            <input type="text" id="refNumber" value="Nº 045/2026 - GAB/CORT">
        </div>
        <div class="input-group">
            <label>📅 Cidade e Data</label>
            <input type="text" id="placeDate" value="Brasília - DF, 31 de março de 2026.">
        </div>
        <div class="input-group">
            <label>👤 Nome do Destinatário</label>
            <input type="text" id="recipientName" value="Dr(a). Fernando Augusto Lima">
        </div>
        <div class="input-group">
            <label>💼 Cargo / Setor do Destinatário</label>
            <input type="text" id="recipientTitle" value="Coordenador de Relações Institucionais">
        </div>
        <div class="input-group">
            <label>📍 Endereço do Destinatário</label>
            <input type="text" id="recipientAddress" value="Av. Paulista, 1000 - Conj. 810 - Bela Vista, São Paulo/SP - CEP: 01310-100">
        </div>
        <div class="input-group">
            <label>✉️ Assunto / Título</label>
            <input type="text" id="subjectLine" value="Manifestação de apreço e colaboração institucional">
        </div>
        <div class="input-group">
            <label>📝 Texto da Cortesia (personalizado)</label>
            <textarea id="customMessage" rows="3">Com nossos cordiais cumprimentos, servimo-nos do presente para externar os mais elevados votos de estima e consideração, bem como para registrar formalmente nosso reconhecimento pela dedicada atuação de Vossa Senhoria.

Sabemos que a rotina institucional demanda não apenas competência técnica, mas também sensibilidade humana. O trabalho que tem sido desenvolvido é exemplo inspirador.

É com satisfação que reconhecemos publicamente as iniciativas lideradas por Vossa Senhoria, reafirmando nossa disposição para colaborar em projetos futuros.

Reiteramos nossos protestos de elevada estima e consideração.</textarea>
        </div>
        <div class="input-group">
            <label>🖋️ Nome do Assinante</label>
            <input type="text" id="signerName" value="Profa. Dra. Ana Beatriz Montenegro">
        </div>
        <div class="input-group">
            <label>📌 Cargo do Assinante</label>
            <input type="text" id="signerTitle" value="Ministra de Estado da Cortesia e Relações Humanas">
        </div>
    </div>
    <div class="button-group">
        <button class="reset-btn" id="resetBtn">↺ Restaurar Padrão</button>
        <button class="print-btn" id="printBtn">🖨️ Imprimir / Salvar PDF (2 cópias)</button>
    </div>
</div>

<!-- Folha A4 com DUAS CÓPIAS lado a lado -->
<div class="a4-sheet" id="a4Sheet">
    <div class="two-copies">
        <!-- CÓPIA 1 (esquerda) -->
        <div class="courtesy-card" id="copy1">
            <!-- conteúdo será preenchido via js -->
        </div>
        <!-- CÓPIA 2 (direita) -->
        <div class="courtesy-card" id="copy2">
            <!-- conteúdo será preenchido via js -->
        </div>
    </div>
</div>

<script>
    // Elementos do formulário
    const orgNameInput = document.getElementById('orgName');
    const docTypeInput = document.getElementById('docType');
    const refNumberInput = document.getElementById('refNumber');
    const placeDateInput = document.getElementById('placeDate');
    const recipientNameInput = document.getElementById('recipientName');
    const recipientTitleInput = document.getElementById('recipientTitle');
    const recipientAddressInput = document.getElementById('recipientAddress');
    const subjectLineInput = document.getElementById('subjectLine');
    const customMessageInput = document.getElementById('customMessage');
    const signerNameInput = document.getElementById('signerName');
    const signerTitleInput = document.getElementById('signerTitle');

    const copy1Div = document.getElementById('copy1');
    const copy2Div = document.getElementById('copy2');

    // Função que gera o HTML interno de uma cortesia com os dados atuais
    function generateCourtesyHTML() {
        const orgName = orgNameInput.value.trim() || "INSTITUIÇÃO DE CORTESIA";
        const docType = docTypeInput.value.trim() || "Documento Oficial";
        const refNumber = refNumberInput.value.trim() || "Nº 001/2026";
        const placeDate = placeDateInput.value.trim() || "Cidade, 00 de mês de 2026.";
        const recipientName = recipientNameInput.value.trim() || "Ilustríssimo(a) destinatário(a)";
        const recipientTitle = recipientTitleInput.value.trim() || "Cargo / Função";
        const recipientAddress = recipientAddressInput.value.trim() || "Endereço completo, CEP";
        const subjectLine = subjectLineInput.value.trim() || "Assunto institucional";
        let customMessage = customMessageInput.value.trim();
        if (!customMessage) {
            customMessage = "É com grande estima que apresentamos nossas saudações. Este é um espaço para mensagem personalizada de cortesia, reconhecimento ou colaboração. Acreditamos que a gentileza e o respeito constroem pontes duradouras entre instituições e pessoas.";
        }
        const signerName = signerNameInput.value.trim() || "Nome do Responsável";
        const signerTitle = signerTitleInput.value.trim() || "Cargo Institucional";

        // Processar parágrafos: quebrar o texto em <p> baseado em linhas duplas, mas manter estrutura
        const paragraphs = customMessage.split(/\n\s*\n/);
        let bodyParagraphs = '';
        for (let para of paragraphs) {
            if (para.trim() !== '') {
                // substituir quebras de linha simples por espaço, mantendo texto contínuo
                let cleanPara = para.replace(/\n/g, ' ').trim();
                if (cleanPara) {
                    bodyParagraphs += `<p>${cleanPara}</p>`;
                }
            }
        }
        // Se não houver parágrafos válidos, usar fallback
        if (!bodyParagraphs) {
            bodyParagraphs = `<p>${customMessage.replace(/\n/g, ' ')}</p>`;
        }

        return `
            <div class="cort-header">
                <div class="org-name">${escapeHtml(orgName)}</div>
                <div class="doc-type">${escapeHtml(docType)}</div>
            </div>
            <div class="ref-line">
                <span>${escapeHtml(refNumber)}</span>
                <span>Processo: 98765.001234/2026-11</span>
            </div>
            <div class="place-date">
                ${escapeHtml(placeDate)}
            </div>
            <div class="recipient">
                <p><strong>Ilustríssimo(a) Senhor(a)</strong></p>
                <p><strong>[NOME_TITULAR]</strong></p>
                <p>${escapeHtml(recipientTitle)}</p>
                <p>${escapeHtml(recipientAddress)}</p>
                <div class="greeting">Prezado(a) ${escapeHtml(recipientName.split(' ')[0])},</div>
            </div>
            <div class="assunto">
                Assunto: ${escapeHtml(subjectLine)}
            </div>
            <div class="body-text">
                ${bodyParagraphs}
            </div>
            <div class="closing">
                <p>Atenciosamente,</p>
            </div>
            <div class="signature-area">
                <div class="signature-line"></div>
                <div class="signature-name">${escapeHtml(signerName)}</div>
                <div class="signature-title">${escapeHtml(signerTitle)}</div>
                <div style="font-size: 7pt; margin-top: 5px;">Documento de cortesia • Registro interno</div>
            </div>
            <div class="footer-note">
                ${escapeHtml(orgName)} • Compromisso com o respeito e a valorização humana<br>
                E-mail: cortesia@exemplo.gov.br • Tel.: (61) 3000-0000
            </div>
        `;
    }

    // Função auxiliar para evitar XSS simples
    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        }).replace(/[\uD800-\uDBFF][\uDC00-\uDFFF]/g, function(c) {
            return c;
        });
    }

    // Atualizar ambas as cópias com o mesmo conteúdo
    function updateBothCopies() {
        const htmlContent = generateCourtesyHTML();
        copy1Div.innerHTML = htmlContent;
        copy2Div.innerHTML = htmlContent;
    }

    // Restaurar valores padrão (personalização inicial)
    function resetToDefault() {
        orgNameInput.value = "MINISTÉRIO DA CORTESIA E RELAÇÕES HUMANAS";
        docTypeInput.value = "Nota de Cortesia Oficial";
        refNumberInput.value = "Nº 045/2026 - GAB/CORT";
        placeDateInput.value = "Brasília - DF, 31 de março de 2026.";
        recipientNameInput.value = "Dr(a). Fernando Augusto Lima";
        recipientTitleInput.value = "Coordenador de Relações Institucionais";
        recipientAddressInput.value = "Av. Paulista, 1000 - Conj. 810 - Bela Vista, São Paulo/SP - CEP: 01310-100";
        subjectLineInput.value = "Manifestação de apreço e colaboração institucional";
        customMessageInput.value = "Com nossos cordiais cumprimentos, servimo-nos do presente para externar os mais elevados votos de estima e consideração, bem como para registrar formalmente nosso reconhecimento pela dedicada atuação de Vossa Senhoria.\n\nSabemos que a rotina institucional demanda não apenas competência técnica, mas também sensibilidade humana. O trabalho que tem sido desenvolvido é exemplo inspirador.\n\nÉ com satisfação que reconhecemos publicamente as iniciativas lideradas por Vossa Senhoria, reafirmando nossa disposição para colaborar em projetos futuros.\n\nReiteramos nossos protestos de elevada estima e consideração.";
        signerNameInput.value = "Profa. Dra. Ana Beatriz Montenegro";
        signerTitleInput.value = "Ministra de Estado da Cortesia e Relações Humanas";
        updateBothCopies();
    }

    // Eventos de input em todos os campos para atualização dinâmica
    const inputs = [orgNameInput, docTypeInput, refNumberInput, placeDateInput, recipientNameInput, recipientTitleInput, recipientAddressInput, subjectLineInput, customMessageInput, signerNameInput, signerTitleInput];
    inputs.forEach(input => {
        input.addEventListener('input', updateBothCopies);
    });

    // Botão reset
    document.getElementById('resetBtn').addEventListener('click', resetToDefault);

    // Botão imprimir (imprime toda a folha A4 com as duas cópias)
    document.getElementById('printBtn').addEventListener('click', () => {
        window.print();
    });

    // Inicialização
    updateBothCopies();

    // Pequeno ajuste para garantir que na impressão as bordas laterais fiquem visíveis
    window.addEventListener('beforeprint', () => {
        // Nada adicional necessário, mas garantir que a folha seja respeitada
    });
</script>
</body>
</html>
HTML;
        $item->save();

        $item = new ComplimentaryItem();
        $item->name = 'Voucher Jantar - Itacaré';
        $item->code = 'VJ_ITAC';
        $item->template_type = 'html';
        $item->metadata = '';
        $item->content = <<<'HTML'
<style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap');

    :root {
        --text-dark: #000000;
        --green-primary: #5c7254;
        --gray-light: #666666;
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        font-family: 'Montserrat', sans-serif;
    }

    body {
        background-color: #e0e0e0;
        padding: 20px;
        display: flex;
        justify-content: center;
    }

    /* Contêiner principal com a imagem de fundo */
    .voucher-wrapper {
        width: 1000px;
        height: 654px; /* Ajuste a altura conforme a proporção real da sua imagem */
        background-image: url('/images/fundo-voucher-jantar.png');
        background-size: 100% 100%;
        background-repeat: no-repeat;
        background-position: center;
        position: relative;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        color: var(--text-dark);
        margin: 0 auto;
    }

    /* Divisões virtuais das metades */
    .via-cliente {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 50%;
    }

    .via-empresa {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 50%;
    }

    /* ================================
       VIA CLIENTE (PARTE DE CIMA)
    ================================ */

    /* Texto Central */
    .cliente-centro {
        position: absolute;
        top: 25px;
        left: 280px;
        width: 480px;
        text-align: center;
    }

    .titulo-voucher {
        font-size: 18px;
        font-weight: 600;
        letter-spacing: 2px;
        color: var(--green-primary);
        margin-top: 30px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .titulo-voucher::before, .titulo-voucher::after {
        content: "";
        display: inline-block;
        width: 40px;
        height: 1px;
        background-color: var(--gray-light);
        margin: 0 15px;
    }

    .texto-principal {
        font-size: 12px;
        line-height: 1.6;
        margin-bottom: 10px;
    }

    .obs-garcom {
        font-size: 11px;
        margin-bottom: 15px;
    }

    /* Assinaturas Cliente */
    .cliente-assinaturas {
        display: flex;
        justify-content: space-between;
        font-size: 11px;
        margin-bottom: 10px;
        text-align: left;
    }

    .linha-ass {
        border-bottom: 1px solid var(--text-dark);
        padding-bottom: 4px;
    }

    .ass-nome { width: 65%; }
    .ass-gerente { width: 30%; text-align: center; }

    .rodape-obs {
        font-size: 10px;
        text-align: left;
        line-height: 1.4;
    }

    /* Info Direita (QR e Via) */
    .cliente-direita {
        position: absolute;
        top: 20px;
        right: 40px;
        width: 180px;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
    }

    .qr-code-img {
        width: 85px;
        height: 85px;
        margin-bottom: 15px;
        margin-right: 15px;
    }

    .badge-via {
        background-color: var(--green-primary);
        color: white;
        padding: 4px 0;
        width: 100%;
        text-align: center;
        font-size: 11px;
        border-radius: 2px;
        margin-bottom: 15px;
    }

    .info-dados {
        font-size: 11px;
        width: 100%;
    }

    .info-dados div {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
    }

    /* Equipe de Vendas (Ambas as vias) */
    .equipe-vendas {
        position: absolute;
        bottom: 25px; /* Ajuste para encaixar antes da onda azul */
        left: 0;
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 30px;
        font-size: 11px;
        font-weight: 600;
    }
    
    /* Cor branca para a equipe na primeira via por causa do fundo azul escuro */
    .via-cliente .equipe-vendas {
        color: #000000;
        bottom: 15px; 
    }

    .equipe-item {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .icone-user {
        width: 18px;
        height: 18px;
        background-color: var(--green-primary);
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        color: white;
        font-size: 10px;
    }

    .divisor-v {
        width: 1px;
        height: 15px;
        background-color: #ccc;
    }

    /* ================================
       VIA EMPRESA (PARTE DE BAIXO)
    ================================ */

    .empresa-dados {
        position: absolute;
        top: 40px;
        left: 260px;
        width: 320px;
        font-size: 11px;
        line-height: 1.6;
    }

    .protocolo-titulo {
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 15px;
        color: var(--text-dark);
    }

    .dado-item {
        display: flex;
        gap: 8px;
        margin-bottom: 8px;
    }

    .empresa-direita {
        position: absolute;
        top: 20px;
        right: 40px;
        width: 320px;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
    }

    /* Caixa de Assinatura */
    .caixa-assinatura {
        width: 100%;
        border: 1px solid #999;
        border-radius: 8px;
        padding: 15px 20px;
        text-align: center;
        margin-top: 5px;
    }

    .caixa-assinatura h3 {
        font-size: 12px;
        color: var(--green-primary);
        margin-bottom: 8px;
    }

    .caixa-assinatura p {
        font-size: 10px;
        margin-bottom: 35px;
    }

    .linha-ass-caixa {
        border-bottom: 1px solid var(--text-dark);
        margin-bottom: 5px;
    }

    .label-ass {
        font-size: 9px;
        margin-bottom: 15px;
    }

    .caixa-rodape {
        display: flex;
        justify-content: space-between;
        font-size: 10px;
    }

    .caixa-rodape .nome { width: 55%; border-bottom: 1px solid var(--text-dark); text-align: left;}
    .caixa-rodape .data { width: 40%; border-bottom: 1px solid var(--text-dark); text-align: right;}

    @media print {
        @page {
            margin: 0; 
            size: A4 portrait;
        }
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            background-color: white !important;
            overflow-x: hidden !important;
            width: 100% !important;
        }
        .voucher-wrapper {
            margin: 0 !important;
            position: relative !important;
            left: 0 !important;
            transform: scale(0.793) !important;
            transform-origin: top left !important;
            box-shadow: none !important;
        }
    }
</style>

<div class="voucher-wrapper">
    
    <!-- ==================== 1ª VIA (CLIENTE) ==================== -->
    <div class="via-cliente">
        
        <div class="cliente-centro">
            <div class="titulo-voucher">VOUCHER JANTAR</div>
            <div class="texto-principal">
                O <strong>Itacaré Vacation Club</strong> vai querido proporcionar através desse voucher<br>
                um <strong>jantar exclusivo</strong> para ser utilizado no <strong>Restaurante Mandio - Resende Imperial</strong>,<br>
                em nome de: <strong>[NOME_TITULAR] e [NOME_CONJUGE]</strong>.
            </div>
            <div class="obs-garcom">OBS: Ao solicitar a conta, informe ao garçom sobre este voucher.</div>

            <div class="cliente-assinaturas">
                <div class="linha-ass ass-nome">Nome: [NOME_TITULAR]</div>
                <div class="linha-ass ass-gerente">GERENTE</div>
            </div>

            <div class="rodape-obs">
                <strong>OBS:</strong> Essa cortesia é pessoal e intransferível / Esse voucher deve ser entregue ao garçom no momento do pagamento da conta.<br>
                <strong>válido por 30 dias da data de emissão</strong>
            </div>
        </div>

        <div class="cliente-direita">
            <!-- QR Code Dinâmico com a Tag [QR_CODE] -->
            [QR_CODE]
            <div class="badge-via">1ª Via (Cliente)</div>
            <div class="info-dados">
                <div><strong>Data:</strong> <span>[DATA]</span></div>
                <div><strong>Nº:</strong> <span>CO-[ID_ATENDIMENTO]</span></div>
            </div>
        </div>

        <div class="equipe-vendas">
            <div class="equipe-item"><span class="icone-user">👤</span> Promotor: [PROMOTOR]</div>
            <div class="divisor-v"></div>
            <div class="equipe-item"><span class="icone-user">👤</span> Consultor: [CONSULTOR]</div>
            <div class="divisor-v"></div>
            <div class="equipe-item"><span class="icone-user">👤</span> Supervisor: [SUPERVISOR]</div>
        </div>

    </div>

    <!-- ==================== 2ª VIA (EMPRESA) ==================== -->
    <div class="via-empresa">
        
        <div class="empresa-dados">
            <div class="protocolo-titulo">PROTOCOLO Nº: CO-[ID_ATENDIMENTO]</div>
            <div class="dado-item">
                <span>📅</span> 
                <span><strong>Data:</strong> [DATA]</span>
            </div>
            <div class="dado-item">
                <span>👤</span> 
                <span><strong>Cliente:</strong> [NOME_TITULAR]<br>e [NOME_CONJUGE]</span>
            </div>
            <div class="dado-item">
                <span>🍴</span> 
                <span><strong>Cortesia:</strong> Jantar exclusivo</span>
            </div>
            <div class="dado-item">
                <span>📄</span> 
                <span><strong>Descrição:</strong> Jantar exclusivo para ser utilizado<br>no Restaurante Mandio - Resende Imperial</span>
            </div>
            <div class="dado-item">
                <span>👤</span> 
                <span><strong>CPF:</strong> [CPF]</span>
            </div>
        </div>

        <div class="empresa-direita">
            <div class="badge-via" style="width: 120px;">2ª Via (Empresa)</div>
            
            <div class="caixa-assinatura">
                <h3>ASSINATURA DO CLIENTE</h3>
                <p>Declaro que recebi a cortesia descrita acima.</p>
                <div class="linha-ass-caixa"></div>
                <div class="label-ass">Assinatura</div>
                
                <div class="caixa-rodape">
                    <div class="nome" style="text-align: center;">[NOME_CURTO]</div>
                    <div class="data" style="text-align: center;">[DATA_IMPRESSAO]</div>
                </div>
            </div>
        </div>

        <div class="equipe-vendas" style="bottom: 50px;">
            <div class="equipe-item"><span class="icone-user">👤</span> Promotor: [PROMOTOR]</div>
            <div class="divisor-v"></div>
            <div class="equipe-item"><span class="icone-user">👤</span> Consultor: [CONSULTOR]</div>
            <div class="divisor-v"></div>
            <div class="equipe-item"><span class="icone-user">👤</span> Supervisor: [SUPERVISOR]</div>
        </div>

    </div>

</div>
HTML;
        $item->save();

        $item = new ComplimentaryItem();
        $item->name = 'Fotos';
        $item->code = 'CORT-NEW-100';
        $item->template_type = 'html';
        $item->metadata = '';
        $item->content = <<<'HTML'
<style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap');

    :root {
        --text-dark: #000000;
        --green-primary: #5c7254;
        --gray-light: #666666;
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        font-family: 'Montserrat', sans-serif;
    }

    body {
        background-color: #e0e0e0;
        padding: 20px;
        display: flex;
        justify-content: center;
    }

    /* Contêiner principal com a imagem de fundo */
    .voucher-wrapper {
        width: 1000px;
        height: 654px; /* Ajuste a altura conforme a proporção real da sua imagem */
        background-image: url('/images/fundo-voucher-jantar.png');
        background-size: 100% 100%;
        background-repeat: no-repeat;
        background-position: center;
        position: relative;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        color: var(--text-dark);
        margin: 0 auto;
    }

    /* Divisões virtuais das metades */
    .via-cliente {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 50%;
    }

    .via-empresa {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 50%;
    }

    /* ================================
       VIA CLIENTE (PARTE DE CIMA)
    ================================ */

    /* Texto Central */
    .cliente-centro {
        position: absolute;
        top: 25px;
        left: 280px;
        width: 480px;
        text-align: center;
    }

    .titulo-voucher {
        font-size: 18px;
        font-weight: 600;
        letter-spacing: 2px;
        color: var(--green-primary);
        margin-top: 30px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .titulo-voucher::before, .titulo-voucher::after {
        content: "";
        display: inline-block;
        width: 40px;
        height: 1px;
        background-color: var(--gray-light);
        margin: 0 15px;
    }

    .texto-principal {
        font-size: 12px;
        line-height: 1.6;
        margin-bottom: 10px;
    }

    .obs-garcom {
        font-size: 11px;
        margin-bottom: 15px;
    }

    /* Assinaturas Cliente */
    .cliente-assinaturas {
        display: flex;
        justify-content: space-between;
        font-size: 11px;
        margin-bottom: 10px;
        text-align: left;
    }

    .linha-ass {
        border-bottom: 1px solid var(--text-dark);
        padding-bottom: 4px;
    }

    .ass-nome { width: 65%; }
    .ass-gerente { width: 30%; text-align: center; }

    .rodape-obs {
        font-size: 10px;
        text-align: left;
        line-height: 1.4;
    }

    /* Info Direita (QR e Via) */
    .cliente-direita {
        position: absolute;
        top: 20px;
        right: 40px;
        width: 180px;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
    }

    .qr-code-img {
        width: 85px;
        height: 85px;
        margin-bottom: 15px;
        margin-right: 15px;
    }

    .badge-via {
        background-color: var(--green-primary);
        color: white;
        padding: 4px 0;
        width: 100%;
        text-align: center;
        font-size: 11px;
        border-radius: 2px;
        margin-bottom: 15px;
    }

    .info-dados {
        font-size: 11px;
        width: 100%;
    }

    .info-dados div {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
    }

    /* Equipe de Vendas (Ambas as vias) */
    .equipe-vendas {
        position: absolute;
        bottom: 25px; /* Ajuste para encaixar antes da onda azul */
        left: 0;
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 30px;
        font-size: 11px;
        font-weight: 600;
    }
    
    /* Cor branca para a equipe na primeira via por causa do fundo azul escuro */
    .via-cliente .equipe-vendas {
        color: #000000;
        bottom: 15px; 
    }

    .equipe-item {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .icone-user {
        width: 18px;
        height: 18px;
        background-color: var(--green-primary);
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        color: white;
        font-size: 10px;
    }

    .divisor-v {
        width: 1px;
        height: 15px;
        background-color: #ccc;
    }

    /* ================================
       VIA EMPRESA (PARTE DE BAIXO)
    ================================ */

    .empresa-dados {
        position: absolute;
        top: 40px;
        left: 260px;
        width: 320px;
        font-size: 11px;
        line-height: 1.6;
    }

    .protocolo-titulo {
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 15px;
        color: var(--text-dark);
    }

    .dado-item {
        display: flex;
        gap: 8px;
        margin-bottom: 8px;
    }

    .empresa-direita {
        position: absolute;
        top: 20px;
        right: 40px;
        width: 320px;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
    }

    /* Caixa de Assinatura */
    .caixa-assinatura {
        width: 100%;
        border: 1px solid #999;
        border-radius: 8px;
        padding: 15px 20px;
        text-align: center;
        margin-top: 5px;
    }

    .caixa-assinatura h3 {
        font-size: 12px;
        color: var(--green-primary);
        margin-bottom: 8px;
    }

    .caixa-assinatura p {
        font-size: 10px;
        margin-bottom: 35px;
    }

    .linha-ass-caixa {
        border-bottom: 1px solid var(--text-dark);
        margin-bottom: 5px;
    }

    .label-ass {
        font-size: 9px;
        margin-bottom: 15px;
    }

    .caixa-rodape {
        display: flex;
        justify-content: space-between;
        font-size: 10px;
    }

    .caixa-rodape .nome { width: 55%; border-bottom: 1px solid var(--text-dark); text-align: left;}
    .caixa-rodape .data { width: 40%; border-bottom: 1px solid var(--text-dark); text-align: right;}

    @media print {
        @page {
            margin: 0; 
            size: A4 portrait;
        }
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            background-color: white !important;
            overflow-x: hidden !important;
            width: 100% !important;
        }
        .voucher-wrapper {
            margin: 0 !important;
            position: relative !important;
            left: 0 !important;
            transform: scale(0.793) !important;
            transform-origin: top left !important;
            box-shadow: none !important;
        }
    }
</style>

<div class="voucher-wrapper">
    
    <!-- ==================== 1ª VIA (CLIENTE) ==================== -->
    <div class="via-cliente">
        
        <div class="cliente-centro">
            <div class="titulo-voucher">VOUCHER FOTOS</div>
            <div class="texto-principal">
                O Itacaré Vacation Club tem o prazer de proporcionar através deste voucher uma <strong>sessão de fotos exclusiva</strong>,<br>em nome de: <strong>[NOME_TITULAR] e [NOME_CONJUGE]</strong>.
            </div>
            <div class="obs-garcom">OBS: Ao realizar as fotos, apresente este voucher ao profissional.</div>

            <div class="cliente-assinaturas">
                <div class="linha-ass ass-nome">Nome: [NOME_TITULAR]</div>
                <div class="linha-ass ass-gerente">GERENTE</div>
            </div>

            <div class="rodape-obs">
                <strong>OBS:</strong> Essa cortesia é pessoal e intransferível / Esse voucher deve ser entregue ao fotógrafo no momento da sessão.<br>
                <strong>válido por 30 dias da data de emissão</strong>
            </div>
        </div>

        <div class="cliente-direita">
            <!-- QR Code Dinâmico com a Tag [QR_CODE] -->
            [QR_CODE]
            <div class="badge-via">1ª Via (Cliente)</div>
            <div class="info-dados">
                <div><strong>Data:</strong> <span>[DATA]</span></div>
                <div><strong>Nº:</strong> <span>CO-[ID_ATENDIMENTO]</span></div>
            </div>
        </div>

        <div class="equipe-vendas">
            <div class="equipe-item"><span class="icone-user">👤</span> Promotor: [PROMOTOR]</div>
            <div class="divisor-v"></div>
            <div class="equipe-item"><span class="icone-user">👤</span> Consultor: [CONSULTOR]</div>
            <div class="divisor-v"></div>
            <div class="equipe-item"><span class="icone-user">👤</span> Supervisor: [SUPERVISOR]</div>
        </div>

    </div>

    <!-- ==================== 2ª VIA (EMPRESA) ==================== -->
    <div class="via-empresa">
        
        <div class="empresa-dados">
            <div class="protocolo-titulo">PROTOCOLO Nº: CO-[ID_ATENDIMENTO]</div>
            <div class="dado-item">
                <span>📅</span> 
                <span><strong>Data:</strong> [DATA]</span>
            </div>
            <div class="dado-item">
                <span>👤</span> 
                <span><strong>Cliente:</strong> [NOME_TITULAR]<br>e [NOME_CONJUGE]</span>
            </div>
            <div class="dado-item">
                <span>🍴</span> 
                <span><strong>Cortesia:</strong> VOUCHER FOTOS</span>
            </div>
            <div class="dado-item">
                <span>📄</span> 
                <span><strong>Descrição:</strong> O Itacaré Vacation Club tem o prazer de proporcionar através deste voucher uma sessão de fotos exclusiva, </span>
            </div>
            <div class="dado-item">
                <span>👤</span> 
                <span><strong>CPF:</strong> [CPF]</span>
            </div>
        </div>

        <div class="empresa-direita">
            <div class="badge-via" style="width: 120px;">2ª Via (Empresa)</div>
            
            <div class="caixa-assinatura">
                <h3>ASSINATURA DO CLIENTE</h3>
                <p>Declaro que recebi a cortesia descrita acima.</p>
                <div class="linha-ass-caixa"></div>
                <div class="label-ass">Assinatura</div>
                
                <div class="caixa-rodape">
                    <div class="nome" style="text-align: center;">[NOME_CURTO]</div>
                    <div class="data" style="text-align: center;">[DATA_IMPRESSAO]</div>
                </div>
            </div>
        </div>

        <div class="equipe-vendas" style="bottom: 50px;">
            <div class="equipe-item"><span class="icone-user">👤</span> Promotor: [PROMOTOR]</div>
            <div class="divisor-v"></div>
            <div class="equipe-item"><span class="icone-user">👤</span> Consultor: [CONSULTOR]</div>
            <div class="divisor-v"></div>
            <div class="equipe-item"><span class="icone-user">👤</span> Supervisor: [SUPERVISOR]</div>
        </div>

    </div>

</div>
HTML;
        $item->save();

        $item = new ComplimentaryItem();
        $item->name = 'Jantar - Terra Boa';
        $item->code = 'CORT-NEW-101';
        $item->template_type = 'html';
        $item->metadata = '';
        $item->content = <<<'HTML'
<style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap');

    :root {
        --text-dark: #000000;
        --green-primary: #5c7254;
        --gray-light: #666666;
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        font-family: 'Montserrat', sans-serif;
    }

    body {
        background-color: #e0e0e0;
        padding: 20px;
        display: flex;
        justify-content: center;
    }

    /* Contêiner principal com a imagem de fundo */
    .voucher-wrapper {
        width: 1000px;
        height: 654px; /* Ajuste a altura conforme a proporção real da sua imagem */
        background-image: url('/images/fundo-voucher-jantar.png');
        background-size: 100% 100%;
        background-repeat: no-repeat;
        background-position: center;
        position: relative;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        color: var(--text-dark);
        margin: 0 auto;
    }

    /* Divisões virtuais das metades */
    .via-cliente {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 50%;
    }

    .via-empresa {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 50%;
    }

    /* ================================
       VIA CLIENTE (PARTE DE CIMA)
    ================================ */

    /* Texto Central */
    .cliente-centro {
        position: absolute;
        top: 25px;
        left: 280px;
        width: 480px;
        text-align: center;
    }

    .titulo-voucher {
        font-size: 18px;
        font-weight: 600;
        letter-spacing: 2px;
        color: var(--green-primary);
        margin-top: 30px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .titulo-voucher::before, .titulo-voucher::after {
        content: "";
        display: inline-block;
        width: 40px;
        height: 1px;
        background-color: var(--gray-light);
        margin: 0 15px;
    }

    .texto-principal {
        font-size: 12px;
        line-height: 1.6;
        margin-bottom: 10px;
    }

    .obs-garcom {
        font-size: 11px;
        margin-bottom: 15px;
    }

    /* Assinaturas Cliente */
    .cliente-assinaturas {
        display: flex;
        justify-content: space-between;
        font-size: 11px;
        margin-bottom: 10px;
        text-align: left;
    }

    .linha-ass {
        border-bottom: 1px solid var(--text-dark);
        padding-bottom: 4px;
    }

    .ass-nome { width: 65%; }
    .ass-gerente { width: 30%; text-align: center; }

    .rodape-obs {
        font-size: 10px;
        text-align: left;
        line-height: 1.4;
    }

    /* Info Direita (QR e Via) */
    .cliente-direita {
        position: absolute;
        top: 20px;
        right: 40px;
        width: 180px;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
    }

    .qr-code-img {
        width: 85px;
        height: 85px;
        margin-bottom: 15px;
        margin-right: 15px;
    }

    .badge-via {
        background-color: var(--green-primary);
        color: white;
        padding: 4px 0;
        width: 100%;
        text-align: center;
        font-size: 11px;
        border-radius: 2px;
        margin-bottom: 15px;
    }

    .info-dados {
        font-size: 11px;
        width: 100%;
    }

    .info-dados div {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
    }

    /* Equipe de Vendas (Ambas as vias) */
    .equipe-vendas {
        position: absolute;
        bottom: 25px; /* Ajuste para encaixar antes da onda azul */
        left: 0;
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 30px;
        font-size: 11px;
        font-weight: 600;
    }
    
    /* Cor branca para a equipe na primeira via por causa do fundo azul escuro */
    .via-cliente .equipe-vendas {
        color: #000000;
        bottom: 15px; 
    }

    .equipe-item {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .icone-user {
        width: 18px;
        height: 18px;
        background-color: var(--green-primary);
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        color: white;
        font-size: 10px;
    }

    .divisor-v {
        width: 1px;
        height: 15px;
        background-color: #ccc;
    }

    /* ================================
       VIA EMPRESA (PARTE DE BAIXO)
    ================================ */

    .empresa-dados {
        position: absolute;
        top: 40px;
        left: 260px;
        width: 320px;
        font-size: 11px;
        line-height: 1.6;
    }

    .protocolo-titulo {
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 15px;
        color: var(--text-dark);
    }

    .dado-item {
        display: flex;
        gap: 8px;
        margin-bottom: 8px;
    }

    .empresa-direita {
        position: absolute;
        top: 20px;
        right: 40px;
        width: 320px;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
    }

    /* Caixa de Assinatura */
    .caixa-assinatura {
        width: 100%;
        border: 1px solid #999;
        border-radius: 8px;
        padding: 15px 20px;
        text-align: center;
        margin-top: 5px;
    }

    .caixa-assinatura h3 {
        font-size: 12px;
        color: var(--green-primary);
        margin-bottom: 8px;
    }

    .caixa-assinatura p {
        font-size: 10px;
        margin-bottom: 35px;
    }

    .linha-ass-caixa {
        border-bottom: 1px solid var(--text-dark);
        margin-bottom: 5px;
    }

    .label-ass {
        font-size: 9px;
        margin-bottom: 15px;
    }

    .caixa-rodape {
        display: flex;
        justify-content: space-between;
        font-size: 10px;
    }

    .caixa-rodape .nome { width: 55%; border-bottom: 1px solid var(--text-dark); text-align: left;}
    .caixa-rodape .data { width: 40%; border-bottom: 1px solid var(--text-dark); text-align: right;}

    @media print {
        @page {
            margin: 0; 
            size: A4 portrait;
        }
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            background-color: white !important;
            overflow-x: hidden !important;
            width: 100% !important;
        }
        .voucher-wrapper {
            margin: 0 !important;
            position: relative !important;
            left: 0 !important;
            transform: scale(0.793) !important;
            transform-origin: top left !important;
            box-shadow: none !important;
        }
    }
</style>

<div class="voucher-wrapper">
    
    <!-- ==================== 1ª VIA (CLIENTE) ==================== -->
    <div class="via-cliente">
        
        <div class="cliente-centro">
            <div class="titulo-voucher">VOUCHER JANTAR</div>
            <div class="texto-principal">
                O Itacaré Vacation Club tem o prazer de proporcionar através deste voucher um <strong>jantar exclusivo</strong> para ser utilizado no <strong>Restaurante do Terra Boa Hotel Boutique</strong>,<br>em nome de: <strong>[NOME_TITULAR] e [NOME_CONJUGE]</strong>.
            </div>
            <div class="obs-garcom">OBS: Ao solicitar a conta, informe ao garçom sobre este voucher.</div>

            <div class="cliente-assinaturas">
                <div class="linha-ass ass-nome">Nome: [NOME_TITULAR]</div>
                <div class="linha-ass ass-gerente">GERENTE</div>
            </div>

            <div class="rodape-obs">
                <strong>OBS:</strong> Essa cortesia é pessoal e intransferível / Esse voucher deve ser entregue ao garçom no momento do pagamento da conta.<br>
                <strong>válido por 30 dias da data de emissão</strong>
            </div>
        </div>

        <div class="cliente-direita">
            <!-- QR Code Dinâmico com a Tag [QR_CODE] -->
            [QR_CODE]
            <div class="badge-via">1ª Via (Cliente)</div>
            <div class="info-dados">
                <div><strong>Data:</strong> <span>[DATA]</span></div>
                <div><strong>Nº:</strong> <span>CO-[ID_ATENDIMENTO]</span></div>
            </div>
        </div>

        <div class="equipe-vendas">
            <div class="equipe-item"><span class="icone-user">👤</span> Promotor: [PROMOTOR]</div>
            <div class="divisor-v"></div>
            <div class="equipe-item"><span class="icone-user">👤</span> Consultor: [CONSULTOR]</div>
            <div class="divisor-v"></div>
            <div class="equipe-item"><span class="icone-user">👤</span> Supervisor: [SUPERVISOR]</div>
        </div>

    </div>

    <!-- ==================== 2ª VIA (EMPRESA) ==================== -->
    <div class="via-empresa">
        
        <div class="empresa-dados">
            <div class="protocolo-titulo">PROTOCOLO Nº: CO-[ID_ATENDIMENTO]</div>
            <div class="dado-item">
                <span>📅</span> 
                <span><strong>Data:</strong> [DATA]</span>
            </div>
            <div class="dado-item">
                <span>👤</span> 
                <span><strong>Cliente:</strong> [NOME_TITULAR]<br>e [NOME_CONJUGE]</span>
            </div>
            <div class="dado-item">
                <span>🍴</span> 
                <span><strong>Cortesia:</strong> VOUCHER JANTAR</span>
            </div>
            <div class="dado-item">
                <span>📄</span> 
                <span><strong>Descrição:</strong> O Itacaré Vacation Club tem o prazer de proporcionar através deste voucher um jantar exclusivo para ser utilizado no Restaurante do Terra Boa Hotel Boutique, </span>
            </div>
            <div class="dado-item">
                <span>👤</span> 
                <span><strong>CPF:</strong> [CPF]</span>
            </div>
        </div>

        <div class="empresa-direita">
            <div class="badge-via" style="width: 120px;">2ª Via (Empresa)</div>
            
            <div class="caixa-assinatura">
                <h3>ASSINATURA DO CLIENTE</h3>
                <p>Declaro que recebi a cortesia descrita acima.</p>
                <div class="linha-ass-caixa"></div>
                <div class="label-ass">Assinatura</div>
                
                <div class="caixa-rodape">
                    <div class="nome" style="text-align: center;">[NOME_CURTO]</div>
                    <div class="data" style="text-align: center;">[DATA_IMPRESSAO]</div>
                </div>
            </div>
        </div>

        <div class="equipe-vendas" style="bottom: 50px;">
            <div class="equipe-item"><span class="icone-user">👤</span> Promotor: [PROMOTOR]</div>
            <div class="divisor-v"></div>
            <div class="equipe-item"><span class="icone-user">👤</span> Consultor: [CONSULTOR]</div>
            <div class="divisor-v"></div>
            <div class="equipe-item"><span class="icone-user">👤</span> Supervisor: [SUPERVISOR]</div>
        </div>

    </div>

</div>
HTML;
        $item->save();

        $item = new ComplimentaryItem();
        $item->name = 'Café da Manhã - Terra Boa';
        $item->code = 'CORT-NEW-102';
        $item->template_type = 'html';
        $item->metadata = '';
        $item->content = <<<'HTML'
<style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap');

    :root {
        --text-dark: #000000;
        --green-primary: #5c7254;
        --gray-light: #666666;
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        font-family: 'Montserrat', sans-serif;
    }

    body {
        background-color: #e0e0e0;
        padding: 20px;
        display: flex;
        justify-content: center;
    }

    /* Contêiner principal com a imagem de fundo */
    .voucher-wrapper {
        width: 1000px;
        height: 654px; /* Ajuste a altura conforme a proporção real da sua imagem */
        background-image: url('/images/fundo-voucher-jantar.png');
        background-size: 100% 100%;
        background-repeat: no-repeat;
        background-position: center;
        position: relative;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        color: var(--text-dark);
        margin: 0 auto;
    }

    /* Divisões virtuais das metades */
    .via-cliente {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 50%;
    }

    .via-empresa {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 50%;
    }

    /* ================================
       VIA CLIENTE (PARTE DE CIMA)
    ================================ */

    /* Texto Central */
    .cliente-centro {
        position: absolute;
        top: 25px;
        left: 280px;
        width: 480px;
        text-align: center;
    }

    .titulo-voucher {
        font-size: 18px;
        font-weight: 600;
        letter-spacing: 2px;
        color: var(--green-primary);
        margin-top: 30px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .titulo-voucher::before, .titulo-voucher::after {
        content: "";
        display: inline-block;
        width: 40px;
        height: 1px;
        background-color: var(--gray-light);
        margin: 0 15px;
    }

    .texto-principal {
        font-size: 12px;
        line-height: 1.6;
        margin-bottom: 10px;
    }

    .obs-garcom {
        font-size: 11px;
        margin-bottom: 15px;
    }

    /* Assinaturas Cliente */
    .cliente-assinaturas {
        display: flex;
        justify-content: space-between;
        font-size: 11px;
        margin-bottom: 10px;
        text-align: left;
    }

    .linha-ass {
        border-bottom: 1px solid var(--text-dark);
        padding-bottom: 4px;
    }

    .ass-nome { width: 65%; }
    .ass-gerente { width: 30%; text-align: center; }

    .rodape-obs {
        font-size: 10px;
        text-align: left;
        line-height: 1.4;
    }

    /* Info Direita (QR e Via) */
    .cliente-direita {
        position: absolute;
        top: 20px;
        right: 40px;
        width: 180px;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
    }

    .qr-code-img {
        width: 85px;
        height: 85px;
        margin-bottom: 15px;
        margin-right: 15px;
    }

    .badge-via {
        background-color: var(--green-primary);
        color: white;
        padding: 4px 0;
        width: 100%;
        text-align: center;
        font-size: 11px;
        border-radius: 2px;
        margin-bottom: 15px;
    }

    .info-dados {
        font-size: 11px;
        width: 100%;
    }

    .info-dados div {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
    }

    /* Equipe de Vendas (Ambas as vias) */
    .equipe-vendas {
        position: absolute;
        bottom: 25px; /* Ajuste para encaixar antes da onda azul */
        left: 0;
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 30px;
        font-size: 11px;
        font-weight: 600;
    }
    
    /* Cor branca para a equipe na primeira via por causa do fundo azul escuro */
    .via-cliente .equipe-vendas {
        color: #000000;
        bottom: 15px; 
    }

    .equipe-item {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .icone-user {
        width: 18px;
        height: 18px;
        background-color: var(--green-primary);
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        color: white;
        font-size: 10px;
    }

    .divisor-v {
        width: 1px;
        height: 15px;
        background-color: #ccc;
    }

    /* ================================
       VIA EMPRESA (PARTE DE BAIXO)
    ================================ */

    .empresa-dados {
        position: absolute;
        top: 40px;
        left: 260px;
        width: 320px;
        font-size: 11px;
        line-height: 1.6;
    }

    .protocolo-titulo {
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 15px;
        color: var(--text-dark);
    }

    .dado-item {
        display: flex;
        gap: 8px;
        margin-bottom: 8px;
    }

    .empresa-direita {
        position: absolute;
        top: 20px;
        right: 40px;
        width: 320px;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
    }

    /* Caixa de Assinatura */
    .caixa-assinatura {
        width: 100%;
        border: 1px solid #999;
        border-radius: 8px;
        padding: 15px 20px;
        text-align: center;
        margin-top: 5px;
    }

    .caixa-assinatura h3 {
        font-size: 12px;
        color: var(--green-primary);
        margin-bottom: 8px;
    }

    .caixa-assinatura p {
        font-size: 10px;
        margin-bottom: 35px;
    }

    .linha-ass-caixa {
        border-bottom: 1px solid var(--text-dark);
        margin-bottom: 5px;
    }

    .label-ass {
        font-size: 9px;
        margin-bottom: 15px;
    }

    .caixa-rodape {
        display: flex;
        justify-content: space-between;
        font-size: 10px;
    }

    .caixa-rodape .nome { width: 55%; border-bottom: 1px solid var(--text-dark); text-align: left;}
    .caixa-rodape .data { width: 40%; border-bottom: 1px solid var(--text-dark); text-align: right;}

    @media print {
        @page {
            margin: 0; 
            size: A4 portrait;
        }
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            background-color: white !important;
            overflow-x: hidden !important;
            width: 100% !important;
        }
        .voucher-wrapper {
            margin: 0 !important;
            position: relative !important;
            left: 0 !important;
            transform: scale(0.793) !important;
            transform-origin: top left !important;
            box-shadow: none !important;
        }
    }
</style>

<div class="voucher-wrapper">
    
    <!-- ==================== 1ª VIA (CLIENTE) ==================== -->
    <div class="via-cliente">
        
        <div class="cliente-centro">
            <div class="titulo-voucher">VOUCHER CAFÉ DA MANHÃ</div>
            <div class="texto-principal">
                O Itacaré Vacation Club tem o prazer de proporcionar através deste voucher um <strong>delicioso café da manhã</strong> para ser utilizado no <strong>Terra Boa Hotel Boutique</strong>,<br>em nome de: <strong>[NOME_TITULAR] e [NOME_CONJUGE]</strong>.
            </div>
            <div class="obs-garcom">OBS: Ao chegar no restaurante, informe ao atendente sobre este voucher.</div>

            <div class="cliente-assinaturas">
                <div class="linha-ass ass-nome">Nome: [NOME_TITULAR]</div>
                <div class="linha-ass ass-gerente">GERENTE</div>
            </div>

            <div class="rodape-obs">
                <strong>OBS:</strong> Essa cortesia é pessoal e intransferível / Esse voucher deve ser entregue ao atendente no restaurante.<br>
                <strong>válido por 30 dias da data de emissão</strong>
            </div>
        </div>

        <div class="cliente-direita">
            <!-- QR Code Dinâmico com a Tag [QR_CODE] -->
            [QR_CODE]
            <div class="badge-via">1ª Via (Cliente)</div>
            <div class="info-dados">
                <div><strong>Data:</strong> <span>[DATA]</span></div>
                <div><strong>Nº:</strong> <span>CO-[ID_ATENDIMENTO]</span></div>
            </div>
        </div>

        <div class="equipe-vendas">
            <div class="equipe-item"><span class="icone-user">👤</span> Promotor: [PROMOTOR]</div>
            <div class="divisor-v"></div>
            <div class="equipe-item"><span class="icone-user">👤</span> Consultor: [CONSULTOR]</div>
            <div class="divisor-v"></div>
            <div class="equipe-item"><span class="icone-user">👤</span> Supervisor: [SUPERVISOR]</div>
        </div>

    </div>

    <!-- ==================== 2ª VIA (EMPRESA) ==================== -->
    <div class="via-empresa">
        
        <div class="empresa-dados">
            <div class="protocolo-titulo">PROTOCOLO Nº: CO-[ID_ATENDIMENTO]</div>
            <div class="dado-item">
                <span>📅</span> 
                <span><strong>Data:</strong> [DATA]</span>
            </div>
            <div class="dado-item">
                <span>👤</span> 
                <span><strong>Cliente:</strong> [NOME_TITULAR]<br>e [NOME_CONJUGE]</span>
            </div>
            <div class="dado-item">
                <span>🍴</span> 
                <span><strong>Cortesia:</strong> VOUCHER CAFÉ DA MANHÃ</span>
            </div>
            <div class="dado-item">
                <span>📄</span> 
                <span><strong>Descrição:</strong> O Itacaré Vacation Club tem o prazer de proporcionar através deste voucher um delicioso café da manhã para ser utilizado no Terra Boa Hotel Boutique, </span>
            </div>
            <div class="dado-item">
                <span>👤</span> 
                <span><strong>CPF:</strong> [CPF]</span>
            </div>
        </div>

        <div class="empresa-direita">
            <div class="badge-via" style="width: 120px;">2ª Via (Empresa)</div>
            
            <div class="caixa-assinatura">
                <h3>ASSINATURA DO CLIENTE</h3>
                <p>Declaro que recebi a cortesia descrita acima.</p>
                <div class="linha-ass-caixa"></div>
                <div class="label-ass">Assinatura</div>
                
                <div class="caixa-rodape">
                    <div class="nome" style="text-align: center;">[NOME_CURTO]</div>
                    <div class="data" style="text-align: center;">[DATA_IMPRESSAO]</div>
                </div>
            </div>
        </div>

        <div class="equipe-vendas" style="bottom: 50px;">
            <div class="equipe-item"><span class="icone-user">👤</span> Promotor: [PROMOTOR]</div>
            <div class="divisor-v"></div>
            <div class="equipe-item"><span class="icone-user">👤</span> Consultor: [CONSULTOR]</div>
            <div class="divisor-v"></div>
            <div class="equipe-item"><span class="icone-user">👤</span> Supervisor: [SUPERVISOR]</div>
        </div>

    </div>

</div>
HTML;
        $item->save();

        $item = new ComplimentaryItem();
        $item->name = 'Café da Manhã - Resende Imperial';
        $item->code = 'CORT-NEW-103';
        $item->template_type = 'html';
        $item->metadata = '';
        $item->content = <<<'HTML'
<style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap');

    :root {
        --text-dark: #000000;
        --green-primary: #5c7254;
        --gray-light: #666666;
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        font-family: 'Montserrat', sans-serif;
    }

    body {
        background-color: #e0e0e0;
        padding: 20px;
        display: flex;
        justify-content: center;
    }

    /* Contêiner principal com a imagem de fundo */
    .voucher-wrapper {
        width: 1000px;
        height: 654px; /* Ajuste a altura conforme a proporção real da sua imagem */
        background-image: url('/images/fundo-voucher-jantar.png');
        background-size: 100% 100%;
        background-repeat: no-repeat;
        background-position: center;
        position: relative;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        color: var(--text-dark);
        margin: 0 auto;
    }

    /* Divisões virtuais das metades */
    .via-cliente {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 50%;
    }

    .via-empresa {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 50%;
    }

    /* ================================
       VIA CLIENTE (PARTE DE CIMA)
    ================================ */

    /* Texto Central */
    .cliente-centro {
        position: absolute;
        top: 25px;
        left: 280px;
        width: 480px;
        text-align: center;
    }

    .titulo-voucher {
        font-size: 18px;
        font-weight: 600;
        letter-spacing: 2px;
        color: var(--green-primary);
        margin-top: 30px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .titulo-voucher::before, .titulo-voucher::after {
        content: "";
        display: inline-block;
        width: 40px;
        height: 1px;
        background-color: var(--gray-light);
        margin: 0 15px;
    }

    .texto-principal {
        font-size: 12px;
        line-height: 1.6;
        margin-bottom: 10px;
    }

    .obs-garcom {
        font-size: 11px;
        margin-bottom: 15px;
    }

    /* Assinaturas Cliente */
    .cliente-assinaturas {
        display: flex;
        justify-content: space-between;
        font-size: 11px;
        margin-bottom: 10px;
        text-align: left;
    }

    .linha-ass {
        border-bottom: 1px solid var(--text-dark);
        padding-bottom: 4px;
    }

    .ass-nome { width: 65%; }
    .ass-gerente { width: 30%; text-align: center; }

    .rodape-obs {
        font-size: 10px;
        text-align: left;
        line-height: 1.4;
    }

    /* Info Direita (QR e Via) */
    .cliente-direita {
        position: absolute;
        top: 20px;
        right: 40px;
        width: 180px;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
    }

    .qr-code-img {
        width: 85px;
        height: 85px;
        margin-bottom: 15px;
        margin-right: 15px;
    }

    .badge-via {
        background-color: var(--green-primary);
        color: white;
        padding: 4px 0;
        width: 100%;
        text-align: center;
        font-size: 11px;
        border-radius: 2px;
        margin-bottom: 15px;
    }

    .info-dados {
        font-size: 11px;
        width: 100%;
    }

    .info-dados div {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
    }

    /* Equipe de Vendas (Ambas as vias) */
    .equipe-vendas {
        position: absolute;
        bottom: 25px; /* Ajuste para encaixar antes da onda azul */
        left: 0;
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 30px;
        font-size: 11px;
        font-weight: 600;
    }
    
    /* Cor branca para a equipe na primeira via por causa do fundo azul escuro */
    .via-cliente .equipe-vendas {
        color: #000000;
        bottom: 15px; 
    }

    .equipe-item {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .icone-user {
        width: 18px;
        height: 18px;
        background-color: var(--green-primary);
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        color: white;
        font-size: 10px;
    }

    .divisor-v {
        width: 1px;
        height: 15px;
        background-color: #ccc;
    }

    /* ================================
       VIA EMPRESA (PARTE DE BAIXO)
    ================================ */

    .empresa-dados {
        position: absolute;
        top: 40px;
        left: 260px;
        width: 320px;
        font-size: 11px;
        line-height: 1.6;
    }

    .protocolo-titulo {
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 15px;
        color: var(--text-dark);
    }

    .dado-item {
        display: flex;
        gap: 8px;
        margin-bottom: 8px;
    }

    .empresa-direita {
        position: absolute;
        top: 20px;
        right: 40px;
        width: 320px;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
    }

    /* Caixa de Assinatura */
    .caixa-assinatura {
        width: 100%;
        border: 1px solid #999;
        border-radius: 8px;
        padding: 15px 20px;
        text-align: center;
        margin-top: 5px;
    }

    .caixa-assinatura h3 {
        font-size: 12px;
        color: var(--green-primary);
        margin-bottom: 8px;
    }

    .caixa-assinatura p {
        font-size: 10px;
        margin-bottom: 35px;
    }

    .linha-ass-caixa {
        border-bottom: 1px solid var(--text-dark);
        margin-bottom: 5px;
    }

    .label-ass {
        font-size: 9px;
        margin-bottom: 15px;
    }

    .caixa-rodape {
        display: flex;
        justify-content: space-between;
        font-size: 10px;
    }

    .caixa-rodape .nome { width: 55%; border-bottom: 1px solid var(--text-dark); text-align: left;}
    .caixa-rodape .data { width: 40%; border-bottom: 1px solid var(--text-dark); text-align: right;}

    @media print {
        @page {
            margin: 0; 
            size: A4 portrait;
        }
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            background-color: white !important;
            overflow-x: hidden !important;
            width: 100% !important;
        }
        .voucher-wrapper {
            margin: 0 !important;
            position: relative !important;
            left: 0 !important;
            transform: scale(0.793) !important;
            transform-origin: top left !important;
            box-shadow: none !important;
        }
    }
</style>

<div class="voucher-wrapper">
    
    <!-- ==================== 1ª VIA (CLIENTE) ==================== -->
    <div class="via-cliente">
        
        <div class="cliente-centro">
            <div class="titulo-voucher">VOUCHER CAFÉ DA MANHÃ</div>
            <div class="texto-principal">
                O Itacaré Vacation Club tem o prazer de proporcionar através deste voucher um <strong>delicioso café da manhã</strong> para ser utilizado no <strong>Resende Imperial Hotel & Spa</strong>,<br>em nome de: <strong>[NOME_TITULAR] e [NOME_CONJUGE]</strong>.
            </div>
            <div class="obs-garcom">OBS: Ao chegar no restaurante, informe ao atendente sobre este voucher.</div>

            <div class="cliente-assinaturas">
                <div class="linha-ass ass-nome">Nome: [NOME_TITULAR]</div>
                <div class="linha-ass ass-gerente">GERENTE</div>
            </div>

            <div class="rodape-obs">
                <strong>OBS:</strong> Essa cortesia é pessoal e intransferível / Esse voucher deve ser entregue ao atendente no restaurante.<br>
                <strong>válido por 30 dias da data de emissão</strong>
            </div>
        </div>

        <div class="cliente-direita">
            <!-- QR Code Dinâmico com a Tag [QR_CODE] -->
            [QR_CODE]
            <div class="badge-via">1ª Via (Cliente)</div>
            <div class="info-dados">
                <div><strong>Data:</strong> <span>[DATA]</span></div>
                <div><strong>Nº:</strong> <span>CO-[ID_ATENDIMENTO]</span></div>
            </div>
        </div>

        <div class="equipe-vendas">
            <div class="equipe-item"><span class="icone-user">👤</span> Promotor: [PROMOTOR]</div>
            <div class="divisor-v"></div>
            <div class="equipe-item"><span class="icone-user">👤</span> Consultor: [CONSULTOR]</div>
            <div class="divisor-v"></div>
            <div class="equipe-item"><span class="icone-user">👤</span> Supervisor: [SUPERVISOR]</div>
        </div>

    </div>

    <!-- ==================== 2ª VIA (EMPRESA) ==================== -->
    <div class="via-empresa">
        
        <div class="empresa-dados">
            <div class="protocolo-titulo">PROTOCOLO Nº: CO-[ID_ATENDIMENTO]</div>
            <div class="dado-item">
                <span>📅</span> 
                <span><strong>Data:</strong> [DATA]</span>
            </div>
            <div class="dado-item">
                <span>👤</span> 
                <span><strong>Cliente:</strong> [NOME_TITULAR]<br>e [NOME_CONJUGE]</span>
            </div>
            <div class="dado-item">
                <span>🍴</span> 
                <span><strong>Cortesia:</strong> VOUCHER CAFÉ DA MANHÃ</span>
            </div>
            <div class="dado-item">
                <span>📄</span> 
                <span><strong>Descrição:</strong> O Itacaré Vacation Club tem o prazer de proporcionar através deste voucher um delicioso café da manhã para ser utilizado no Resende Imperial Hotel & Spa, </span>
            </div>
            <div class="dado-item">
                <span>👤</span> 
                <span><strong>CPF:</strong> [CPF]</span>
            </div>
        </div>

        <div class="empresa-direita">
            <div class="badge-via" style="width: 120px;">2ª Via (Empresa)</div>
            
            <div class="caixa-assinatura">
                <h3>ASSINATURA DO CLIENTE</h3>
                <p>Declaro que recebi a cortesia descrita acima.</p>
                <div class="linha-ass-caixa"></div>
                <div class="label-ass">Assinatura</div>
                
                <div class="caixa-rodape">
                    <div class="nome" style="text-align: center;">[NOME_CURTO]</div>
                    <div class="data" style="text-align: center;">[DATA_IMPRESSAO]</div>
                </div>
            </div>
        </div>

        <div class="equipe-vendas" style="bottom: 50px;">
            <div class="equipe-item"><span class="icone-user">👤</span> Promotor: [PROMOTOR]</div>
            <div class="divisor-v"></div>
            <div class="equipe-item"><span class="icone-user">👤</span> Consultor: [CONSULTOR]</div>
            <div class="divisor-v"></div>
            <div class="equipe-item"><span class="icone-user">👤</span> Supervisor: [SUPERVISOR]</div>
        </div>

    </div>

</div>
HTML;
        $item->save();

        $item = new ComplimentaryItem();
        $item->name = 'Massagem Relaxante';
        $item->code = 'CORT-NEW-104';
        $item->template_type = 'html';
        $item->metadata = '';
        $item->content = <<<'HTML'
<style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap');

    :root {
        --text-dark: #000000;
        --green-primary: #5c7254;
        --gray-light: #666666;
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        font-family: 'Montserrat', sans-serif;
    }

    body {
        background-color: #e0e0e0;
        padding: 20px;
        display: flex;
        justify-content: center;
    }

    /* Contêiner principal com a imagem de fundo */
    .voucher-wrapper {
        width: 1000px;
        height: 654px; /* Ajuste a altura conforme a proporção real da sua imagem */
        background-image: url('/images/fundo-voucher-jantar.png');
        background-size: 100% 100%;
        background-repeat: no-repeat;
        background-position: center;
        position: relative;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        color: var(--text-dark);
        margin: 0 auto;
    }

    /* Divisões virtuais das metades */
    .via-cliente {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 50%;
    }

    .via-empresa {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 50%;
    }

    /* ================================
       VIA CLIENTE (PARTE DE CIMA)
    ================================ */

    /* Texto Central */
    .cliente-centro {
        position: absolute;
        top: 25px;
        left: 280px;
        width: 480px;
        text-align: center;
    }

    .titulo-voucher {
        font-size: 18px;
        font-weight: 600;
        letter-spacing: 2px;
        color: var(--green-primary);
        margin-top: 30px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .titulo-voucher::before, .titulo-voucher::after {
        content: "";
        display: inline-block;
        width: 40px;
        height: 1px;
        background-color: var(--gray-light);
        margin: 0 15px;
    }

    .texto-principal {
        font-size: 12px;
        line-height: 1.6;
        margin-bottom: 10px;
    }

    .obs-garcom {
        font-size: 11px;
        margin-bottom: 15px;
    }

    /* Assinaturas Cliente */
    .cliente-assinaturas {
        display: flex;
        justify-content: space-between;
        font-size: 11px;
        margin-bottom: 10px;
        text-align: left;
    }

    .linha-ass {
        border-bottom: 1px solid var(--text-dark);
        padding-bottom: 4px;
    }

    .ass-nome { width: 65%; }
    .ass-gerente { width: 30%; text-align: center; }

    .rodape-obs {
        font-size: 10px;
        text-align: left;
        line-height: 1.4;
    }

    /* Info Direita (QR e Via) */
    .cliente-direita {
        position: absolute;
        top: 20px;
        right: 40px;
        width: 180px;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
    }

    .qr-code-img {
        width: 85px;
        height: 85px;
        margin-bottom: 15px;
        margin-right: 15px;
    }

    .badge-via {
        background-color: var(--green-primary);
        color: white;
        padding: 4px 0;
        width: 100%;
        text-align: center;
        font-size: 11px;
        border-radius: 2px;
        margin-bottom: 15px;
    }

    .info-dados {
        font-size: 11px;
        width: 100%;
    }

    .info-dados div {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
    }

    /* Equipe de Vendas (Ambas as vias) */
    .equipe-vendas {
        position: absolute;
        bottom: 25px; /* Ajuste para encaixar antes da onda azul */
        left: 0;
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 30px;
        font-size: 11px;
        font-weight: 600;
    }
    
    /* Cor branca para a equipe na primeira via por causa do fundo azul escuro */
    .via-cliente .equipe-vendas {
        color: #000000;
        bottom: 15px; 
    }

    .equipe-item {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .icone-user {
        width: 18px;
        height: 18px;
        background-color: var(--green-primary);
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        color: white;
        font-size: 10px;
    }

    .divisor-v {
        width: 1px;
        height: 15px;
        background-color: #ccc;
    }

    /* ================================
       VIA EMPRESA (PARTE DE BAIXO)
    ================================ */

    .empresa-dados {
        position: absolute;
        top: 40px;
        left: 260px;
        width: 320px;
        font-size: 11px;
        line-height: 1.6;
    }

    .protocolo-titulo {
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 15px;
        color: var(--text-dark);
    }

    .dado-item {
        display: flex;
        gap: 8px;
        margin-bottom: 8px;
    }

    .empresa-direita {
        position: absolute;
        top: 20px;
        right: 40px;
        width: 320px;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
    }

    /* Caixa de Assinatura */
    .caixa-assinatura {
        width: 100%;
        border: 1px solid #999;
        border-radius: 8px;
        padding: 15px 20px;
        text-align: center;
        margin-top: 5px;
    }

    .caixa-assinatura h3 {
        font-size: 12px;
        color: var(--green-primary);
        margin-bottom: 8px;
    }

    .caixa-assinatura p {
        font-size: 10px;
        margin-bottom: 35px;
    }

    .linha-ass-caixa {
        border-bottom: 1px solid var(--text-dark);
        margin-bottom: 5px;
    }

    .label-ass {
        font-size: 9px;
        margin-bottom: 15px;
    }

    .caixa-rodape {
        display: flex;
        justify-content: space-between;
        font-size: 10px;
    }

    .caixa-rodape .nome { width: 55%; border-bottom: 1px solid var(--text-dark); text-align: left;}
    .caixa-rodape .data { width: 40%; border-bottom: 1px solid var(--text-dark); text-align: right;}

    @media print {
        @page {
            margin: 0; 
            size: A4 portrait;
        }
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            background-color: white !important;
            overflow-x: hidden !important;
            width: 100% !important;
        }
        .voucher-wrapper {
            margin: 0 !important;
            position: relative !important;
            left: 0 !important;
            transform: scale(0.793) !important;
            transform-origin: top left !important;
            box-shadow: none !important;
        }
    }
</style>

<div class="voucher-wrapper">
    
    <!-- ==================== 1ª VIA (CLIENTE) ==================== -->
    <div class="via-cliente">
        
        <div class="cliente-centro">
            <div class="titulo-voucher">VOUCHER MASSAGEM</div>
            <div class="texto-principal">
                O Itacaré Vacation Club tem o prazer de proporcionar através deste voucher uma <strong>sessão de massagem relaxante</strong>,<br>em nome de: <strong>[NOME_TITULAR] e [NOME_CONJUGE]</strong>.
            </div>
            <div class="obs-garcom">OBS: Agende seu horário com antecedência e apresente este voucher.</div>

            <div class="cliente-assinaturas">
                <div class="linha-ass ass-nome">Nome: [NOME_TITULAR]</div>
                <div class="linha-ass ass-gerente">GERENTE</div>
            </div>

            <div class="rodape-obs">
                <strong>OBS:</strong> Essa cortesia é pessoal e intransferível / Esse voucher deve ser entregue à recepção ou terapeuta no momento do atendimento.<br>
                <strong>válido por 30 dias da data de emissão</strong>
            </div>
        </div>

        <div class="cliente-direita">
            <!-- QR Code Dinâmico com a Tag [QR_CODE] -->
            [QR_CODE]
            <div class="badge-via">1ª Via (Cliente)</div>
            <div class="info-dados">
                <div><strong>Data:</strong> <span>[DATA]</span></div>
                <div><strong>Nº:</strong> <span>CO-[ID_ATENDIMENTO]</span></div>
            </div>
        </div>

        <div class="equipe-vendas">
            <div class="equipe-item"><span class="icone-user">👤</span> Promotor: [PROMOTOR]</div>
            <div class="divisor-v"></div>
            <div class="equipe-item"><span class="icone-user">👤</span> Consultor: [CONSULTOR]</div>
            <div class="divisor-v"></div>
            <div class="equipe-item"><span class="icone-user">👤</span> Supervisor: [SUPERVISOR]</div>
        </div>

    </div>

    <!-- ==================== 2ª VIA (EMPRESA) ==================== -->
    <div class="via-empresa">
        
        <div class="empresa-dados">
            <div class="protocolo-titulo">PROTOCOLO Nº: CO-[ID_ATENDIMENTO]</div>
            <div class="dado-item">
                <span>📅</span> 
                <span><strong>Data:</strong> [DATA]</span>
            </div>
            <div class="dado-item">
                <span>👤</span> 
                <span><strong>Cliente:</strong> [NOME_TITULAR]<br>e [NOME_CONJUGE]</span>
            </div>
            <div class="dado-item">
                <span>🍴</span> 
                <span><strong>Cortesia:</strong> VOUCHER MASSAGEM</span>
            </div>
            <div class="dado-item">
                <span>📄</span> 
                <span><strong>Descrição:</strong> O Itacaré Vacation Club tem o prazer de proporcionar através deste voucher uma sessão de massagem relaxante, </span>
            </div>
            <div class="dado-item">
                <span>👤</span> 
                <span><strong>CPF:</strong> [CPF]</span>
            </div>
        </div>

        <div class="empresa-direita">
            <div class="badge-via" style="width: 120px;">2ª Via (Empresa)</div>
            
            <div class="caixa-assinatura">
                <h3>ASSINATURA DO CLIENTE</h3>
                <p>Declaro que recebi a cortesia descrita acima.</p>
                <div class="linha-ass-caixa"></div>
                <div class="label-ass">Assinatura</div>
                
                <div class="caixa-rodape">
                    <div class="nome" style="text-align: center;">[NOME_CURTO]</div>
                    <div class="data" style="text-align: center;">[DATA_IMPRESSAO]</div>
                </div>
            </div>
        </div>

        <div class="equipe-vendas" style="bottom: 50px;">
            <div class="equipe-item"><span class="icone-user">👤</span> Promotor: [PROMOTOR]</div>
            <div class="divisor-v"></div>
            <div class="equipe-item"><span class="icone-user">👤</span> Consultor: [CONSULTOR]</div>
            <div class="divisor-v"></div>
            <div class="equipe-item"><span class="icone-user">👤</span> Supervisor: [SUPERVISOR]</div>
        </div>

    </div>

</div>
HTML;
        $item->save();

        $item = new ComplimentaryItem();
        $item->name = 'Voucher R$100,00 - Cabana Ariramba';
        $item->code = 'CORT-NEW-105';
        $item->template_type = 'html';
        $item->metadata = '';
        $item->content = <<<'HTML'
<style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap');

    :root {
        --text-dark: #000000;
        --green-primary: #5c7254;
        --gray-light: #666666;
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        font-family: 'Montserrat', sans-serif;
    }

    body {
        background-color: #e0e0e0;
        padding: 20px;
        display: flex;
        justify-content: center;
    }

    /* Contêiner principal com a imagem de fundo */
    .voucher-wrapper {
        width: 1000px;
        height: 654px; /* Ajuste a altura conforme a proporção real da sua imagem */
        background-image: url('/images/fundo-voucher-jantar.png');
        background-size: 100% 100%;
        background-repeat: no-repeat;
        background-position: center;
        position: relative;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        color: var(--text-dark);
        margin: 0 auto;
    }

    /* Divisões virtuais das metades */
    .via-cliente {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 50%;
    }

    .via-empresa {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 50%;
    }

    /* ================================
       VIA CLIENTE (PARTE DE CIMA)
    ================================ */

    /* Texto Central */
    .cliente-centro {
        position: absolute;
        top: 25px;
        left: 280px;
        width: 480px;
        text-align: center;
    }

    .titulo-voucher {
        font-size: 18px;
        font-weight: 600;
        letter-spacing: 2px;
        color: var(--green-primary);
        margin-top: 30px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .titulo-voucher::before, .titulo-voucher::after {
        content: "";
        display: inline-block;
        width: 40px;
        height: 1px;
        background-color: var(--gray-light);
        margin: 0 15px;
    }

    .texto-principal {
        font-size: 12px;
        line-height: 1.6;
        margin-bottom: 10px;
    }

    .obs-garcom {
        font-size: 11px;
        margin-bottom: 15px;
    }

    /* Assinaturas Cliente */
    .cliente-assinaturas {
        display: flex;
        justify-content: space-between;
        font-size: 11px;
        margin-bottom: 10px;
        text-align: left;
    }

    .linha-ass {
        border-bottom: 1px solid var(--text-dark);
        padding-bottom: 4px;
    }

    .ass-nome { width: 65%; }
    .ass-gerente { width: 30%; text-align: center; }

    .rodape-obs {
        font-size: 10px;
        text-align: left;
        line-height: 1.4;
    }

    /* Info Direita (QR e Via) */
    .cliente-direita {
        position: absolute;
        top: 20px;
        right: 40px;
        width: 180px;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
    }

    .qr-code-img {
        width: 85px;
        height: 85px;
        margin-bottom: 15px;
        margin-right: 15px;
    }

    .badge-via {
        background-color: var(--green-primary);
        color: white;
        padding: 4px 0;
        width: 100%;
        text-align: center;
        font-size: 11px;
        border-radius: 2px;
        margin-bottom: 15px;
    }

    .info-dados {
        font-size: 11px;
        width: 100%;
    }

    .info-dados div {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
    }

    /* Equipe de Vendas (Ambas as vias) */
    .equipe-vendas {
        position: absolute;
        bottom: 25px; /* Ajuste para encaixar antes da onda azul */
        left: 0;
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 30px;
        font-size: 11px;
        font-weight: 600;
    }
    
    /* Cor branca para a equipe na primeira via por causa do fundo azul escuro */
    .via-cliente .equipe-vendas {
        color: #000000;
        bottom: 15px; 
    }

    .equipe-item {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .icone-user {
        width: 18px;
        height: 18px;
        background-color: var(--green-primary);
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        color: white;
        font-size: 10px;
    }

    .divisor-v {
        width: 1px;
        height: 15px;
        background-color: #ccc;
    }

    /* ================================
       VIA EMPRESA (PARTE DE BAIXO)
    ================================ */

    .empresa-dados {
        position: absolute;
        top: 40px;
        left: 260px;
        width: 320px;
        font-size: 11px;
        line-height: 1.6;
    }

    .protocolo-titulo {
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 15px;
        color: var(--text-dark);
    }

    .dado-item {
        display: flex;
        gap: 8px;
        margin-bottom: 8px;
    }

    .empresa-direita {
        position: absolute;
        top: 20px;
        right: 40px;
        width: 320px;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
    }

    /* Caixa de Assinatura */
    .caixa-assinatura {
        width: 100%;
        border: 1px solid #999;
        border-radius: 8px;
        padding: 15px 20px;
        text-align: center;
        margin-top: 5px;
    }

    .caixa-assinatura h3 {
        font-size: 12px;
        color: var(--green-primary);
        margin-bottom: 8px;
    }

    .caixa-assinatura p {
        font-size: 10px;
        margin-bottom: 35px;
    }

    .linha-ass-caixa {
        border-bottom: 1px solid var(--text-dark);
        margin-bottom: 5px;
    }

    .label-ass {
        font-size: 9px;
        margin-bottom: 15px;
    }

    .caixa-rodape {
        display: flex;
        justify-content: space-between;
        font-size: 10px;
    }

    .caixa-rodape .nome { width: 55%; border-bottom: 1px solid var(--text-dark); text-align: left;}
    .caixa-rodape .data { width: 40%; border-bottom: 1px solid var(--text-dark); text-align: right;}

    @media print {
        @page {
            margin: 0; 
            size: A4 portrait;
        }
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            background-color: white !important;
            overflow-x: hidden !important;
            width: 100% !important;
        }
        .voucher-wrapper {
            margin: 0 !important;
            position: relative !important;
            left: 0 !important;
            transform: scale(0.793) !important;
            transform-origin: top left !important;
            box-shadow: none !important;
        }
    }
</style>

<div class="voucher-wrapper">
    
    <!-- ==================== 1ª VIA (CLIENTE) ==================== -->
    <div class="via-cliente">
        
        <div class="cliente-centro">
            <div class="titulo-voucher">VOUCHER CONSUMAÇÃO - R$ 100,00</div>
            <div class="texto-principal">
                O Itacaré Vacation Club tem o prazer de proporcionar através deste voucher uma <strong>consumação no valor de R$ 100,00</strong> para ser utilizada na <strong>Cabana Ariramba</strong>,<br>em nome de: <strong>[NOME_TITULAR] e [NOME_CONJUGE]</strong>.
            </div>
            <div class="obs-garcom">OBS: Ao solicitar a conta, informe ao garçom sobre este voucher.</div>

            <div class="cliente-assinaturas">
                <div class="linha-ass ass-nome">Nome: [NOME_TITULAR]</div>
                <div class="linha-ass ass-gerente">GERENTE</div>
            </div>

            <div class="rodape-obs">
                <strong>OBS:</strong> Essa cortesia é pessoal e intransferível / Esse voucher deve ser entregue ao garçom no momento do pagamento da conta.<br>
                <strong>válido por 30 dias da data de emissão</strong>
            </div>
        </div>

        <div class="cliente-direita">
            <!-- QR Code Dinâmico com a Tag [QR_CODE] -->
            [QR_CODE]
            <div class="badge-via">1ª Via (Cliente)</div>
            <div class="info-dados">
                <div><strong>Data:</strong> <span>[DATA]</span></div>
                <div><strong>Nº:</strong> <span>CO-[ID_ATENDIMENTO]</span></div>
            </div>
        </div>

        <div class="equipe-vendas">
            <div class="equipe-item"><span class="icone-user">👤</span> Promotor: [PROMOTOR]</div>
            <div class="divisor-v"></div>
            <div class="equipe-item"><span class="icone-user">👤</span> Consultor: [CONSULTOR]</div>
            <div class="divisor-v"></div>
            <div class="equipe-item"><span class="icone-user">👤</span> Supervisor: [SUPERVISOR]</div>
        </div>

    </div>

    <!-- ==================== 2ª VIA (EMPRESA) ==================== -->
    <div class="via-empresa">
        
        <div class="empresa-dados">
            <div class="protocolo-titulo">PROTOCOLO Nº: CO-[ID_ATENDIMENTO]</div>
            <div class="dado-item">
                <span>📅</span> 
                <span><strong>Data:</strong> [DATA]</span>
            </div>
            <div class="dado-item">
                <span>👤</span> 
                <span><strong>Cliente:</strong> [NOME_TITULAR]<br>e [NOME_CONJUGE]</span>
            </div>
            <div class="dado-item">
                <span>🍴</span> 
                <span><strong>Cortesia:</strong> VOUCHER CONSUMAÇÃO - R$ 100,00</span>
            </div>
            <div class="dado-item">
                <span>📄</span> 
                <span><strong>Descrição:</strong> O Itacaré Vacation Club tem o prazer de proporcionar através deste voucher uma consumação no valor de R$ 100,00 para ser utilizada na Cabana Ariramba, </span>
            </div>
            <div class="dado-item">
                <span>👤</span> 
                <span><strong>CPF:</strong> [CPF]</span>
            </div>
        </div>

        <div class="empresa-direita">
            <div class="badge-via" style="width: 120px;">2ª Via (Empresa)</div>
            
            <div class="caixa-assinatura">
                <h3>ASSINATURA DO CLIENTE</h3>
                <p>Declaro que recebi a cortesia descrita acima.</p>
                <div class="linha-ass-caixa"></div>
                <div class="label-ass">Assinatura</div>
                
                <div class="caixa-rodape">
                    <div class="nome" style="text-align: center;">[NOME_CURTO]</div>
                    <div class="data" style="text-align: center;">[DATA_IMPRESSAO]</div>
                </div>
            </div>
        </div>

        <div class="equipe-vendas" style="bottom: 50px;">
            <div class="equipe-item"><span class="icone-user">👤</span> Promotor: [PROMOTOR]</div>
            <div class="divisor-v"></div>
            <div class="equipe-item"><span class="icone-user">👤</span> Consultor: [CONSULTOR]</div>
            <div class="divisor-v"></div>
            <div class="equipe-item"><span class="icone-user">👤</span> Supervisor: [SUPERVISOR]</div>
        </div>

    </div>

</div>
HTML;
        $item->save();

        $item = new ComplimentaryItem();
        $item->name = 'Voucher R$100,00 - Cabana Terra Boa';
        $item->code = 'CORT-NEW-106';
        $item->template_type = 'html';
        $item->metadata = '';
        $item->content = <<<'HTML'
<style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap');

    :root {
        --text-dark: #000000;
        --green-primary: #5c7254;
        --gray-light: #666666;
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        font-family: 'Montserrat', sans-serif;
    }

    body {
        background-color: #e0e0e0;
        padding: 20px;
        display: flex;
        justify-content: center;
    }

    /* Contêiner principal com a imagem de fundo */
    .voucher-wrapper {
        width: 1000px;
        height: 654px; /* Ajuste a altura conforme a proporção real da sua imagem */
        background-image: url('/images/fundo-voucher-jantar.png');
        background-size: 100% 100%;
        background-repeat: no-repeat;
        background-position: center;
        position: relative;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        color: var(--text-dark);
        margin: 0 auto;
    }

    /* Divisões virtuais das metades */
    .via-cliente {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 50%;
    }

    .via-empresa {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 50%;
    }

    /* ================================
       VIA CLIENTE (PARTE DE CIMA)
    ================================ */

    /* Texto Central */
    .cliente-centro {
        position: absolute;
        top: 25px;
        left: 280px;
        width: 480px;
        text-align: center;
    }

    .titulo-voucher {
        font-size: 18px;
        font-weight: 600;
        letter-spacing: 2px;
        color: var(--green-primary);
        margin-top: 30px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .titulo-voucher::before, .titulo-voucher::after {
        content: "";
        display: inline-block;
        width: 40px;
        height: 1px;
        background-color: var(--gray-light);
        margin: 0 15px;
    }

    .texto-principal {
        font-size: 12px;
        line-height: 1.6;
        margin-bottom: 10px;
    }

    .obs-garcom {
        font-size: 11px;
        margin-bottom: 15px;
    }

    /* Assinaturas Cliente */
    .cliente-assinaturas {
        display: flex;
        justify-content: space-between;
        font-size: 11px;
        margin-bottom: 10px;
        text-align: left;
    }

    .linha-ass {
        border-bottom: 1px solid var(--text-dark);
        padding-bottom: 4px;
    }

    .ass-nome { width: 65%; }
    .ass-gerente { width: 30%; text-align: center; }

    .rodape-obs {
        font-size: 10px;
        text-align: left;
        line-height: 1.4;
    }

    /* Info Direita (QR e Via) */
    .cliente-direita {
        position: absolute;
        top: 20px;
        right: 40px;
        width: 180px;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
    }

    .qr-code-img {
        width: 85px;
        height: 85px;
        margin-bottom: 15px;
        margin-right: 15px;
    }

    .badge-via {
        background-color: var(--green-primary);
        color: white;
        padding: 4px 0;
        width: 100%;
        text-align: center;
        font-size: 11px;
        border-radius: 2px;
        margin-bottom: 15px;
    }

    .info-dados {
        font-size: 11px;
        width: 100%;
    }

    .info-dados div {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
    }

    /* Equipe de Vendas (Ambas as vias) */
    .equipe-vendas {
        position: absolute;
        bottom: 25px; /* Ajuste para encaixar antes da onda azul */
        left: 0;
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 30px;
        font-size: 11px;
        font-weight: 600;
    }
    
    /* Cor branca para a equipe na primeira via por causa do fundo azul escuro */
    .via-cliente .equipe-vendas {
        color: #000000;
        bottom: 15px; 
    }

    .equipe-item {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .icone-user {
        width: 18px;
        height: 18px;
        background-color: var(--green-primary);
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        color: white;
        font-size: 10px;
    }

    .divisor-v {
        width: 1px;
        height: 15px;
        background-color: #ccc;
    }

    /* ================================
       VIA EMPRESA (PARTE DE BAIXO)
    ================================ */

    .empresa-dados {
        position: absolute;
        top: 40px;
        left: 260px;
        width: 320px;
        font-size: 11px;
        line-height: 1.6;
    }

    .protocolo-titulo {
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 15px;
        color: var(--text-dark);
    }

    .dado-item {
        display: flex;
        gap: 8px;
        margin-bottom: 8px;
    }

    .empresa-direita {
        position: absolute;
        top: 20px;
        right: 40px;
        width: 320px;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
    }

    /* Caixa de Assinatura */
    .caixa-assinatura {
        width: 100%;
        border: 1px solid #999;
        border-radius: 8px;
        padding: 15px 20px;
        text-align: center;
        margin-top: 5px;
    }

    .caixa-assinatura h3 {
        font-size: 12px;
        color: var(--green-primary);
        margin-bottom: 8px;
    }

    .caixa-assinatura p {
        font-size: 10px;
        margin-bottom: 35px;
    }

    .linha-ass-caixa {
        border-bottom: 1px solid var(--text-dark);
        margin-bottom: 5px;
    }

    .label-ass {
        font-size: 9px;
        margin-bottom: 15px;
    }

    .caixa-rodape {
        display: flex;
        justify-content: space-between;
        font-size: 10px;
    }

    .caixa-rodape .nome { width: 55%; border-bottom: 1px solid var(--text-dark); text-align: left;}
    .caixa-rodape .data { width: 40%; border-bottom: 1px solid var(--text-dark); text-align: right;}

    @media print {
        @page {
            margin: 0; 
            size: A4 portrait;
        }
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            background-color: white !important;
            overflow-x: hidden !important;
            width: 100% !important;
        }
        .voucher-wrapper {
            margin: 0 !important;
            position: relative !important;
            left: 0 !important;
            transform: scale(0.793) !important;
            transform-origin: top left !important;
            box-shadow: none !important;
        }
    }
</style>

<div class="voucher-wrapper">
    
    <!-- ==================== 1ª VIA (CLIENTE) ==================== -->
    <div class="via-cliente">
        
        <div class="cliente-centro">
            <div class="titulo-voucher">VOUCHER CONSUMAÇÃO - R$ 100,00</div>
            <div class="texto-principal">
                O Itacaré Vacation Club tem o prazer de proporcionar através deste voucher uma <strong>consumação no valor de R$ 100,00</strong> para ser utilizada na <strong>Cabana Terra Boa</strong>,<br>em nome de: <strong>[NOME_TITULAR] e [NOME_CONJUGE]</strong>.
            </div>
            <div class="obs-garcom">OBS: Ao solicitar a conta, informe ao garçom sobre este voucher.</div>

            <div class="cliente-assinaturas">
                <div class="linha-ass ass-nome">Nome: [NOME_TITULAR]</div>
                <div class="linha-ass ass-gerente">GERENTE</div>
            </div>

            <div class="rodape-obs">
                <strong>OBS:</strong> Essa cortesia é pessoal e intransferível / Esse voucher deve ser entregue ao garçom no momento do pagamento da conta.<br>
                <strong>válido por 30 dias da data de emissão</strong>
            </div>
        </div>

        <div class="cliente-direita">
            <!-- QR Code Dinâmico com a Tag [QR_CODE] -->
            [QR_CODE]
            <div class="badge-via">1ª Via (Cliente)</div>
            <div class="info-dados">
                <div><strong>Data:</strong> <span>[DATA]</span></div>
                <div><strong>Nº:</strong> <span>CO-[ID_ATENDIMENTO]</span></div>
            </div>
        </div>

        <div class="equipe-vendas">
            <div class="equipe-item"><span class="icone-user">👤</span> Promotor: [PROMOTOR]</div>
            <div class="divisor-v"></div>
            <div class="equipe-item"><span class="icone-user">👤</span> Consultor: [CONSULTOR]</div>
            <div class="divisor-v"></div>
            <div class="equipe-item"><span class="icone-user">👤</span> Supervisor: [SUPERVISOR]</div>
        </div>

    </div>

    <!-- ==================== 2ª VIA (EMPRESA) ==================== -->
    <div class="via-empresa">
        
        <div class="empresa-dados">
            <div class="protocolo-titulo">PROTOCOLO Nº: CO-[ID_ATENDIMENTO]</div>
            <div class="dado-item">
                <span>📅</span> 
                <span><strong>Data:</strong> [DATA]</span>
            </div>
            <div class="dado-item">
                <span>👤</span> 
                <span><strong>Cliente:</strong> [NOME_TITULAR]<br>e [NOME_CONJUGE]</span>
            </div>
            <div class="dado-item">
                <span>🍴</span> 
                <span><strong>Cortesia:</strong> VOUCHER CONSUMAÇÃO - R$ 100,00</span>
            </div>
            <div class="dado-item">
                <span>📄</span> 
                <span><strong>Descrição:</strong> O Itacaré Vacation Club tem o prazer de proporcionar através deste voucher uma consumação no valor de R$ 100,00 para ser utilizada na Cabana Terra Boa, </span>
            </div>
            <div class="dado-item">
                <span>👤</span> 
                <span><strong>CPF:</strong> [CPF]</span>
            </div>
        </div>

        <div class="empresa-direita">
            <div class="badge-via" style="width: 120px;">2ª Via (Empresa)</div>
            
            <div class="caixa-assinatura">
                <h3>ASSINATURA DO CLIENTE</h3>
                <p>Declaro que recebi a cortesia descrita acima.</p>
                <div class="linha-ass-caixa"></div>
                <div class="label-ass">Assinatura</div>
                
                <div class="caixa-rodape">
                    <div class="nome" style="text-align: center;">[NOME_CURTO]</div>
                    <div class="data" style="text-align: center;">[DATA_IMPRESSAO]</div>
                </div>
            </div>
        </div>

        <div class="equipe-vendas" style="bottom: 50px;">
            <div class="equipe-item"><span class="icone-user">👤</span> Promotor: [PROMOTOR]</div>
            <div class="divisor-v"></div>
            <div class="equipe-item"><span class="icone-user">👤</span> Consultor: [CONSULTOR]</div>
            <div class="divisor-v"></div>
            <div class="equipe-item"><span class="icone-user">👤</span> Supervisor: [SUPERVISOR]</div>
        </div>

    </div>

</div>
HTML;
        $item->save();

        $item = new ComplimentaryItem();
        $item->name = 'Day Use - Terra Boa';
        $item->code = 'CORT-NEW-107';
        $item->template_type = 'html';
        $item->metadata = '';
        $item->content = <<<'HTML'
<style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap');

    :root {
        --text-dark: #000000;
        --green-primary: #5c7254;
        --gray-light: #666666;
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        font-family: 'Montserrat', sans-serif;
    }

    body {
        background-color: #e0e0e0;
        padding: 20px;
        display: flex;
        justify-content: center;
    }

    /* Contêiner principal com a imagem de fundo */
    .voucher-wrapper {
        width: 1000px;
        height: 654px; /* Ajuste a altura conforme a proporção real da sua imagem */
        background-image: url('/images/fundo-voucher-jantar.png');
        background-size: 100% 100%;
        background-repeat: no-repeat;
        background-position: center;
        position: relative;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        color: var(--text-dark);
        margin: 0 auto;
    }

    /* Divisões virtuais das metades */
    .via-cliente {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 50%;
    }

    .via-empresa {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 50%;
    }

    /* ================================
       VIA CLIENTE (PARTE DE CIMA)
    ================================ */

    /* Texto Central */
    .cliente-centro {
        position: absolute;
        top: 25px;
        left: 280px;
        width: 480px;
        text-align: center;
    }

    .titulo-voucher {
        font-size: 18px;
        font-weight: 600;
        letter-spacing: 2px;
        color: var(--green-primary);
        margin-top: 30px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .titulo-voucher::before, .titulo-voucher::after {
        content: "";
        display: inline-block;
        width: 40px;
        height: 1px;
        background-color: var(--gray-light);
        margin: 0 15px;
    }

    .texto-principal {
        font-size: 12px;
        line-height: 1.6;
        margin-bottom: 10px;
    }

    .obs-garcom {
        font-size: 11px;
        margin-bottom: 15px;
    }

    /* Assinaturas Cliente */
    .cliente-assinaturas {
        display: flex;
        justify-content: space-between;
        font-size: 11px;
        margin-bottom: 10px;
        text-align: left;
    }

    .linha-ass {
        border-bottom: 1px solid var(--text-dark);
        padding-bottom: 4px;
    }

    .ass-nome { width: 65%; }
    .ass-gerente { width: 30%; text-align: center; }

    .rodape-obs {
        font-size: 10px;
        text-align: left;
        line-height: 1.4;
    }

    /* Info Direita (QR e Via) */
    .cliente-direita {
        position: absolute;
        top: 20px;
        right: 40px;
        width: 180px;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
    }

    .qr-code-img {
        width: 85px;
        height: 85px;
        margin-bottom: 15px;
        margin-right: 15px;
    }

    .badge-via {
        background-color: var(--green-primary);
        color: white;
        padding: 4px 0;
        width: 100%;
        text-align: center;
        font-size: 11px;
        border-radius: 2px;
        margin-bottom: 15px;
    }

    .info-dados {
        font-size: 11px;
        width: 100%;
    }

    .info-dados div {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
    }

    /* Equipe de Vendas (Ambas as vias) */
    .equipe-vendas {
        position: absolute;
        bottom: 25px; /* Ajuste para encaixar antes da onda azul */
        left: 0;
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 30px;
        font-size: 11px;
        font-weight: 600;
    }
    
    /* Cor branca para a equipe na primeira via por causa do fundo azul escuro */
    .via-cliente .equipe-vendas {
        color: #000000;
        bottom: 15px; 
    }

    .equipe-item {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .icone-user {
        width: 18px;
        height: 18px;
        background-color: var(--green-primary);
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        color: white;
        font-size: 10px;
    }

    .divisor-v {
        width: 1px;
        height: 15px;
        background-color: #ccc;
    }

    /* ================================
       VIA EMPRESA (PARTE DE BAIXO)
    ================================ */

    .empresa-dados {
        position: absolute;
        top: 40px;
        left: 260px;
        width: 320px;
        font-size: 11px;
        line-height: 1.6;
    }

    .protocolo-titulo {
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 15px;
        color: var(--text-dark);
    }

    .dado-item {
        display: flex;
        gap: 8px;
        margin-bottom: 8px;
    }

    .empresa-direita {
        position: absolute;
        top: 20px;
        right: 40px;
        width: 320px;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
    }

    /* Caixa de Assinatura */
    .caixa-assinatura {
        width: 100%;
        border: 1px solid #999;
        border-radius: 8px;
        padding: 15px 20px;
        text-align: center;
        margin-top: 5px;
    }

    .caixa-assinatura h3 {
        font-size: 12px;
        color: var(--green-primary);
        margin-bottom: 8px;
    }

    .caixa-assinatura p {
        font-size: 10px;
        margin-bottom: 35px;
    }

    .linha-ass-caixa {
        border-bottom: 1px solid var(--text-dark);
        margin-bottom: 5px;
    }

    .label-ass {
        font-size: 9px;
        margin-bottom: 15px;
    }

    .caixa-rodape {
        display: flex;
        justify-content: space-between;
        font-size: 10px;
    }

    .caixa-rodape .nome { width: 55%; border-bottom: 1px solid var(--text-dark); text-align: left;}
    .caixa-rodape .data { width: 40%; border-bottom: 1px solid var(--text-dark); text-align: right;}

    @media print {
        @page {
            margin: 0; 
            size: A4 portrait;
        }
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            background-color: white !important;
            overflow-x: hidden !important;
            width: 100% !important;
        }
        .voucher-wrapper {
            margin: 0 !important;
            position: relative !important;
            left: 0 !important;
            transform: scale(0.793) !important;
            transform-origin: top left !important;
            box-shadow: none !important;
        }
    }
</style>

<div class="voucher-wrapper">
    
    <!-- ==================== 1ª VIA (CLIENTE) ==================== -->
    <div class="via-cliente">
        
        <div class="cliente-centro">
            <div class="titulo-voucher">VOUCHER DAY USE</div>
            <div class="texto-principal">
                O Itacaré Vacation Club tem o prazer de proporcionar através deste voucher um <strong>Day Use</strong> para ser utilizado nas dependências do <strong>Terra Boa Hotel Boutique</strong>,<br>em nome de: <strong>[NOME_TITULAR] e [NOME_CONJUGE]</strong>.
            </div>
            <div class="obs-garcom">OBS: Ao chegar, informe à recepção sobre este voucher.</div>

            <div class="cliente-assinaturas">
                <div class="linha-ass ass-nome">Nome: [NOME_TITULAR]</div>
                <div class="linha-ass ass-gerente">GERENTE</div>
            </div>

            <div class="rodape-obs">
                <strong>OBS:</strong> Essa cortesia é pessoal e intransferível / Esse voucher deve ser entregue à recepção do hotel no momento do acesso.<br>
                <strong>válido por 30 dias da data de emissão</strong>
            </div>
        </div>

        <div class="cliente-direita">
            <!-- QR Code Dinâmico com a Tag [QR_CODE] -->
            [QR_CODE]
            <div class="badge-via">1ª Via (Cliente)</div>
            <div class="info-dados">
                <div><strong>Data:</strong> <span>[DATA]</span></div>
                <div><strong>Nº:</strong> <span>CO-[ID_ATENDIMENTO]</span></div>
            </div>
        </div>

        <div class="equipe-vendas">
            <div class="equipe-item"><span class="icone-user">👤</span> Promotor: [PROMOTOR]</div>
            <div class="divisor-v"></div>
            <div class="equipe-item"><span class="icone-user">👤</span> Consultor: [CONSULTOR]</div>
            <div class="divisor-v"></div>
            <div class="equipe-item"><span class="icone-user">👤</span> Supervisor: [SUPERVISOR]</div>
        </div>

    </div>

    <!-- ==================== 2ª VIA (EMPRESA) ==================== -->
    <div class="via-empresa">
        
        <div class="empresa-dados">
            <div class="protocolo-titulo">PROTOCOLO Nº: CO-[ID_ATENDIMENTO]</div>
            <div class="dado-item">
                <span>📅</span> 
                <span><strong>Data:</strong> [DATA]</span>
            </div>
            <div class="dado-item">
                <span>👤</span> 
                <span><strong>Cliente:</strong> [NOME_TITULAR]<br>e [NOME_CONJUGE]</span>
            </div>
            <div class="dado-item">
                <span>🍴</span> 
                <span><strong>Cortesia:</strong> VOUCHER DAY USE</span>
            </div>
            <div class="dado-item">
                <span>📄</span> 
                <span><strong>Descrição:</strong> O Itacaré Vacation Club tem o prazer de proporcionar através deste voucher um Day Use para ser utilizado nas dependências do Terra Boa Hotel Boutique, </span>
            </div>
            <div class="dado-item">
                <span>👤</span> 
                <span><strong>CPF:</strong> [CPF]</span>
            </div>
        </div>

        <div class="empresa-direita">
            <div class="badge-via" style="width: 120px;">2ª Via (Empresa)</div>
            
            <div class="caixa-assinatura">
                <h3>ASSINATURA DO CLIENTE</h3>
                <p>Declaro que recebi a cortesia descrita acima.</p>
                <div class="linha-ass-caixa"></div>
                <div class="label-ass">Assinatura</div>
                
                <div class="caixa-rodape">
                    <div class="nome" style="text-align: center;">[NOME_CURTO]</div>
                    <div class="data" style="text-align: center;">[DATA_IMPRESSAO]</div>
                </div>
            </div>
        </div>

        <div class="equipe-vendas" style="bottom: 50px;">
            <div class="equipe-item"><span class="icone-user">👤</span> Promotor: [PROMOTOR]</div>
            <div class="divisor-v"></div>
            <div class="equipe-item"><span class="icone-user">👤</span> Consultor: [CONSULTOR]</div>
            <div class="divisor-v"></div>
            <div class="equipe-item"><span class="icone-user">👤</span> Supervisor: [SUPERVISOR]</div>
        </div>

    </div>

</div>
HTML;
        $item->save();

        $item = new ComplimentaryItem();
        $item->name = 'Day Use - Resende Imperial';
        $item->code = 'CORT-NEW-108';
        $item->template_type = 'html';
        $item->metadata = '';
        $item->content = <<<'HTML'
<style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap');

    :root {
        --text-dark: #000000;
        --green-primary: #5c7254;
        --gray-light: #666666;
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        font-family: 'Montserrat', sans-serif;
    }

    body {
        background-color: #e0e0e0;
        padding: 20px;
        display: flex;
        justify-content: center;
    }

    /* Contêiner principal com a imagem de fundo */
    .voucher-wrapper {
        width: 1000px;
        height: 654px; /* Ajuste a altura conforme a proporção real da sua imagem */
        background-image: url('/images/fundo-voucher-jantar.png');
        background-size: 100% 100%;
        background-repeat: no-repeat;
        background-position: center;
        position: relative;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        color: var(--text-dark);
        margin: 0 auto;
    }

    /* Divisões virtuais das metades */
    .via-cliente {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 50%;
    }

    .via-empresa {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 50%;
    }

    /* ================================
       VIA CLIENTE (PARTE DE CIMA)
    ================================ */

    /* Texto Central */
    .cliente-centro {
        position: absolute;
        top: 25px;
        left: 280px;
        width: 480px;
        text-align: center;
    }

    .titulo-voucher {
        font-size: 18px;
        font-weight: 600;
        letter-spacing: 2px;
        color: var(--green-primary);
        margin-top: 30px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .titulo-voucher::before, .titulo-voucher::after {
        content: "";
        display: inline-block;
        width: 40px;
        height: 1px;
        background-color: var(--gray-light);
        margin: 0 15px;
    }

    .texto-principal {
        font-size: 12px;
        line-height: 1.6;
        margin-bottom: 10px;
    }

    .obs-garcom {
        font-size: 11px;
        margin-bottom: 15px;
    }

    /* Assinaturas Cliente */
    .cliente-assinaturas {
        display: flex;
        justify-content: space-between;
        font-size: 11px;
        margin-bottom: 10px;
        text-align: left;
    }

    .linha-ass {
        border-bottom: 1px solid var(--text-dark);
        padding-bottom: 4px;
    }

    .ass-nome { width: 65%; }
    .ass-gerente { width: 30%; text-align: center; }

    .rodape-obs {
        font-size: 10px;
        text-align: left;
        line-height: 1.4;
    }

    /* Info Direita (QR e Via) */
    .cliente-direita {
        position: absolute;
        top: 20px;
        right: 40px;
        width: 180px;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
    }

    .qr-code-img {
        width: 85px;
        height: 85px;
        margin-bottom: 15px;
        margin-right: 15px;
    }

    .badge-via {
        background-color: var(--green-primary);
        color: white;
        padding: 4px 0;
        width: 100%;
        text-align: center;
        font-size: 11px;
        border-radius: 2px;
        margin-bottom: 15px;
    }

    .info-dados {
        font-size: 11px;
        width: 100%;
    }

    .info-dados div {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
    }

    /* Equipe de Vendas (Ambas as vias) */
    .equipe-vendas {
        position: absolute;
        bottom: 25px; /* Ajuste para encaixar antes da onda azul */
        left: 0;
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 30px;
        font-size: 11px;
        font-weight: 600;
    }
    
    /* Cor branca para a equipe na primeira via por causa do fundo azul escuro */
    .via-cliente .equipe-vendas {
        color: #000000;
        bottom: 15px; 
    }

    .equipe-item {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .icone-user {
        width: 18px;
        height: 18px;
        background-color: var(--green-primary);
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        color: white;
        font-size: 10px;
    }

    .divisor-v {
        width: 1px;
        height: 15px;
        background-color: #ccc;
    }

    /* ================================
       VIA EMPRESA (PARTE DE BAIXO)
    ================================ */

    .empresa-dados {
        position: absolute;
        top: 40px;
        left: 260px;
        width: 320px;
        font-size: 11px;
        line-height: 1.6;
    }

    .protocolo-titulo {
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 15px;
        color: var(--text-dark);
    }

    .dado-item {
        display: flex;
        gap: 8px;
        margin-bottom: 8px;
    }

    .empresa-direita {
        position: absolute;
        top: 20px;
        right: 40px;
        width: 320px;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
    }

    /* Caixa de Assinatura */
    .caixa-assinatura {
        width: 100%;
        border: 1px solid #999;
        border-radius: 8px;
        padding: 15px 20px;
        text-align: center;
        margin-top: 5px;
    }

    .caixa-assinatura h3 {
        font-size: 12px;
        color: var(--green-primary);
        margin-bottom: 8px;
    }

    .caixa-assinatura p {
        font-size: 10px;
        margin-bottom: 35px;
    }

    .linha-ass-caixa {
        border-bottom: 1px solid var(--text-dark);
        margin-bottom: 5px;
    }

    .label-ass {
        font-size: 9px;
        margin-bottom: 15px;
    }

    .caixa-rodape {
        display: flex;
        justify-content: space-between;
        font-size: 10px;
    }

    .caixa-rodape .nome { width: 55%; border-bottom: 1px solid var(--text-dark); text-align: left;}
    .caixa-rodape .data { width: 40%; border-bottom: 1px solid var(--text-dark); text-align: right;}

    @media print {
        @page {
            margin: 0; 
            size: A4 portrait;
        }
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            background-color: white !important;
            overflow-x: hidden !important;
            width: 100% !important;
        }
        .voucher-wrapper {
            margin: 0 !important;
            position: relative !important;
            left: 0 !important;
            transform: scale(0.793) !important;
            transform-origin: top left !important;
            box-shadow: none !important;
        }
    }
</style>

<div class="voucher-wrapper">
    
    <!-- ==================== 1ª VIA (CLIENTE) ==================== -->
    <div class="via-cliente">
        
        <div class="cliente-centro">
            <div class="titulo-voucher">VOUCHER DAY USE</div>
            <div class="texto-principal">
                O Itacaré Vacation Club tem o prazer de proporcionar através deste voucher um <strong>Day Use</strong> para ser utilizado nas dependências do <strong>Resende Imperial Hotel & Spa</strong>,<br>em nome de: <strong>[NOME_TITULAR] e [NOME_CONJUGE]</strong>.
            </div>
            <div class="obs-garcom">OBS: Ao chegar, informe à recepção sobre este voucher.</div>

            <div class="cliente-assinaturas">
                <div class="linha-ass ass-nome">Nome: [NOME_TITULAR]</div>
                <div class="linha-ass ass-gerente">GERENTE</div>
            </div>

            <div class="rodape-obs">
                <strong>OBS:</strong> Essa cortesia é pessoal e intransferível / Esse voucher deve ser entregue à recepção do hotel no momento do acesso.<br>
                <strong>válido por 30 dias da data de emissão</strong>
            </div>
        </div>

        <div class="cliente-direita">
            <!-- QR Code Dinâmico com a Tag [QR_CODE] -->
            [QR_CODE]
            <div class="badge-via">1ª Via (Cliente)</div>
            <div class="info-dados">
                <div><strong>Data:</strong> <span>[DATA]</span></div>
                <div><strong>Nº:</strong> <span>CO-[ID_ATENDIMENTO]</span></div>
            </div>
        </div>

        <div class="equipe-vendas">
            <div class="equipe-item"><span class="icone-user">👤</span> Promotor: [PROMOTOR]</div>
            <div class="divisor-v"></div>
            <div class="equipe-item"><span class="icone-user">👤</span> Consultor: [CONSULTOR]</div>
            <div class="divisor-v"></div>
            <div class="equipe-item"><span class="icone-user">👤</span> Supervisor: [SUPERVISOR]</div>
        </div>

    </div>

    <!-- ==================== 2ª VIA (EMPRESA) ==================== -->
    <div class="via-empresa">
        
        <div class="empresa-dados">
            <div class="protocolo-titulo">PROTOCOLO Nº: CO-[ID_ATENDIMENTO]</div>
            <div class="dado-item">
                <span>📅</span> 
                <span><strong>Data:</strong> [DATA]</span>
            </div>
            <div class="dado-item">
                <span>👤</span> 
                <span><strong>Cliente:</strong> [NOME_TITULAR]<br>e [NOME_CONJUGE]</span>
            </div>
            <div class="dado-item">
                <span>🍴</span> 
                <span><strong>Cortesia:</strong> VOUCHER DAY USE</span>
            </div>
            <div class="dado-item">
                <span>📄</span> 
                <span><strong>Descrição:</strong> O Itacaré Vacation Club tem o prazer de proporcionar através deste voucher um Day Use para ser utilizado nas dependências do Resende Imperial Hotel & Spa, </span>
            </div>
            <div class="dado-item">
                <span>👤</span> 
                <span><strong>CPF:</strong> [CPF]</span>
            </div>
        </div>

        <div class="empresa-direita">
            <div class="badge-via" style="width: 120px;">2ª Via (Empresa)</div>
            
            <div class="caixa-assinatura">
                <h3>ASSINATURA DO CLIENTE</h3>
                <p>Declaro que recebi a cortesia descrita acima.</p>
                <div class="linha-ass-caixa"></div>
                <div class="label-ass">Assinatura</div>
                
                <div class="caixa-rodape">
                    <div class="nome" style="text-align: center;">[NOME_CURTO]</div>
                    <div class="data" style="text-align: center;">[DATA_IMPRESSAO]</div>
                </div>
            </div>
        </div>

        <div class="equipe-vendas" style="bottom: 50px;">
            <div class="equipe-item"><span class="icone-user">👤</span> Promotor: [PROMOTOR]</div>
            <div class="divisor-v"></div>
            <div class="equipe-item"><span class="icone-user">👤</span> Consultor: [CONSULTOR]</div>
            <div class="divisor-v"></div>
            <div class="equipe-item"><span class="icone-user">👤</span> Supervisor: [SUPERVISOR]</div>
        </div>

    </div>

</div>
HTML;
        $item->save();

    }
}
