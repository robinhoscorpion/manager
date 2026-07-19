<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$users = \App\Models\User::all();
$adminRole = \App\Models\Role::where('slug', 'admin')->first();

$keepAdmins = ['admin@admin.com', 'superadmin@admin.com', 'rui@rui.com'];

if ($adminRole) {
    foreach ($users as $user) {
        if (!in_array($user->email, $keepAdmins)) {
            $user->roles()->detach($adminRole->id);
            echo "Removed admin from " . $user->email . "\n";
        }
    }
}
