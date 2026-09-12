<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $roles = \App\Models\CommissionRule::pluck('name')->toArray();
    $count = app(\App\Services\CommissionService::class)->generateCommissionsBulk(8, 2026, $roles);
    echo "Gerou comissoes para $count propostas em Agosto/2026.\n";
    
    $commissions = \App\Models\Commission::with('installments')->get();
    foreach ($commissions as $c) {
        echo "Role: {$c->role} | Origin: {$c->origin_type} | Total: {$c->total_amount}\n";
    }
} catch (\Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
