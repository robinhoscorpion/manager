<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('capture_points', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Valor gravado em sales_services.local (imutável após criado)
            $table->string('value')->unique();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // Pontos de captação que antes eram fixos no sistema
        $now = now();
        DB::table('capture_points')->insert([
            ['name' => 'Hotel', 'value' => 'hotel', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Sindicato', 'value' => 'sindicato', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Externo', 'value' => 'externo', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Aeroporto', 'value' => 'aeroporto', 'active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('capture_points');
    }
};
