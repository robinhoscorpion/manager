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
            $table->string('contract_delivery_status')->default('pending')->nullable();
            $table->string('contract_delivery_method')->nullable();
            $table->timestamp('contract_delivered_at')->nullable();
            $table->foreignId('contract_delivered_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_services', function (Blueprint $table) {
            $table->dropForeign(['contract_delivered_by']);
            $table->dropColumn([
                'contract_delivery_status',
                'contract_delivery_method',
                'contract_delivered_at',
                'contract_delivered_by',
            ]);
        });
    }
};
