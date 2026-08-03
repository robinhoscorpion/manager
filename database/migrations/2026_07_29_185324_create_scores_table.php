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
        Schema::create('point_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accommodation_id')->constrained('point_accommodations')->onDelete('cascade');
            $table->foreignId('season_id')->constrained('point_seasons')->onDelete('cascade');
            $table->integer('pax');
            $table->decimal('points', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_scores');
    }
};
