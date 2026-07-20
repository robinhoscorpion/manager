<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

echo "1. Rodando migrations (criando a tabela platform_goals se nao existir)...\n";
try {
    Artisan::call('migrate', ['--force' => true]);
    echo Artisan::output();
} catch (\Exception $e) {
    echo "Erro ao rodar migration: " . $e->getMessage() . "\n";
}

echo "\n2. Inserindo novas permissoes...\n";
$permissions = [
    ['name' => 'Acessar Metas da Plataforma', 'slug' => 'configuracoes.metas.acessar', 'group' => 'Configurações'],
    ['name' => 'Gerenciar Metas da Plataforma', 'slug' => 'configuracoes.metas.gerenciar', 'group' => 'Configurações'],
];

$adminRole = DB::table('roles')->where('slug', 'admin')->first();

foreach ($permissions as $p) {
    $existing = DB::table('permissions')->where('slug', $p['slug'])->first();
    
    if (!$existing) {
        $id = DB::table('permissions')->insertGetId([
            'name' => $p['name'],
            'slug' => $p['slug'],
            'group' => $p['group'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "Permissao criada: {$p['name']}\n";
        
        // Vincular ao admin
        if ($adminRole) {
            DB::table('permission_role')->insert([
                'permission_id' => $id,
                'role_id' => $adminRole->id
            ]);
            echo "-> Vinculada ao perfil admin.\n";
        }
    } else {
        echo "Permissao ja existe: {$p['name']}\n";
        
        // Garantir que o admin tem o vinculo
        if ($adminRole) {
            $hasLink = DB::table('permission_role')
                ->where('permission_id', $existing->id)
                ->where('role_id', $adminRole->id)
                ->exists();
                
            if (!$hasLink) {
                DB::table('permission_role')->insert([
                    'permission_id' => $existing->id,
                    'role_id' => $adminRole->id
                ]);
                echo "-> Vinculada ao perfil admin agora.\n";
            }
        }
    }
}

echo "\n3. Limpando cache para aplicar permissoes...\n";
Artisan::call('cache:clear');
echo "Concluido com sucesso! Pode acessar as Metas.\n";
