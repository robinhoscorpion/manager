<?php
$users = App\Models\User::with('roles')->get();
foreach($users as $u) {
    echo $u->name . ' - Roles: ' . $u->roles->pluck('name')->join(', ') . "\n";
}
echo "\nEMPLOYEES:\n";
$emps = App\Models\Employee::all();
foreach($emps as $e) {
    echo $e->name . ' - Position: ' . $e->position . ' - Role: ' . $e->role . "\n";
}
