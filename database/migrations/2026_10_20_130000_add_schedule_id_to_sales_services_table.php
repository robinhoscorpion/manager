<?php

use Carbon\Carbon;
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
        // Idempotente: no MySQL o DDL não é revertido se a migration falhar no meio
        if (!Schema::hasColumn('sales_services', 'schedule_id')) {
            Schema::table('sales_services', function (Blueprint $table) {
                $table->unsignedBigInteger('schedule_id')->nullable()->after('client_id');
            });
        }

        if (!$this->hasForeignKey('sales_services', 'sales_services_schedule_id_foreign')) {
            Schema::table('sales_services', function (Blueprint $table) {
                $table->foreign('schedule_id')->references('id')->on('schedules')->nullOnDelete();
            });
        }

        // Vincula atendimentos já gerados a partir de agendamentos (local 'SALA', mesmo OPC, data e nome do cliente).
        // A coluna sales_services.date é texto com formatos mistos (Y-m-d, Y-m-d H:i:s, d/m/Y),
        // então a data é comparada em PHP e não no SQL.
        $schedules = DB::table('schedules')->where('status', 'show')->get();
        foreach ($schedules as $schedule) {
            $scheduleDate = $this->normalizeDate($schedule->date);
            if (!$scheduleDate) {
                continue;
            }

            $ids = DB::table('sales_services')
                ->join('clients', 'clients.id', '=', 'sales_services.client_id')
                ->whereNull('sales_services.schedule_id')
                ->where('sales_services.local', 'SALA')
                ->where('sales_services.opc_id', $schedule->user_id)
                ->where('clients.nome', $schedule->name)
                ->get(['sales_services.id', 'sales_services.date'])
                ->filter(fn($service) => $this->normalizeDate($service->date) === $scheduleDate)
                ->pluck('id');

            if ($ids->isNotEmpty()) {
                DB::table('sales_services')->whereIn('id', $ids)->update(['schedule_id' => $schedule->id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if ($this->hasForeignKey('sales_services', 'sales_services_schedule_id_foreign')) {
            Schema::table('sales_services', function (Blueprint $table) {
                $table->dropForeign('sales_services_schedule_id_foreign');
            });
        }

        if (Schema::hasColumn('sales_services', 'schedule_id')) {
            Schema::table('sales_services', function (Blueprint $table) {
                $table->dropColumn('schedule_id');
            });
        }
    }

    /**
     * Converte datas em Y-m-d, Y-m-d H:i:s ou d/m/Y para Y-m-d (null se inválida).
     */
    private function normalizeDate($value): ?string
    {
        if (!$value) {
            return null;
        }

        $value = trim((string) $value);

        try {
            if (preg_match('#^\d{2}/\d{2}/\d{4}#', $value)) {
                return Carbon::createFromFormat('d/m/Y', substr($value, 0, 10))->format('Y-m-d');
            }

            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function hasForeignKey(string $table, string $name): bool
    {
        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $name)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }
};
