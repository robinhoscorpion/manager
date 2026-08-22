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
            $table->dropUnique(['role']);
            $table->dropColumn('role');
            
            $table->string('name')->after('id'); // Ex: "Comissão do Liner"
            $table->string('role_column')->after('name'); // Ex: "liner_id"
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commission_rules', function (Blueprint $table) {
            $table->dropColumn(['name', 'role_column']);
            $table->string('role')->unique();
        });
    }
};
