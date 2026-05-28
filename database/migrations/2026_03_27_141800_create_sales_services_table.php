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
        Schema::create('sales_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            
            $table->string('date');
            $table->string('time');
            $table->string('clients')->nullable(); // Nome formatado do titular + cônjuge para listagem rápida
            $table->string('local')->default('hotel');
            $table->string('opc_avatar')->nullable();
            $table->string('qualification')->default('Q');
            $table->string('status')->default('table');
            
            // Cônjuge (Contexto do Atendimento)
            $table->boolean('tem_conjuge')->default(false);
            $table->string('nome_conjuge')->nullable();
            $table->date('data_nascimento_conjuge')->nullable();
            $table->integer('idade_conjuge')->nullable();
            $table->string('profissao_conjuge')->nullable();
            
            // Família e Relação (Contexto do Atendimento)
            $table->integer('quantidade_filhos')->default(0);
            $table->string('tempo_juntos')->nullable();
            $table->string('renda_familiar')->nullable();
            
            // Logística
            $table->string('cortesia')->nullable();
            $table->text('observacoes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_services');
    }
};
