<?php

namespace Database\Seeders;

use App\Models\Qualification;
use Illuminate\Database\Seeder;

class QualificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $qualifications = [
            ['name' => 'Qualificado', 'code' => 'Q', 'color' => 'bg-blue-500', 'description' => 'Perfil padrão qualificado'],
            ['name' => 'Bronze 1.5k', 'code' => '1.500', 'color' => 'bg-emerald-500', 'description' => 'Renda mínima 1.500'],
            ['name' => 'Prata 2.8k', 'code' => '2.800', 'color' => 'bg-cyan-500', 'description' => 'Renda mínima 2.800'],
            ['name' => 'Ouro 3.2k', 'code' => '3.200', 'color' => 'bg-purple-500', 'description' => 'Renda mínima 3.200'],
            ['name' => 'Platina 4.5k', 'code' => '4.500', 'color' => 'bg-pink-500', 'description' => 'Renda mínima 4.500'],
            ['name' => 'Diamante 5k', 'code' => '5.000', 'color' => 'bg-orange-500', 'description' => 'Renda mínima 5.000'],
            ['name' => 'Elite 7.2k', 'code' => '7.200', 'color' => 'bg-amber-500', 'description' => 'Renda mínima 7.200'],
            ['name' => 'Imperial 10k', 'code' => '10.000', 'color' => 'bg-rose-500', 'description' => 'Renda mínima 10.000'],
        ];

        foreach ($qualifications as $qual) {
            Qualification::updateOrCreate(['code' => $qual['code']], $qual);
        }
    }
}
