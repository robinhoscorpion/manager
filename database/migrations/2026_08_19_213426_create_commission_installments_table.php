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
        Schema::create('commission_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_id')->constrained()->cascadeOnDelete();
            $table->integer('month_offset');
            $table->date('due_date');
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending'); // pending, paid, cancelled
            $table->string('origin_type'); // avista, cartao, boleto
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commission_installments');
    }
};
