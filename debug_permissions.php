<?php
/**
 * debug_permissions.php
 * Script completo de diagnóstico do sistema de permissões.
 * Uso: php debug_permissions.php [email_do_usuario]
 */
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

// ─────────────────────────────────────────
// Helpers de output
// ─────────────────────────────────────────
function sep(string $title = ''): void {
    echo "\n" . str_repeat('─', 70) . "\n";
    if ($title) echo "  $title\n" . str_repeat('─', 70) . "\n";
}

function ok(string $msg): void  { echo "  ✅ $msg\n"; }
function warn(string $msg): void { echo "  ⚠️  $msg\n"; }
function err(string $msg): void  { echo "  ❌ $msg\n"; }
function info(string $msg): void { echo "  ℹ️  $msg\n"; }

// ─────────────────────────────────────────
// Todas as permissões usadas nas rotas
// ─────────────────────────────────────────
$ROUTE_PERMISSIONS = [
    'dashboard.acessar',
    'funcionarios.gerenciar',
    'funcionarios.acessar',
    'funcionarios.deletar',
    'usuarios.gerenciar',
    'usuarios.acessar',
    'usuarios.deletar',
    'configuracoes.logs.acessar',
    'cargos.gerenciar',
    'cargos.acessar',
    'agendamentos.acessar',
    'agendamentos.gerenciar',
    'atendimentos.acessar',
    'atendimentos.criar',
    'atendimentos.editar',
    'atendimentos.deletar',
    'configuracoes.colunas.acessar',
    'configuracoes.colunas.gerenciar',
    'configuracoes.modelos_contrato.acessar',
    'configuracoes.modelos_contrato.gerenciar',
    'configuracoes.metas.acessar',
    'configuracoes.metas.gerenciar',
    'configuracoes.produtos.acessar',
    'configuracoes.produtos.gerenciar',
    'configuracoes.manutencao.acessar',
    'configuracoes.manutencao.gerenciar',
    'configuracoes.formas_pagamento.acessar',
    'configuracoes.formas_pagamento.gerenciar',
    'configuracoes.qualificacao.acessar',
    'configuracoes.qualificacao.gerenciar',
    'configuracoes.cortesias.acessar',
    'configuracoes.cortesias.gerenciar',
    'configuracoes.modelos_proposta.acessar',
    'configuracoes.modelos_proposta.gerenciar',
    'configuracoes.assuntos_protocolo.acessar',
    'configuracoes.assuntos_protocolo.gerenciar',
    'recebiveis.acessar',
    'recebiveis.gerenciar',
    'controle_vendas.acessar',
    'controle_vendas.gerenciar',
    'pos_venda.boas_vindas.acessar',
    'pos_venda.boas_vindas.gerenciar',
    'pos_venda.gestao_contratos.acessar',
    'pos_venda.gestao_contratos.gerenciar',
    'pos_venda.aniversariantes.acessar',
    'pos_venda.protocolos.acessar',
    'pos_venda.protocolos.criar',
    'pos_venda.protocolos.excluir',
    'pos_venda.reservas.acessar',
    'pos_venda.reservas.gerenciar',
];

$filterEmail = $argv[1] ?? null;

// ─────────────────────────────────────────
// 1. VERIFICAÇÃO DO BANCO DE DADOS
// ─────────────────────────────────────────
sep('1. VERIFICAÇÃO DAS TABELAS DO BANCO');

// Verifica tabelas
$tables = ['roles', 'permissions', 'role_permission', 'user_role'];
foreach ($tables as $table) {
    try {
        $count = DB::table($table)->count();
        ok("Tabela '$table' existe e tem $count registros");
    } catch (\Exception $e) {
        err("Tabela '$table' NÃO encontrada: " . $e->getMessage());
    }
}

// ─────────────────────────────────────────
// 2. PERMISSÕES NO BANCO vs ROTAS
// ─────────────────────────────────────────
sep('2. PERMISSÕES CADASTRADAS vs USADAS NAS ROTAS');

