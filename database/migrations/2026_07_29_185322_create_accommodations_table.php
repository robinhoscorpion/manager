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
        Schema::create('point_accommodations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resort_id')->constrained('point_resorts')->onDelete('cascade');
            $table->string('name');
            $table->string('group_name')->nullable();
            $table->integer('max_pax');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_accommodations');
    }
};
