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
        if (!Schema::hasColumn('sales_services', 'viagens_por_ano')) {
            Schema::table('sales_services', function (Blueprint $table) {
                $table->unsignedSmallInteger('viagens_por_ano')->nullable()->after('renda_familiar');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('sales_services', 'viagens_por_ano')) {
            Schema::table('sales_services', function (Blueprint $table) {
                $table->dropColumn('viagens_por_ano');
            });
        }
    }
};
