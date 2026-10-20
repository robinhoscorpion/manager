<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales_services', function (Blueprint $table) {
            $table->foreignId('schedule_id')->nullable()->after('client_id')->constrained('schedules')->nullOnDelete();
        });

        // Vincula atendimentos já gerados a partir de agendamentos (local 'SALA', mesmo OPC, data e nome do cliente)
        $schedules = DB::table('schedules')->where('status', 'show')->get();
        foreach ($schedules as $schedule) {
            DB::table('sales_services')
                ->join('clients', 'clients.id', '=', 'sales_services.client_id')
                ->whereNull('sales_services.schedule_id')
                ->where('sales_services.local', 'SALA')
                ->where('sales_services.opc_id', $schedule->user_id)
                ->whereDate('sales_services.date', $schedule->date)
                ->where('clients.nome', $schedule->name)
                ->update(['sales_services.schedule_id' => $schedule->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_services', function (Blueprint $table) {
            $table->dropConstrainedForeignId('schedule_id');
        });
    }
};
