<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PointTable\Resort;
use App\Models\PointTable\Season;
use App\Models\PointTable\Accommodation;
use App\Models\PointTable\Score;

class PointTableSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Criar Temporadas
        $seasonsData = [
            ['name' => 'BAIXA TEMPORADA', 'advance_days' => 20, 'period_description' => "MARÇO, ABRIL, MAIO,\nJUNHO, AGOSTO E SETEMBRO"],
            ['name' => 'MÉDIA TEMPORADA', 'advance_days' => 30, 'period_description' => 'OUTUBRO E NOVEMBRO'],
            ['name' => 'ALTA TEMPORADA',  'advance_days' => 60, 'period_description' => "FEVEREIRO, JULHO,\nDEZEMBRO E FERIADOS"],
            ['name' => 'SUPER ALTA',      'advance_days' => 90, 'period_description' => 'JANEIRO E CARNAVAL'],
            ['name' => 'DATAS ESPECIAIS', 'advance_days' => 120, 'period_description' => 'RÉVEILLON'],
        ];

        $seasons = [];
        foreach ($seasonsData as $sData) {
            $seasons[$sData['name']] = Season::firstOrCreate(['name' => $sData['name']], $sData);
        }

        // 2. Criar Resorts e Estrutura
        $resortsData = [
            [
                'name' => 'RESENDE IMPERIAL',
                'icon' => '🌿',
                'color_theme' => 'bg-[#3d5a2e]',
                'accommodations' => [
                    ['name' => 'CLASSIC TÉRREO OU SUPERIOR SEM VISTA', 'group_name' => 'CLASSIC TÉRREO OU SUPERIOR SEM VISTA', 'max_pax' => 4, 'scores' => [
                        ['pax' => 2, 'season' => 'BAIXA TEMPORADA', 'points' => 35000],
                        ['pax' => 3, 'season' => 'BAIXA TEMPORADA', 'points' => 45500],
                        ['pax' => 4, 'season' => 'BAIXA TEMPORADA', 'points' => 56000],
                        ['pax' => 2, 'season' => 'MÉDIA TEMPORADA', 'points' => 45000],
                        ['pax' => 3, 'season' => 'MÉDIA TEMPORADA', 'points' => 58500],
                        ['pax' => 4, 'season' => 'MÉDIA TEMPORADA', 'points' => 72000],
                        ['pax' => 2, 'season' => 'ALTA TEMPORADA',  'points' => 60000],
                        ['pax' => 3, 'season' => 'ALTA TEMPORADA',  'points' => 78000],
                        ['pax' => 4, 'season' => 'ALTA TEMPORADA',  'points' => 96000],
                        ['pax' => 2, 'season' => 'SUPER ALTA',      'points' => 90000],
                        ['pax' => 3, 'season' => 'SUPER ALTA',      'points' => 117000],
                        ['pax' => 4, 'season' => 'SUPER ALTA',      'points' => 144000],
                        ['pax' => 2, 'season' => 'DATAS ESPECIAIS', 'points' => 145000],
                        ['pax' => 3, 'season' => 'DATAS ESPECIAIS', 'points' => 188500],
                        ['pax' => 4, 'season' => 'DATAS ESPECIAIS', 'points' => 232000],
                    ]],
                    ['name' => 'CLASSIC SUPERIOR COM VISTA PISCINA', 'group_name' => 'CLASSIC SUPERIOR COM VISTA PISCINA', 'max_pax' => 4, 'scores' => [
                        ['pax' => 3, 'season' => 'BAIXA TEMPORADA', 'points' => 54600],
                        ['pax' => 4, 'season' => 'BAIXA TEMPORADA', 'points' => 67200],
                        ['pax' => 3, 'season' => 'MÉDIA TEMPORADA', 'points' => 70200],
                        ['pax' => 4, 'season' => 'MÉDIA TEMPORADA', 'points' => 86400],
                        ['pax' => 3, 'season' => 'ALTA TEMPORADA',  'points' => 93600],
                        ['pax' => 4, 'season' => 'ALTA TEMPORADA',  'points' => 115200],
                        ['pax' => 3, 'season' => 'SUPER ALTA',      'points' => 140400],
                        ['pax' => 4, 'season' => 'SUPER ALTA',      'points' => 172800],
                        ['pax' => 3, 'season' => 'DATAS ESPECIAIS', 'points' => 226200],
                        ['pax' => 4, 'season' => 'DATAS ESPECIAIS', 'points' => 278200],
                    ]],
                    ['name' => 'SUÍTE IMPERIAL', 'group_name' => 'SUÍTE IMPERIAL', 'max_pax' => 2, 'scores' => [
                        ['pax' => 2, 'season' => 'BAIXA TEMPORADA', 'points' => 50400],
                        ['pax' => 2, 'season' => 'MÉDIA TEMPORADA', 'points' => 64800],
                        ['pax' => 2, 'season' => 'ALTA TEMPORADA',  'points' => 86400],
                        ['pax' => 2, 'season' => 'SUPER ALTA',      'points' => 129600],
                        ['pax' => 2, 'season' => 'DATAS ESPECIAIS', 'points' => 208800],
                    ]],
                    ['name' => 'SUÍTE REAL', 'group_name' => 'SUÍTE REAL', 'max_pax' => 2, 'scores' => [
                        ['pax' => 2, 'season' => 'BAIXA TEMPORADA', 'points' => 67200],
                        ['pax' => 2, 'season' => 'MÉDIA TEMPORADA', 'points' => 86400],
                        ['pax' => 2, 'season' => 'ALTA TEMPORADA',  'points' => 115200],
                        ['pax' => 2, 'season' => 'SUPER ALTA',      'points' => 172800],
                        ['pax' => 2, 'season' => 'DATAS ESPECIAIS', 'points' => 278400],
                    ]],
                ]
            ],
            [
                'name' => 'TERRA BOA',
                'icon' => '🌿',
                'color_theme' => 'bg-[#3d5a2e]',
                'accommodations' => [
                    ['name' => 'LUXO TÉRREO', 'group_name' => 'LUXO TÉRREO', 'max_pax' => 3, 'scores' => [
                        ['pax' => 2, 'season' => 'BAIXA TEMPORADA', 'points' => 25000],
                        ['pax' => 3, 'season' => 'BAIXA TEMPORADA', 'points' => 32500],
                        ['pax' => 2, 'season' => 'MÉDIA TEMPORADA', 'points' => 31000],
                        ['pax' => 3, 'season' => 'MÉDIA TEMPORADA', 'points' => 40300],
                        ['pax' => 2, 'season' => 'ALTA TEMPORADA',  'points' => 40000],
                        ['pax' => 3, 'season' => 'ALTA TEMPORADA',  'points' => 52000],
                        ['pax' => 2, 'season' => 'SUPER ALTA',      'points' => 65000],
                        ['pax' => 3, 'season' => 'SUPER ALTA',      'points' => 84500],
                        ['pax' => 2, 'season' => 'DATAS ESPECIAIS', 'points' => 130000],
                        ['pax' => 3, 'season' => 'DATAS ESPECIAIS', 'points' => 169000],
                    ]],
                    ['name' => 'LUXO SUPERIOR', 'group_name' => 'LUXO SUPERIOR', 'max_pax' => 4, 'scores' => [
                        ['pax' => 2, 'season' => 'BAIXA TEMPORADA', 'points' => 27500],
                        ['pax' => 4, 'season' => 'BAIXA TEMPORADA', 'points' => 44000],
                        ['pax' => 2, 'season' => 'MÉDIA TEMPORADA', 'points' => 34100],
                        ['pax' => 4, 'season' => 'MÉDIA TEMPORADA', 'points' => 54560],
                        ['pax' => 2, 'season' => 'ALTA TEMPORADA',  'points' => 44000],
                        ['pax' => 4, 'season' => 'ALTA TEMPORADA',  'points' => 70400],
                        ['pax' => 2, 'season' => 'SUPER ALTA',      'points' => 71500],
                        ['pax' => 4, 'season' => 'SUPER ALTA',      'points' => 114400],
                        ['pax' => 2, 'season' => 'DATAS ESPECIAIS', 'points' => 143000],
                        ['pax' => 4, 'season' => 'DATAS ESPECIAIS', 'points' => 228800],
                    ]],
                    ['name' => 'SUÍTE MASTER', 'group_name' => 'SUÍTE MASTER', 'max_pax' => 2, 'scores' => [
                        ['pax' => 2, 'season' => 'BAIXA TEMPORADA', 'points' => 32500],
                        ['pax' => 2, 'season' => 'MÉDIA TEMPORADA', 'points' => 40300],
                        ['pax' => 2, 'season' => 'ALTA TEMPORADA',  'points' => 52000],
                        ['pax' => 2, 'season' => 'SUPER ALTA',      'points' => 84500],
                        ['pax' => 2, 'season' => 'DATAS ESPECIAIS', 'points' => 169000],
                    ]],
                    ['name' => 'SUÍTE MASTER LUXO', 'group_name' => 'SUÍTE MASTER LUXO', 'max_pax' => 2, 'scores' => [
                        ['pax' => 2, 'season' => 'BAIXA TEMPORADA', 'points' => 36250],
                        ['pax' => 2, 'season' => 'MÉDIA TEMPORADA', 'points' => 45000],
                        ['pax' => 2, 'season' => 'ALTA TEMPORADA',  'points' => 58000],
                        ['pax' => 2, 'season' => 'SUPER ALTA',      'points' => 94250],
                        ['pax' => 2, 'season' => 'DATAS ESPECIAIS', 'points' => 188500],
                    ]],
                ]
            ],
            [
                'name' => 'VIRA CANOA',
                'icon' => '🌴',
                'color_theme' => 'bg-[#5a3d1e]',
                'accommodations' => [
                    ['name' => 'QUARTO OU BANGALÔ TÉRREO', 'group_name' => 'QUARTO OU BANGALÔ TÉRREO', 'max_pax' => 3, 'scores' => [
                        ['pax' => 2, 'season' => 'BAIXA TEMPORADA', 'points' => 25000],
                        ['pax' => 3, 'season' => 'BAIXA TEMPORADA', 'points' => 32500],
                        ['pax' => 2, 'season' => 'MÉDIA TEMPORADA', 'points' => 29000],
                        ['pax' => 3, 'season' => 'MÉDIA TEMPORADA', 'points' => 37700],
                        ['pax' => 2, 'season' => 'ALTA TEMPORADA',  'points' => 33000],
                        ['pax' => 3, 'season' => 'ALTA TEMPORADA',  'points' => 42900],
                        ['pax' => 2, 'season' => 'SUPER ALTA',      'points' => 60000],
                        ['pax' => 3, 'season' => 'SUPER ALTA',      'points' => 78000],
                        ['pax' => 2, 'season' => 'DATAS ESPECIAIS', 'points' => 110000],
                        ['pax' => 3, 'season' => 'DATAS ESPECIAIS', 'points' => 143000],
                    ]],
                    ['name' => 'QUARTO OU BANGALÔ LUXO SUPERIOR', 'group_name' => 'QUARTO OU BANGALÔ LUXO SUPERIOR', 'max_pax' => 3, 'scores' => [
                        ['pax' => 2, 'season' => 'BAIXA TEMPORADA', 'points' => 27500],
                        ['pax' => 3, 'season' => 'BAIXA TEMPORADA', 'points' => 35750],
                        ['pax' => 2, 'season' => 'MÉDIA TEMPORADA', 'points' => 31900],
                        ['pax' => 3, 'season' => 'MÉDIA TEMPORADA', 'points' => 41470],
                        ['pax' => 2, 'season' => 'ALTA TEMPORADA',  'points' => 36300],
                        ['pax' => 3, 'season' => 'ALTA TEMPORADA',  'points' => 47190],
                        ['pax' => 2, 'season' => 'SUPER ALTA',      'points' => 66000],
                        ['pax' => 3, 'season' => 'SUPER ALTA',      'points' => 85800],
                        ['pax' => 2, 'season' => 'DATAS ESPECIAIS', 'points' => 121000],
                        ['pax' => 3, 'season' => 'DATAS ESPECIAIS', 'points' => 157300],
                    ]],
                ]
            ],
            [
                'name' => 'PEDRA TORTA',
                'icon' => '🪨',
                'color_theme' => 'bg-[#2e3d5a]',
                'accommodations' => [
                    ['name' => 'LUXO TÉRREO', 'group_name' => 'LUXO TÉRREO', 'max_pax' => 3, 'scores' => [
                        ['pax' => 2, 'season' => 'BAIXA TEMPORADA', 'points' => 20000],
                        ['pax' => 3, 'season' => 'BAIXA TEMPORADA', 'points' => 26000],
                        ['pax' => 2, 'season' => 'MÉDIA TEMPORADA', 'points' => 22000],
                        ['pax' => 3, 'season' => 'MÉDIA TEMPORADA', 'points' => 28600],
                        ['pax' => 2, 'season' => 'ALTA TEMPORADA',  'points' => 27000],
                        ['pax' => 3, 'season' => 'ALTA TEMPORADA',  'points' => 35100],
                        ['pax' => 2, 'season' => 'SUPER ALTA',      'points' => 55000],
                        ['pax' => 3, 'season' => 'SUPER ALTA',      'points' => 71500],
                        ['pax' => 2, 'season' => 'DATAS ESPECIAIS', 'points' => 90000],
                        ['pax' => 3, 'season' => 'DATAS ESPECIAIS', 'points' => 117000],
                    ]],
                    ['name' => 'LUXO SUPERIOR', 'group_name' => 'LUXO SUPERIOR', 'max_pax' => 3, 'scores' => [
                        ['pax' => 2, 'season' => 'BAIXA TEMPORADA', 'points' => 26000],
                        ['pax' => 3, 'season' => 'BAIXA TEMPORADA', 'points' => 28600],
                        ['pax' => 2, 'season' => 'MÉDIA TEMPORADA', 'points' => 28600],
                        ['pax' => 3, 'season' => 'MÉDIA TEMPORADA', 'points' => 31460],
                        ['pax' => 2, 'season' => 'ALTA TEMPORADA',  'points' => 35100],
                        ['pax' => 3, 'season' => 'ALTA TEMPORADA',  'points' => 38610],
                        ['pax' => 2, 'season' => 'SUPER ALTA',      'points' => 71500],
                        ['pax' => 3, 'season' => 'SUPER ALTA',      'points' => 78650],
                        ['pax' => 2, 'season' => 'DATAS ESPECIAIS', 'points' => 117000],
                        ['pax' => 3, 'season' => 'DATAS ESPECIAIS', 'points' => 128700],
                    ]],
                    ['name' => 'SUÍTE MASTER', 'group_name' => 'SUÍTE MASTER', 'max_pax' => 4, 'scores' => [
                        ['pax' => 2, 'season' => 'BAIXA TEMPORADA', 'points' => 20000],
                        ['pax' => 3, 'season' => 'BAIXA TEMPORADA', 'points' => 35200],
                        ['pax' => 4, 'season' => 'BAIXA TEMPORADA', 'points' => 38600],
                        ['pax' => 2, 'season' => 'MÉDIA TEMPORADA', 'points' => 22000],
                        ['pax' => 3, 'season' => 'MÉDIA TEMPORADA', 'points' => 38720],
                        ['pax' => 4, 'season' => 'MÉDIA TEMPORADA', 'points' => 42000],
                        ['pax' => 2, 'season' => 'ALTA TEMPORADA',  'points' => 27000],
                        ['pax' => 3, 'season' => 'ALTA TEMPORADA',  'points' => 47520],
                        ['pax' => 4, 'season' => 'ALTA TEMPORADA',  'points' => 54000],
                        ['pax' => 2, 'season' => 'SUPER ALTA',      'points' => 55000],
                        ['pax' => 3, 'season' => 'SUPER ALTA',      'points' => 96800],
                        ['pax' => 4, 'season' => 'SUPER ALTA',      'points' => 108000],
                        ['pax' => 2, 'season' => 'DATAS ESPECIAIS', 'points' => 90000],
                        ['pax' => 3, 'season' => 'DATAS ESPECIAIS', 'points' => 158400],
                        ['pax' => 4, 'season' => 'DATAS ESPECIAIS', 'points' => 174000],
                    ]],
                ]
            ],
        ];

        foreach ($resortsData as $rData) {
            $resort = Resort::firstOrCreate(
                ['name' => $rData['name']],
                ['icon' => $rData['icon'], 'color_theme' => $rData['color_theme']]
            );

            foreach ($rData['accommodations'] as $accData) {
                $acc = Accommodation::firstOrCreate([
                    'resort_id' => $resort->id,
                    'name' => $accData['name'],
                ], [
                    'group_name' => $accData['group_name'],
                    'max_pax' => $accData['max_pax'],
                ]);

                foreach ($accData['scores'] as $scoreData) {
                    $seasonId = $seasons[$scoreData['season']]->id;
                    Score::updateOrCreate([
                        'accommodation_id' => $acc->id,
                        'season_id' => $seasonId,
                        'pax' => $scoreData['pax'],
                    ], [
                        'points' => $scoreData['points'],
                    ]);
                }
            }
        }
    }
}
