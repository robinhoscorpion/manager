<?php
$u = \App\Models\User::where('email', 'p@gmail.com')->first(); 
if (!$u) {
    echo "User not found\n";
    exit;
}
echo 'Roles: ' . $u->roles->pluck('slug')->implode(',') . "\n";
echo 'Perms: ' . $u->roles()->with('permissions')->get()->pluck('permissions')->flatten()->pluck('slug')->unique()->implode(',') . "\n";
