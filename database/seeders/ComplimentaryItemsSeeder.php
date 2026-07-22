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
        $item->name = 'Voucher Jantar - Itacaré';
        $item->code = 'VJ_ITAC';
        $item->template_type = 'html';
        $item->metadata = '';
        $item->is_active = true;
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
        $item->is_active = true;
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
        $item->is_active = true;
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
        $item->is_active = true;
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
        $item->is_active = true;
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
        $item->is_active = true;
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
        $item->is_active = true;
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
        $item->is_active = true;
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
        $item->is_active = true;
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
        $item->is_active = true;
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
