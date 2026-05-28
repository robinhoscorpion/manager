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
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('quantity')->nullable()->change();
        });
        Schema::table('proposals', function (Blueprint $table) {
            $table->unsignedBigInteger('quantity')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('quantity', 12, 2)->nullable()->change();
        });
        Schema::table('proposals', function (Blueprint $table) {
            $table->decimal('quantity', 12, 2)->nullable()->change();
        });
    }
};