$dbPerms = Permission::pluck('slug')->toArray();
$missing = array_diff($ROUTE_PERMISSIONS, $dbPerms);
$unused  = array_diff($dbPerms, $ROUTE_PERMISSIONS);

if (empty($missing)) {
    ok("Todas as " . count($ROUTE_PERMISSIONS) . " permissões das rotas estão cadastradas no banco.");
} else {
    err("Permissões usadas nas ROTAS mas NÃO cadastradas no banco:");
    foreach ($missing as $p) {
        echo "     - $p\n";
    }
}

if (!empty($unused)) {
    warn("Permissões cadastradas no banco mas NÃO usadas em rotas:");
    foreach ($unused as $p) {
        echo "     - $p\n";
    }
}

// ─────────────────────────────────────────
// 3. VERIFICAÇÃO POR CARGO
// ─────────────────────────────────────────
sep('3. PERMISSÕES POR CARGO');

$roles = Role::with('permissions')->get();
if ($roles->isEmpty()) {
    err("Nenhum cargo encontrado no banco!");
} else {
    foreach ($roles as $role) {
        echo "\n  🔑 Cargo: [{$role->slug}] {$role->name}\n";
        $rolePerms = $role->permissions->pluck('slug')->toArray();
        if (empty($rolePerms)) {
            warn("  Nenhuma permissão atribuída a este cargo.");
        } else {
            echo "     Permissões (" . count($rolePerms) . "): " . implode(', ', $rolePerms) . "\n";
        }
    }
}

// ─────────────────────────────────────────
// 4. VERIFICAÇÃO POR USUÁRIO
// ─────────────────────────────────────────
sep('4. VERIFICAÇÃO DE USUÁRIOS');

$usersQuery = User::with('roles.permissions');
if ($filterEmail) {
    $usersQuery->where('email', $filterEmail);
    echo "  (Filtrando por email: $filterEmail)\n";
}
$users = $usersQuery->get();

if ($users->isEmpty()) {
    err("Nenhum usuário encontrado" . ($filterEmail ? " com o email '$filterEmail'" : "") . ".");
} else {
    foreach ($users as $user) {
        echo "\n";
        sep("👤 Usuário: {$user->name} ({$user->email}) [ID: {$user->id}]");

        // Cargos
        $userRoles = $user->roles;
        if ($userRoles->isEmpty()) {
            err("Sem cargos atribuídos!");
        } else {
            $roleNames = $userRoles->map(fn($r) => "[{$r->slug}] {$r->name}")->implode(', ');
            info("Cargos (" . $userRoles->count() . "): $roleNames");

            if ($userRoles->count() > 1) {
                warn("Usuário tem MÚLTIPLOS CARGOS - verificando acúmulo de permissões...");
            }
        }

        // Verifica se é admin
        $isAdmin = $user->hasRole('admin');
        if ($isAdmin) {
            ok("Cargo 'admin' detectado → middleware libera TODAS as rotas automaticamente.");
        }

        // Permissões efetivas via hasPermission()
        echo "\n  ── Teste de hasPermission() para cada permissão das rotas ──\n";
        $permissionsGranted = [];
        $permissionsDenied  = [];

        foreach ($ROUTE_PERMISSIONS as $perm) {
            $result = $user->hasPermission($perm);
            if ($result) {
                $permissionsGranted[] = $perm;
            } else {
                $permissionsDenied[] = $perm;
            }
        }

        // Permissões via roles (para comparação)
        $effectiveViaRoles = $user->roles
            ->flatMap(fn($r) => $r->permissions->pluck('slug'))
            ->unique()
            ->values()
            ->toArray();

        // Cross-check: permissão está nos roles mas hasPermission() retorna false?
        echo "\n  ── Cross-check: permissões nos cargos vs hasPermission() ──\n";
        $inconsistencies = 0;
        foreach ($effectiveViaRoles as $perm) {
            $hasIt = $user->hasPermission($perm);
            if (!$hasIt) {
                err("INCONSISTÊNCIA: '$perm' está no cargo mas hasPermission() retorna FALSE!");
                $inconsistencies++;
            }
        }

        // Permissão passa hasPermission mas não está em nenhum role
        foreach ($permissionsGranted as $perm) {
            if (!in_array($perm, $effectiveViaRoles)) {
                warn("Anomalia: hasPermission('$perm') = true mas não encontrado via roles->permissions");
            }
        }

        if ($inconsistencies === 0 && !empty($effectiveViaRoles)) {
            ok("Nenhuma inconsistência encontrada! hasPermission() está coerente com os cargos.");
        }

        // Resumo
        echo "\n  ── Resumo de Acesso ──\n";
        if ($isAdmin) {
            ok("ADMIN: acesso total via middleware (bypass de permissões).");
        } else {
            ok("Permissões liberadas (" . count($permissionsGranted) . "): " . (empty($permissionsGranted) ? 'nenhuma' : implode(', ', $permissionsGranted)));
            if (!empty($permissionsDenied)) {
                info("Permissões negadas (" . count($permissionsDenied) . "): " . implode(', ', $permissionsDenied));
            }
        }

        // Verifica múltiplos cargos
        if ($userRoles->count() > 1) {
            echo "\n  ── Verificação de Múltiplos Cargos ──\n";
            foreach ($userRoles as $role) {
                $roleSpecificPerms = $role->permissions->pluck('slug')->toArray();
                info("Cargo [{$role->slug}] contribui com: " . (empty($roleSpecificPerms) ? 'nenhuma permissão' : implode(', ', $roleSpecificPerms)));
            }
            $total = count($effectiveViaRoles);
            info("Total único após acúmulo: $total permissões");
        }
    }
}

