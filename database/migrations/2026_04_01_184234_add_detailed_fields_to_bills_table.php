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
        Schema::table('bills', function (Blueprint $table) {
            $table->date('paid_at')->nullable()->after('due_date');
            $table->decimal('interest_amount', 15, 2)->default(0)->after('amount');
            $table->decimal('paid_amount', 15, 2)->nullable()->after('interest_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropColumn(['paid_at', 'interest_amount', 'paid_amount']);
        });
    }
};
