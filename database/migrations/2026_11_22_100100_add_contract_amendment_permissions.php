<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $permissions = [
        ['name' => 'Acessar Upgrade/Downgrade', 'slug' => 'pos_venda.alteracao_contrato.acessar', 'group' => 'Pós-venda'],
        ['name' => 'Gerenciar Upgrade/Downgrade', 'slug' => 'pos_venda.alteracao_contrato.gerenciar', 'group' => 'Pós-venda'],
    ];

    public function up(): void
    {
        $adminRoleId = DB::table('roles')->where('slug', 'admin')->value('id');

        foreach ($this->permissions as $perm) {
            $id = DB::table('permissions')->where('slug', $perm['slug'])->value('id');
            if (!$id) {
                $id = DB::table('permissions')->insertGetId(array_merge($perm, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }

            if ($adminRoleId) {
                DB::table('role_permission')->insertOrIgnore([
                    'role_id' => $adminRoleId,
                    'permission_id' => $id,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('slug', array_column($this->permissions, 'slug'))->delete();
    }
};
