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
            $table->string('welcome_status')->default('pending')->after('status');
            $table->timestamp('welcome_sent_at')->nullable()->after('welcome_status');
            $table->foreignId('welcome_sent_by')->nullable()->constrained('users')->nullOnDelete()->after('welcome_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_services', function (Blueprint $table) {
            $table->dropForeign(['welcome_sent_by']);
            $table->dropColumn(['welcome_status', 'welcome_sent_at', 'welcome_sent_by']);
        });
    }
};
