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
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('proposal_id')->constrained()->onDelete('cascade');
            $table->foreignId('sales_service_id')->constrained()->onDelete('cascade');
            $table->string('category');
            $table->string('description');
            $table->decimal('amount', 15, 2);
            $table->date('due_date');
            $table->string('status')->default('pending'); // pending, paid, cancelled
            $table->string('payment_method');
            $table->integer('installment_number');
            $table->integer('total_installments');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
