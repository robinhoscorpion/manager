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
            $table->dropColumn(['opc_avatar', 'liner_avatar', 'closer_avatar', 'avatar']);
            
            $table->foreignId('opc_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('liner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('closer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('mkt_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_services', function (Blueprint $table) {
            $table->dropForeign(['opc_id']);
            $table->dropForeign(['liner_id']);
            $table->dropForeign(['closer_id']);
            $table->dropForeign(['mkt_id']);
            
            $table->dropColumn(['opc_id', 'liner_id', 'closer_id', 'mkt_id']);
            
            $table->string('opc_avatar')->nullable();
            $table->string('liner_avatar')->nullable();
            $table->string('closer_avatar')->nullable();
            $table->string('avatar')->nullable();
        });
    }
};
