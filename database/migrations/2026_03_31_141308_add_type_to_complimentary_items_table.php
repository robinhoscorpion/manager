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
        Schema::table('complimentary_items', function (Blueprint $table) {
            $table->string('type')->default('atendimento')->after('name');
            $table->dropColumn('value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('complimentary_items', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->decimal('value', 12, 2)->default(0.00);
        });
    }
};
