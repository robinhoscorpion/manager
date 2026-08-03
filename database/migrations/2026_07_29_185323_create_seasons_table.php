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
        Schema::create('point_seasons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('advance_days');
            $table->text('period_description')->nullable();
            $table->json('months_active')->nullable();
            $table->json('special_dates')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_seasons');
    }
};
