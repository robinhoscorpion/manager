<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, update existing values to preserve the 5-digit padding they implicitly had via str_pad in PHP
        DB::statement("UPDATE products SET current_sequence = LPAD(current_sequence, 5, '0')");

        Schema::table('products', function (Blueprint $table) {
            $table->string('current_sequence')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('current_sequence')->change();
        });
    }
};
