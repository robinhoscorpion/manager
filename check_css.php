<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$item = \App\Models\ComplimentaryItem::find(6);
$html = $item->content;
$css_pos = strpos($html, '.caixa-rodape');
echo substr($html, $css_pos - 50, 400);
