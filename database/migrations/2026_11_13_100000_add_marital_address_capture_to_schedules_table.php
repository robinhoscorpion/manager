<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->string('estado_civil')->nullable()->after('profissao');
            $table->string('cidade')->nullable()->after('estado_civil');
            $table->string('bairro')->nullable()->after('cidade');
            $table->string('local')->nullable()->after('bairro'); // Ponto de captação (capture_points.value)
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn(['estado_civil', 'cidade', 'bairro', 'local']);
        });
    }
};
