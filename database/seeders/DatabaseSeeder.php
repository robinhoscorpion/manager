<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Criar ou Obter Role Admin
        $adminRole = \Illuminate\Support\Facades\DB::table('roles')->where('slug', 'admin')->first();
        if (!$adminRole) {
            $adminRoleId = \Illuminate\Support\Facades\DB::table('roles')->insertGetId([
                'name' => 'Administrador',
                'slug' => 'admin',
                'description' => 'Acesso total ao sistema',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $adminRoleId = $adminRole->id;
        }

        // 2. Definir Todas as Permissões do Sistema
        $permissions = [
            ['name' => 'Gerenciar Atendimentos', 'slug' => 'atendimentos.gerenciar', 'group' => 'Vendas'],
            ['name' => 'Visualizar Atendimentos', 'slug' => 'atendimentos.ver', 'group' => 'Vendas'],
            ['name' => 'Gerenciar Funcionários', 'slug' => 'funcionarios.gerenciar', 'group' => 'RH'],
            ['name' => 'Visualizar Funcionários', 'slug' => 'funcionarios.ver', 'group' => 'RH'],
            ['name' => 'Visualizar Usuários', 'slug' => 'usuarios.ver', 'group' => 'Segurança'],
            ['name' => 'Criar Usuários', 'slug' => 'usuarios.criar', 'group' => 'Segurança'],
            ['name' => 'Editar Usuários', 'slug' => 'usuarios.editar', 'group' => 'Segurança'],
            ['name' => 'Deletar Usuários', 'slug' => 'usuarios.deletar', 'group' => 'Segurança'],
            ['name' => 'Alterar Status Usuários', 'slug' => 'usuarios.status', 'group' => 'Segurança'],
            ['name' => 'Gerenciar Cargos', 'slug' => 'cargos.gerenciar', 'group' => 'Segurança'],
        ];

        foreach ($permissions as $permData) {
            $perm = \Illuminate\Support\Facades\DB::table('permissions')->where('slug', $permData['slug'])->first();
            if (!$perm) {
                $permId = \Illuminate\Support\Facades\DB::table('permissions')->insertGetId(array_merge($permData, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            } else {
                $permId = $perm->id;
            }

            // Vincular à Role Admin (se não estiver vinculado)
            $exists = \Illuminate\Support\Facades\DB::table('role_permission')
                ->where('role_id', $adminRoleId)
                ->where('permission_id', $permId)
                ->exists();
            
            if (!$exists) {
                \Illuminate\Support\Facades\DB::table('role_permission')->insert([
                    'role_id' => $adminRoleId,
                    'permission_id' => $permId
                ]);
            }
        }

        // 3. Criar Usuário Admin se não existir
        $user = User::where('email', 'admin@admin.com')->first();
        if (!$user) {
            $user = User::create([
                'name' => 'Administrador',
                'email' => 'admin@admin.com',
                'password' => \Illuminate\Support\Facades\Hash::make('12345678'),
                'email_verified_at' => now(),
            ]);
        }

        // 4. Vincular Usuário à Role (se não estiver vinculado)
        $userRoleExists = \Illuminate\Support\Facades\DB::table('user_role')
            ->where('user_id', $user->id)
            ->where('role_id', $adminRoleId)
            ->exists();

        if (!$userRoleExists) {
            \Illuminate\Support\Facades\DB::table('user_role')->insert([
                'user_id' => $user->id,
                'role_id' => $adminRoleId
            ]);
        }
    }
}
