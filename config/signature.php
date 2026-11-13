<?php

/*
|--------------------------------------------------------------------------
| Assinatura digital de contratos
|--------------------------------------------------------------------------
|
| Provedores disponíveis para o empreendimento escolher em
| Configurações → Assinatura Digital. Para integrar um novo serviço
| (ClickSign, ZapSign, D4Sign...), crie uma classe que implemente
| App\Services\Signature\Contracts\SignatureProvider e registre aqui.
|
*/

return [

    'providers' => [
        'manual' => App\Services\Signature\Providers\ManualProvider::class,
        'autentique' => App\Services\Signature\Providers\AutentiqueProvider::class,
    ],

    // Disco e pasta onde os PDFs (enviados e assinados) são guardados
    'disk' => 'local',
    'directory' => 'contracts/signatures',

    // Timeout (segundos) das chamadas HTTP aos provedores
    'http_timeout' => 60,

];
