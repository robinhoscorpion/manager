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
            $table->string('contract_signature_status')->nullable()->default('pending');
            $table->string('contract_signed_at')->nullable();
            $table->string('contract_file_path')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_services', function (Blueprint $table) {
            $table->dropColumn([
                'contract_signature_status',
                'contract_signed_at',
                'contract_file_path'
            ]);
        });
    }
};
