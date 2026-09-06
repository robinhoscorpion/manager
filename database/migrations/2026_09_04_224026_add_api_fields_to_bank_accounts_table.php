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
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->string('gateway')->nullable()->after('owner_type'); // e.g. asaas, pagarme, mercadopago
            $table->text('api_token')->nullable()->after('gateway');
            $table->string('webhook_secret')->nullable()->after('api_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropColumn(['gateway', 'api_token', 'webhook_secret']);
        });
    }
};
