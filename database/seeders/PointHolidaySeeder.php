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
            ['name' => 'Carnaval', 'holiday_date' => '2000-03-07'],
            ['name' => 'Páscoa', 'holiday_date' => '2000-04-23'],
            ['name' => 'Tiradentes', 'holiday_date' => '2000-04-21'],
            ['name' => 'Dia do Trabalho', 'holiday_date' => '2000-05-01'],
            ['name' => 'Corpus Christi', 'holiday_date' => '2000-06-22'],
            ['name' => 'Independência do Brasil', 'holiday_date' => '2000-09-07'],
            ['name' => 'Dia das Crianças', 'holiday_date' => '2000-10-12'],
            ['name' => 'Finados', 'holiday_date' => '2000-11-02'],
            ['name' => 'Proclamação da República', 'holiday_date' => '2000-11-15'],
            ['name' => 'Natal', 'holiday_date' => '2000-12-25'],
            ['name' => 'Réveillon', 'holiday_date' => '2000-12-31'],
        ];

        foreach ($holidays as $holiday) {
            \App\Models\PointTable\Holiday::updateOrCreate(
                ['name' => $holiday['name']],
                ['holiday_date' => $holiday['holiday_date']]
            );
        }
    }
}
