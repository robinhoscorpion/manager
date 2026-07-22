<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$item = \App\Models\ComplimentaryItem::find(6);
$html = $item->content;
$pos = strpos($html, 'ASSINATURA DO CLIENTE');
echo substr($html, $pos - 100, 500);
