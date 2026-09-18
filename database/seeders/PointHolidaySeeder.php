<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PointHolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $holidays = [
            [
                'name'           => 'Ano Novo 2025/2026',
                'holiday_date'   => '2026-01-01',
                'start_date'     => '2025-12-28',
                'end_date'       => '2026-01-04',
                'classification' => 'Data Especial',
            ],
            [
                'name'           => 'Carnaval',
                'holiday_date'   => '2026-02-17',
                'start_date'     => '2026-02-13',
                'end_date'       => '2026-02-18',
                'classification' => 'Super Alta',
            ],
            [
                'name'           => 'Semana Santa / Páscoa',
                'holiday_date'   => '2026-04-03',
                'start_date'     => '2026-04-02',
                'end_date'       => '2026-04-05',
                'classification' => 'Alta Temporada',
            ],
            [
                'name'           => 'Tiradentes',
                'holiday_date'   => '2026-04-21',
                'start_date'     => '2026-04-17',
                'end_date'       => '2026-04-21',
                'classification' => 'Alta Temporada',
            ],
            [
                'name'           => 'Dia do Trabalhador',
                'holiday_date'   => '2026-05-01',
                'start_date'     => '2026-04-30',
                'end_date'       => '2026-05-03',
                'classification' => 'Alta Temporada',
            ],
            [
                'name'           => 'Corpus Christi',
                'holiday_date'   => '2026-06-04',
                'start_date'     => '2026-06-03',
                'end_date'       => '2026-06-07',
                'classification' => 'Alta Temporada',
            ],
            [
                'name'           => 'São João',
                'holiday_date'   => '2026-06-24',
                'start_date'     => '2026-06-19',
                'end_date'       => '2026-06-24',
                'classification' => 'Alta Temporada',
            ],
            [
                'name'           => 'Independência da Bahia',
                'holiday_date'   => '2026-07-02',
                'start_date'     => '2026-07-01',
                'end_date'       => '2026-07-05',
                'classification' => 'Alta Temporada',
            ],
            [
                'name'           => 'Independência do Brasil',
                'holiday_date'   => '2026-09-07',
                'start_date'     => '2026-09-04',
                'end_date'       => '2026-09-07',
                'classification' => 'Alta Temporada',
            ],
            [
                'name'           => 'N. Sra. Aparecida / Crianças',
                'holiday_date'   => '2026-10-12',
                'start_date'     => '2026-10-09',
                'end_date'       => '2026-10-12',
                'classification' => 'Alta Temporada',
            ],
            [
                'name'           => 'Finados',
                'holiday_date'   => '2026-11-02',
                'start_date'     => '2026-10-30',
                'end_date'       => '2026-11-02',
                'classification' => 'Alta Temporada',
            ],
            [
                'name'           => 'Proclamação da República',
                'holiday_date'   => '2026-11-15',
                'start_date'     => '2026-11-13',
                'end_date'       => '2026-11-15',
                'classification' => 'Alta Temporada',
            ],
            [
                'name'           => 'Consciência Negra',
                'holiday_date'   => '2026-11-20',
                'start_date'     => '2026-11-19',
                'end_date'       => '2026-11-22',
                'classification' => 'Alta Temporada',
            ],
            [
                'name'           => 'Natal',
                'holiday_date'   => '2026-12-25',
                'start_date'     => '2026-12-23',
                'end_date'       => '2026-12-27',
                'classification' => 'Alta Temporada',
            ],
            [
                'name'           => 'Ano Novo 2026/2027',
                'holiday_date'   => '2027-01-01',
                'start_date'     => '2026-12-27',
                'end_date'       => '2027-01-03',
                'classification' => 'Data Especial',
            ],
        ];

        foreach ($holidays as $holiday) {
            \App\Models\PointTable\Holiday::updateOrCreate(
                ['name' => $holiday['name'], 'holiday_date' => $holiday['holiday_date']],
                [
                    'start_date'     => $holiday['start_date'],
                    'end_date'       => $holiday['end_date'],
                    'classification' => $holiday['classification'],
                ]
            );
        }
    }
}

