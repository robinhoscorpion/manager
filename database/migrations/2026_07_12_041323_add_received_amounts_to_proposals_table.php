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
        Schema::table('proposals', function (Blueprint $table) {
            $table->decimal('received_down_payment', 10, 2)->nullable()->after('audit_reason');
            $table->decimal('received_taxes', 10, 2)->nullable()->after('received_down_payment');
            $table->decimal('received_balance', 10, 2)->nullable()->after('received_taxes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn(['received_down_payment', 'received_taxes', 'received_balance']);
        });
    }
};
