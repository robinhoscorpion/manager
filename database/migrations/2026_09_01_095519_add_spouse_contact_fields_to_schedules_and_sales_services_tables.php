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
        Schema::table('schedules', function (Blueprint $table) {
            $table->string('spouse_phone')->nullable()->after('spouse_name');
            $table->string('spouse_email')->nullable()->after('spouse_phone');
        });

        Schema::table('sales_services', function (Blueprint $table) {
            $table->string('celular_conjuge')->nullable()->after('nome_conjuge');
            $table->string('email_conjuge')->nullable()->after('celular_conjuge');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn(['spouse_phone', 'spouse_email']);
        });

        Schema::table('sales_services', function (Blueprint $table) {
            $table->dropColumn(['celular_conjuge', 'email_conjuge']);
        });
    }
};
