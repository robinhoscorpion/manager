<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales_services', function (Blueprint $table) {
            $table->string('cpf_conjuge')->nullable()->after('nome_conjuge');
            $table->string('rg_conjuge')->nullable()->after('cpf_conjuge');
            $table->string('nacionalidade_conjuge')->nullable()->after('profissao_conjuge');
            $table->string('estado_civil_conjuge')->nullable()->after('nacionalidade_conjuge');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_services', function (Blueprint $table) {
            $table->dropColumn(['cpf_conjuge', 'rg_conjuge', 'nacionalidade_conjuge', 'estado_civil_conjuge']);
        });
    }
};