// ─────────────────────────────────────────
// 5. ANÁLISE DO INERTIA SHARE
// ─────────────────────────────────────────
sep('5. VERIFICAÇÃO: INERTIA SHARE vs hasPermission()');
echo "  O HandleInertiaRequests compartilha permissões assim:\n";
echo "  \$user->roles()->with('permissions')->get()\n";
echo "        ->pluck('permissions')->flatten()\n";
echo "        ->pluck('slug')->unique()->values()->all()\n\n";

if (!$filterEmail) {
    info("Use 'php debug_permissions.php email@usuario.com' para checar um usuário específico.");
}

$usersCheck = $filterEmail ? [$users->first()] : $users->all();
foreach ($usersCheck as $user) {
    if (!$user) continue;

    // Simulação do que o Inertia compartilha
    $inertiaPerms = $user->roles()->with('permissions')->get()
        ->pluck('permissions')->flatten()
        ->pluck('slug')->unique()->values()->toArray();

    // O que hasPermission() retorna
    $hasPermResults = [];
    foreach ($ROUTE_PERMISSIONS as $perm) {
        if ($user->hasPermission($perm)) {
            $hasPermResults[] = $perm;
        }
    }

    // Comparação
    $onlyInInertia  = array_diff($inertiaPerms, $hasPermResults);
    $onlyInHasPerm  = array_diff($hasPermResults, $inertiaPerms);
    $match = empty($onlyInInertia) && empty($onlyInHasPerm);

    echo "  👤 {$user->name} ({$user->email}):\n";
    if ($match) {
        ok("Inertia share e hasPermission() estão SINCRONIZADOS.");
    } else {
        if (!empty($onlyInInertia)) {
            warn("No Inertia (frontend) mas NÃO em hasPermission() (backend): " . implode(', ', $onlyInInertia));
        }
        if (!empty($onlyInHasPerm)) {
            warn("Em hasPermission() mas NÃO no Inertia (frontend): " . implode(', ', $onlyInHasPerm));
        }
    }
}

sep('FIM DO DIAGNÓSTICO');
echo "  Use: php debug_permissions.php [email] para analisar um usuário específico.\n\n";
