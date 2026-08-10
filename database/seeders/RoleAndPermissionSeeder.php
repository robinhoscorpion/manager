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
        $permissions = [
            // ================= Dashboard =================
            ['name' => 'Acessar Dashboard', 'slug' => 'dashboard.acessar', 'group' => 'Dashboard'],

            // ================= Sala de Vendas =================
            ['name' => 'Acessar Agendamentos', 'slug' => 'agendamentos.acessar', 'group' => 'Sala de Vendas'],
            ['name' => 'Gerenciar Agendamentos', 'slug' => 'agendamentos.gerenciar', 'group' => 'Sala de Vendas'],
            ['name' => 'Ver Todos os Agendamentos', 'slug' => 'agendamentos.ver_todos', 'group' => 'Sala de Vendas'],
            ['name' => 'Acessar Atendimentos', 'slug' => 'atendimentos.acessar', 'group' => 'Sala de Vendas'],
            ['name' => 'Ver Todos os Atendimentos', 'slug' => 'atendimentos.ver_todos', 'group' => 'Sala de Vendas'],
            ['name' => 'Criar Atendimentos', 'slug' => 'atendimentos.criar', 'group' => 'Sala de Vendas'],
            ['name' => 'Editar Atendimentos', 'slug' => 'atendimentos.editar', 'group' => 'Sala de Vendas'],
            ['name' => 'Editar Contrato', 'slug' => 'atendimentos.editar_contrato', 'group' => 'Sala de Vendas'],
            ['name' => 'Excluir Atendimentos', 'slug' => 'atendimentos.deletar', 'group' => 'Sala de Vendas'],
            ['name' => 'Alterar Qualificação', 'slug' => 'atendimentos.alterar_qualificacao', 'group' => 'Sala de Vendas'],
            ['name' => 'Alterar Status', 'slug' => 'atendimentos.mudar_status', 'group' => 'Sala de Vendas'],

            // ================= Financeiro =================
            ['name' => 'Acessar Recebíveis', 'slug' => 'recebiveis.acessar', 'group' => 'Financeiro'],
            ['name' => 'Gerenciar Recebíveis', 'slug' => 'recebiveis.gerenciar', 'group' => 'Financeiro'],
            
            ['name' => 'Acessar Controle de Vendas', 'slug' => 'controle_vendas.acessar', 'group' => 'Financeiro'],
            ['name' => 'Gerenciar Controle de Vendas', 'slug' => 'controle_vendas.gerenciar', 'group' => 'Financeiro'],

            // ================= Pós-venda =================
            ['name' => 'Acessar Boas-vindas', 'slug' => 'pos_venda.boas_vindas.acessar', 'group' => 'Pós-venda'],
            ['name' => 'Gerenciar Boas-vindas', 'slug' => 'pos_venda.boas_vindas.gerenciar', 'group' => 'Pós-venda'],
            
            ['name' => 'Acessar Gestão de Contratos', 'slug' => 'pos_venda.gestao_contratos.acessar', 'group' => 'Pós-venda'],
            ['name' => 'Gerenciar Gestão de Contratos', 'slug' => 'pos_venda.gestao_contratos.gerenciar', 'group' => 'Pós-venda'],
            
            ['name' => 'Acessar Protocolos', 'slug' => 'pos_venda.protocolos.acessar', 'group' => 'Pós-venda'],
            ['name' => 'Criar Protocolos', 'slug' => 'pos_venda.protocolos.criar', 'group' => 'Pós-venda'],
            ['name' => 'Gerenciar Protocolos', 'slug' => 'pos_venda.protocolos.gerenciar', 'group' => 'Pós-venda'],
            ['name' => 'Excluir Protocolos', 'slug' => 'pos_venda.protocolos.excluir', 'group' => 'Pós-venda'],
            
            ['name' => 'Acessar Onboarding', 'slug' => 'pos_venda.onboarding.acessar', 'group' => 'Pós-venda'],
            ['name' => 'Gerenciar Onboarding', 'slug' => 'pos_venda.onboarding.gerenciar', 'group' => 'Pós-venda'],
            
            ['name' => 'Acessar Pendências documentais', 'slug' => 'pos_venda.pendencias.acessar', 'group' => 'Pós-venda'],
            ['name' => 'Gerenciar Pendências documentais', 'slug' => 'pos_venda.pendencias.gerenciar', 'group' => 'Pós-venda'],
            
            ['name' => 'Acessar Treinamentos', 'slug' => 'pos_venda.treinamentos.acessar', 'group' => 'Pós-venda'],
            ['name' => 'Gerenciar Treinamentos', 'slug' => 'pos_venda.treinamentos.gerenciar', 'group' => 'Pós-venda'],
            
            ['name' => 'Acessar Acompanhamentos', 'slug' => 'pos_venda.acompanhamentos.acessar', 'group' => 'Pós-venda'],
            ['name' => 'Gerenciar Acompanhamentos', 'slug' => 'pos_venda.acompanhamentos.gerenciar', 'group' => 'Pós-venda'],
            
            ['name' => 'Acessar Campanhas de relacionamento', 'slug' => 'pos_venda.campanhas.acessar', 'group' => 'Pós-venda'],
            ['name' => 'Gerenciar Campanhas de relacionamento', 'slug' => 'pos_venda.campanhas.gerenciar', 'group' => 'Pós-venda'],
            
            ['name' => 'Acessar Aniversariantes', 'slug' => 'pos_venda.aniversariantes.acessar', 'group' => 'Pós-venda'],
            ['name' => 'Gerenciar Aniversariantes', 'slug' => 'pos_venda.aniversariantes.gerenciar', 'group' => 'Pós-venda'],
            
            ['name' => 'Acessar Reservas', 'slug' => 'pos_venda.reservas.acessar', 'group' => 'Pós-venda'],
            ['name' => 'Gerenciar Reservas', 'slug' => 'pos_venda.reservas.gerenciar', 'group' => 'Pós-venda'],
            
            ['name' => 'Acessar Cancelamentos', 'slug' => 'pos_venda.cancelamentos.acessar', 'group' => 'Pós-venda'],
            ['name' => 'Gerenciar Cancelamentos', 'slug' => 'pos_venda.cancelamentos.gerenciar', 'group' => 'Pós-venda'],

            // ================= Funcionários =================
            ['name' => 'Acessar Funcionários', 'slug' => 'funcionarios.acessar', 'group' => 'Funcionários'],
            ['name' => 'Gerenciar Funcionários', 'slug' => 'funcionarios.gerenciar', 'group' => 'Funcionários'],
            ['name' => 'Excluir Funcionários', 'slug' => 'funcionarios.deletar', 'group' => 'Funcionários'],

            // ================= Usuários =================
            ['name' => 'Acessar Usuários', 'slug' => 'usuarios.acessar', 'group' => 'Usuários'],
            ['name' => 'Gerenciar Usuários', 'slug' => 'usuarios.gerenciar', 'group' => 'Usuários'],
            ['name' => 'Excluir Usuários', 'slug' => 'usuarios.deletar', 'group' => 'Usuários'],

            // ================= Cargos =================
            ['name' => 'Acessar Cargos', 'slug' => 'cargos.acessar', 'group' => 'Cargos'],
            ['name' => 'Gerenciar Cargos e Permissões', 'slug' => 'cargos.gerenciar', 'group' => 'Cargos'],

            // ================= Configurações =================
            ['name' => 'Acessar Colunas do Dashboard', 'slug' => 'configuracoes.colunas.acessar', 'group' => 'Configurações'],
            ['name' => 'Gerenciar Colunas do Dashboard', 'slug' => 'configuracoes.colunas.gerenciar', 'group' => 'Configurações'],
            
            ['name' => 'Acessar Modelos de Proposta', 'slug' => 'configuracoes.modelos_proposta.acessar', 'group' => 'Configurações'],
            ['name' => 'Gerenciar Modelos de Proposta', 'slug' => 'configuracoes.modelos_proposta.gerenciar', 'group' => 'Configurações'],
            
            ['name' => 'Acessar Modelos de Contrato', 'slug' => 'configuracoes.modelos_contrato.acessar', 'group' => 'Configurações'],
            ['name' => 'Gerenciar Modelos de Contrato', 'slug' => 'configuracoes.modelos_contrato.gerenciar', 'group' => 'Configurações'],
            
            ['name' => 'Acessar Gestão de Produtos', 'slug' => 'configuracoes.produtos.acessar', 'group' => 'Configurações'],
            ['name' => 'Gerenciar Produtos', 'slug' => 'configuracoes.produtos.gerenciar', 'group' => 'Configurações'],
            
            ['name' => 'Acessar Gestão de Manutenção', 'slug' => 'configuracoes.manutencao.acessar', 'group' => 'Configurações'],
            ['name' => 'Gerenciar Manutenção', 'slug' => 'configuracoes.manutencao.gerenciar', 'group' => 'Configurações'],
            
            ['name' => 'Acessar Formas de Pagamento', 'slug' => 'configuracoes.formas_pagamento.acessar', 'group' => 'Configurações'],
            ['name' => 'Gerenciar Formas de Pagamento', 'slug' => 'configuracoes.formas_pagamento.gerenciar', 'group' => 'Configurações'],
            
            ['name' => 'Acessar Tipos de Qualificação', 'slug' => 'configuracoes.qualificacao.acessar', 'group' => 'Configurações'],
            ['name' => 'Gerenciar Tipos de Qualificação', 'slug' => 'configuracoes.qualificacao.gerenciar', 'group' => 'Configurações'],
            
            ['name' => 'Acessar Cortesias', 'slug' => 'configuracoes.cortesias.acessar', 'group' => 'Configurações'],
            ['name' => 'Gerenciar Cortesias', 'slug' => 'configuracoes.cortesias.gerenciar', 'group' => 'Configurações'],
            
            ['name' => 'Acessar Metas da Plataforma', 'slug' => 'configuracoes.metas.acessar', 'group' => 'Configurações'],
            ['name' => 'Gerenciar Metas da Plataforma', 'slug' => 'configuracoes.metas.gerenciar', 'group' => 'Configurações'],
            
            ['name' => 'Acessar Assuntos de Protocolo', 'slug' => 'configuracoes.assuntos_protocolo.acessar', 'group' => 'Configurações'],
            ['name' => 'Gerenciar Assuntos de Protocolo', 'slug' => 'configuracoes.assuntos_protocolo.gerenciar', 'group' => 'Configurações'],
            
            ['name' => 'Acessar Logs do Sistema', 'slug' => 'configuracoes.logs.acessar', 'group' => 'Configurações'],
        ];

        // Delete old permissions that are no longer in this list
        $currentSlugs = array_column($permissions, 'slug');
        \App\Models\Permission::whereNotIn('slug', $currentSlugs)->delete();

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
        // $users = \App\Models\User::all();
        // foreach ($users as $user) {
        //     $user->roles()->syncWithoutDetaching([$adminRole->id]);
        // }
    }
}
