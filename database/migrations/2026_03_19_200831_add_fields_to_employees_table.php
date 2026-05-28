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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('type')->default('Efetivado')->after('email');
            $table->string('workload')->default('44 Horas')->after('salary');
            $table->boolean('status')->default(true)->after('workload');
            $table->boolean('is_active')->default(true)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['type', 'workload', 'status', 'is_active']);
        });
    }
};
