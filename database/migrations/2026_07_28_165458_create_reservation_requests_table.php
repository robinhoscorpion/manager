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
        Schema::create('reservation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_service_id')->constrained('sales_services')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Atendente que abriu
            $table->string('destination');
            $table->date('check_in');
            $table->date('check_out');
            $table->integer('adults');
            $table->integer('children')->default(0);
            $table->enum('status', ['pending', 'analyzing', 'confirmed', 'canceled'])->default('pending');
            $table->text('observations')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservation_requests');
    }
};
