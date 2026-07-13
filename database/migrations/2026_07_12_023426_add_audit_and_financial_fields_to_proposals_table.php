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
            $table->string('audit_status')->default('pending')->after('status')->comment('pending, approved, rejected');
            $table->text('audit_reason')->nullable()->after('audit_status');
            $table->decimal('base_value', 10, 2)->nullable()->after('total_value');
            $table->decimal('taxes', 10, 2)->nullable()->after('base_value');
            $table->decimal('gross_value', 10, 2)->nullable()->after('taxes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn(['audit_status', 'audit_reason', 'base_value', 'taxes', 'gross_value']);
        });
    }
};
