<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Define se a qualificação entra no cálculo de aproveitamento/conversão
 * (Dashboard, Ranking de Vendas e Desempenho Comercial).
 * Ex.: NT (No Tour) não entra, pois o casal não fez a apresentação.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qualifications', function (Blueprint $table) {
            $table->boolean('counts_in_metrics')->default(true)->after('is_active');
        });

        // NT (No Tour) não entra para o cálculo
        DB::table('qualifications')->whereRaw('UPPER(TRIM(code)) = ?', ['NT'])->update(['counts_in_metrics' => false]);
    }

    public function down(): void
    {
        Schema::table('qualifications', function (Blueprint $table) {
            $table->dropColumn('counts_in_metrics');
        });
    }
};
