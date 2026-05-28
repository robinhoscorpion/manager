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
            $table->string('avatar')->nullable()->after('local');
            $table->string('liner_avatar')->nullable()->after('opc_avatar');
            $table->string('closer_avatar')->nullable()->after('liner_avatar');
            $table->boolean('opc')->default(false)->after('closer_avatar');
            $table->boolean('closer')->default(false)->after('opc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_services', function (Blueprint $table) {
            $table->dropColumn(['avatar', 'liner_avatar', 'closer_avatar', 'opc', 'closer']);
        });
    }
};
