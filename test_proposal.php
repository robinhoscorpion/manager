<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$p = \App\Models\Proposal::where('sales_service_id', 39)->first();
dump($p ? $p->toArray() : 'no proposal');
if ($p) {
    dump($p->product ? $p->product->toArray() : 'no product');
}
