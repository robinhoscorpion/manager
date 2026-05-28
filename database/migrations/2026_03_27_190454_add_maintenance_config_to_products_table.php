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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('maintenance_fee_value', 12, 2)->default(0)->after('contract_fee');
            $table->integer('maintenance_fee_installments')->default(1)->after('maintenance_fee_value');
            $table->string('maintenance_fee_start_rule')->default('semester_based')->after('maintenance_fee_installments');
            $table->integer('maintenance_fee_day')->default(10)->after('maintenance_fee_start_rule');
            $table->integer('maintenance_fee_delay_years')->default(0)->after('maintenance_fee_day');
            $table->boolean('is_maintenance_exempt')->default(false)->after('maintenance_fee_delay_years');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            //
        });
    }
};
