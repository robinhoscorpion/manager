<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Assinatura digital: consulta envios em andamento (plano B caso algum webhook se perca)
Artisan::command('signatures:sync {--all : Ignora o intervalo mínimo desde a última consulta}', function (\App\Services\Signature\SignatureService $signatures) {
    $query = \App\Models\ContractSignature::query()
        ->where(function ($q) {
            $q->whereIn('status', \App\Models\ContractSignature::OPEN_STATUSES)
              ->orWhere(fn ($q) => $q->where('status', \App\Models\ContractSignature::STATUS_SIGNED)->whereNull('signed_file_path'));
        })
        ->whereNotNull('external_id');

    if (!$this->option('all')) {
        $query->where(fn ($q) => $q->whereNull('last_synced_at')->orWhere('last_synced_at', '<', now()->subMinutes(25)));
    }

    $count = 0;
    $query->orderBy('id')->each(function ($signature) use ($signatures, &$count) {
        $signatures->syncQuietly($signature);
        $count++;
    });

    $this->info("{$count} envio(s) de assinatura consultado(s).");
})->purpose('Atualiza o status das assinaturas digitais em andamento');

Schedule::command('signatures:sync')->everyThirtyMinutes()->withoutOverlapping();

// Contas a receber: marca parcelas vencidas (e devolve para "pendente" as que foram prorrogadas)
Artisan::command('bills:refresh-overdue', function () {
    $result = \App\Services\Finance\LateCharges::refreshStatuses();
    $this->info("{$result['overdue']} parcela(s) marcada(s) como vencida(s); {$result['back_to_pending']} voltaram para pendente.");
})->purpose('Atualiza o status "vencido" das parcelas a receber');

Schedule::command('bills:refresh-overdue')->dailyAt('00:05');
