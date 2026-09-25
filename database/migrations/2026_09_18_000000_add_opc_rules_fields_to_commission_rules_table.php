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
            $table->string('rule_type')->default('percentage')->after('role_column');
            $table->json('score_rules')->nullable()->after('boleto_fixed_installments');
            $table->json('qualification_rules')->nullable()->after('score_rules');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commission_rules', function (Blueprint $table) {
            $table->dropColumn(['rule_type', 'score_rules', 'qualification_rules']);
        });
    }
};
