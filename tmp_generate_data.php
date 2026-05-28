<?php

use App\Models\Client;
use App\Models\Address;
use App\Models\SalesService;
use App\Models\Qualification;
use App\Models\ComplimentaryItem;
use Illuminate\Support\Str;

// Nomes e sobrenomes para gerar dados brasileiros plausíveis
$nomes = ['João', 'Maria', 'Pedro', 'Ana', 'Lucas', 'Julia', 'Carlos', 'Beatriz', 'Marcos', 'Fernanda', 'Rafael', 'Camila', 'Bruno', 'Larissa'];
$sobrenomes = ['Silva', 'Santos', 'Oliveira', 'Souza', 'Rodrigues', 'Ferreira', 'Alves', 'Pereira', 'Lima', 'Gomes', 'Costa', 'Ribeiro'];
$profissoes = ['Engenheiro', 'Médico', 'Advogado', 'Professor', 'Empresário', 'Vendedor', 'Autônomo', 'Arquiteto', 'Analista', 'Gerente'];
$nacionalidades = ['Brasileira', 'Brasileira', 'Brasileira', 'Americana', 'Portuguesa', 'Argentina', 'Espanhola'];
$locais = ['hotel', 'sindicato', 'externo', 'aeroporto'];
$avatars = [
    '/images/avatars/avatar_seller_1_1773746765420.png',
    '/images/avatars/avatar_seller_2_1773746782851.png',
    '/images/avatars/avatar_seller_3_1773746801396.png',
    '/images/avatars/ranking_avatar_illu_1_1773747414150.png',
    '/images/avatars/ranking_avatar_illu_2_1773747429050.png',
    '/images/avatars/ranking_avatar_illu_3_1773747444706.png'
];

$states = ['SC', 'PR', 'SP', 'RS', 'RJ', 'MG'];
$cities = ['Florianópolis', 'Curitiba', 'São Paulo', 'Porto Alegre', 'Rio de Janeiro', 'Belo Horizonte'];

echo "Iniciando geração de 10 atendimentos...\n";

for ($i = 0; $i < 10; $i++) {
    $nomeComp = $nomes[array_rand($nomes)] . ' ' . $sobrenomes[array_rand($sobrenomes)] . ' ' . $sobrenomes[array_rand($sobrenomes)];
    $email = strtolower(Str::slug($nomeComp, '.')) . '@example.com';
    $isEstrangeiro = rand(1, 10) > 8;
    $nacionalidade = $isEstrangeiro ? $nacionalidades[array_rand($nacionalidades)] : 'Brasileira';
    
    // 1. Criar Cliente
    $client = Client::create([
        'nome' => strtoupper($nomeComp),
        'cpf' => $isEstrangeiro ? null : sprintf('%03d.%03d.%03d-%02d', rand(100, 999), rand(100, 999), rand(100, 999), rand(10, 99)),
        'rg' => sprintf('%d.%d.%d-%d', rand(1, 9), rand(100, 999), rand(100, 999), rand(0, 9)),
        'nacionalidade' => $nacionalidade,
        'data_nascimento' => date('Y-m-d', strtotime('-' . rand(20, 60) . ' years')),
        'profissao' => $profissoes[array_rand($profissoes)],
        'estado_civil' => ['solteiro', 'casado', 'divorciado', 'viuvo'][array_rand(['solteiro', 'casado', 'divorciado', 'viuvo'])],
        'celular1' => sprintf('(48) 9%04d-%04d', rand(8000, 9999), rand(1000, 9999)),
        'email' => $email,
    ]);

    // 2. Criar Endereço
    Address::create([
        'client_id' => $client->id,
        'cep' => sprintf('%05d-%03d', rand(88000, 88999), rand(100, 999)),
        'rua' => 'Rua Aleatória ' . rand(1, 100),
        'numero' => (string)rand(10, 2000),
        'bairro' => 'Centro',
        'cidade' => $cities[array_rand($cities)],
        'estado' => $states[array_rand($states)],
    ]);

    // 3. Criar Atendimento
    SalesService::create([
        'client_id' => $client->id,
        'date' => date('d/m/Y'),
        'time' => sprintf('%02dh%02d', rand(8, 20), [0, 15, 30, 45][array_rand([0, 15, 30, 45])]),
        'local' => $locais[array_rand($locais)],
        'opc_avatar' => $avatars[array_rand($avatars)],
        'qualification' => 'Q',
        'status' => 'MESA',
        'nome_conjuge' => rand(0, 1) ? 'Cônjuge de ' . $nomeComp : null,
        'quantidade_filhos' => rand(0, 3),
        'tempo_juntos' => rand(1, 20) . ' anos',
        'renda_familiar' => ['3k', '3-5k', '5-8k', '8-12k'][array_rand(['3k', '3-5k', '5-8k', '8-12k'])],
        'observacoes' => 'Atendimento gerado automaticamente para teste de interface.',
    ]);

    echo "Atendimento $i gerado: $nomeComp\n";
}

echo "\nSucesso! 10 atendimentos gerados.\n";
