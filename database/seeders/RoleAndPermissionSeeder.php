<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Permissions
        $permissions = [
            // Usuários
            ['name' => 'Ver Usuários', 'slug' => 'usuarios.ver', 'group' => 'Usuários'],
            ['name' => 'Criar Usuários', 'slug' => 'usuarios.criar', 'group' => 'Usuários'],
            ['name' => 'Editar Usuários', 'slug' => 'usuarios.editar', 'group' => 'Usuários'],
            ['name' => 'Excluir Usuários', 'slug' => 'usuarios.deletar', 'group' => 'Usuários'],
            ['name' => 'Alterar Status', 'slug' => 'usuarios.status', 'group' => 'Usuários'],
            
            // Funcionários
            ['name' => 'Ver Funcionários', 'slug' => 'funcionarios.ver', 'group' => 'Funcionários'],
            ['name' => 'Gerenciar Funcionários', 'slug' => 'funcionarios.gerenciar', 'group' => 'Funcionários'],
            
            // Sistema / RBAC
            ['name' => 'Gerenciar Cargos', 'slug' => 'cargos.gerenciar', 'group' => 'Sistema'],
            
            // Vendas / Atendimento
            ['name' => 'Ver Atendimentos', 'slug' => 'atendimentos.ver', 'group' => 'Vendas'],
            ['name' => 'Gerenciar Atendimentos', 'slug' => 'atendimentos.gerenciar', 'group' => 'Vendas'],
        ];

        foreach ($permissions as $p) {
            \App\Models\Permission::updateOrCreate(['slug' => $p['slug']], $p);
        }

        // 2. Create Roles
        $adminRole = \App\Models\Role::updateOrCreate(['slug' => 'admin'], [
            'name' => 'Administrador',
            'description' => 'Acesso total ao sistema',
        ]);

        $managerRole = \App\Models\Role::updateOrCreate(['slug' => 'manager'], [
            'name' => 'Gerente',
            'description' => 'Gestão de equipe e vendas',
        ]);

        $operatorRole = \App\Models\Role::updateOrCreate(['slug' => 'operator'], [
            'name' => 'Operador',
            'description' => 'Acesso básico aos atendimentos',
        ]);

        // 3. Assign all permissions to Admin
        $allPermissions = \App\Models\Permission::all();
        $adminRole->permissions()->sync($allPermissions->pluck('id'));

        // 4. Assign specific permissions to Manager
        $managerPermissions = \App\Models\Permission::whereIn('group', ['Usuários', 'Funcionários', 'Vendas'])->get();
        $managerRole->permissions()->sync($managerPermissions->pluck('id'));

        // 5. Assign specific permissions to Operator
        $operatorPermissions = \App\Models\Permission::whereIn('slug', ['atendimentos.ver', 'atendimentos.gerenciar'])->get();
        $operatorRole->permissions()->sync($operatorPermissions->pluck('id'));

        // 6. Assign Admin role to existing users for testing
        $users = \App\Models\User::all();
        foreach ($users as $user) {
            $user->roles()->syncWithoutDetaching([$adminRole->id]);
        }
    }
}
