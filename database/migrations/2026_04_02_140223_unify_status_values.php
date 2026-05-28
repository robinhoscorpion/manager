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
        // 1. Atualizar SalesService
        DB::table('sales_services')->where('status', 'MESA')->update(['status' => 'table']);
        DB::table('sales_services')->where('status', 'PENDENTE')->update(['status' => 'pending']);
        DB::table('sales_services')->where('status', 'PROPOSTA')->update(['status' => 'proposal']);
        DB::table('sales_services')->where('status', 'APROVADO')->update(['status' => 'approved']);
        DB::table('sales_services')->where('status', 'REPROVADO')->update(['status' => 'rejected']);
        DB::table('sales_services')->where('status', 'CANCELADO')->update(['status' => 'cancelled']);
        DB::table('sales_services')->where('status', 'FINALIZADO')->update(['status' => 'completed']);

        // 2. Atualizar Proposal (caso existam variantes)
        DB::table('proposals')->where('status', 'pending')->update(['status' => 'pending']); // redundante mas seguro
        DB::table('proposals')->where('status', 'approved')->update(['status' => 'approved']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback (opcional, mas bom pra consistência caso precise voltar)
        DB::table('sales_services')->where('status', 'table')->update(['status' => 'MESA']);
        DB::table('sales_services')->where('status', 'pending')->update(['status' => 'PENDENTE']);
        DB::table('sales_services')->where('status', 'proposal')->update(['status' => 'PROPOSTA']);
        DB::table('sales_services')->where('status', 'approved')->update(['status' => 'APROVADO']);
        DB::table('sales_services')->where('status', 'rejected')->update(['status' => 'REPROVADO']);
        DB::table('sales_services')->where('status', 'cancelled')->update(['status' => 'CANCELADO']);
        DB::table('sales_services')->where('status', 'completed')->update(['status' => 'FINALIZADO']);
    }
};
