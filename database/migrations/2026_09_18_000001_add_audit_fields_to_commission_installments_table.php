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
        Schema::table('commission_installments', function (Blueprint $table) {
            $table->timestamp('paid_at')->nullable()->after('status');
            $table->foreignId('audited_by')->nullable()->after('paid_at')->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable()->after('audited_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commission_installments', function (Blueprint $table) {
            $table->dropForeign(['audited_by']);
            $table->dropColumn(['paid_at', 'audited_by', 'notes']);
        });
    }
};
