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
        Schema::create('cancellations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Usuário que executou o cancelamento
            $table->string('reason'); // Financeiro, Saúde, Arrependimento, Insatisfação, Outros
            $table->text('details')->nullable(); // Observações/relatório do distrato
            
            // Valores financeiros no momento do distrato
            $table->decimal('total_paid', 10, 2)->default(0); // Valor total já pago pelo cliente
            $table->decimal('fine_amount', 10, 2)->default(0); // Multa retida
            $table->decimal('refund_amount', 10, 2)->default(0); // Estorno
            
            $table->string('status')->default('completed'); // 'pending', 'completed'
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cancellations');
    }
};
