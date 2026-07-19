<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$u = \App\Models\User::where('email', 'p@gmail.com')->first();
if (!$u) {
    echo "User not found\n";
    exit;
}

echo "USER: " . $u->name . "\n";
echo "ROLES: " . json_encode($u->roles->pluck('name')) . "\n";
echo "DIRECT PERMS: " . json_encode($u->permissions->pluck('name')) . "\n";
echo "ROLE PERMS: " . json_encode($u->getPermissionsViaRoles()->pluck('name')) . "\n";
echo "ALL PERMS: " . json_encode($u->getAllPermissions()->pluck('name')) . "\n";
