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
        Schema::table('commission_rules', function (Blueprint $table) {
            $table->decimal('distribution_base_percentage', 5, 2)->default(15.00)->after('percentage');
            $table->integer('cash_installments')->default(1)->after('distribution_base_percentage');
            $table->integer('credit_installments')->default(3)->after('cash_installments');
            $table->string('boleto_installments_type')->default('dynamic')->after('credit_installments');
            $table->integer('boleto_fixed_installments')->nullable()->after('boleto_installments_type');
        });
    }

    public function down(): void
    {
        Schema::table('commission_rules', function (Blueprint $table) {
            $table->dropColumn([
                'distribution_base_percentage',
                'cash_installments',
                'credit_installments',
                'boleto_installments_type',
                'boleto_fixed_installments'
            ]);
        });
    }
};
