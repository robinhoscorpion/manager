<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncBillsRecipient extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:sync-recipients';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza o recebedor das parcelas (bills) antigas com base nas Formas de Pagamento configuradas.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando sincronização de recebedores...');

        $map = \App\Models\PaymentMethod::pluck('recipient', 'name')->toArray();
        $count = 0;

        foreach ($map as $method => $recipient) {
            if ($recipient) {
                $updated = \App\Models\Bill::where('payment_method', $method)
                    ->whereNull('recipient')
                    ->update(['recipient' => $recipient]);
                
                if ($updated > 0) {
                    $this->line("Atualizadas {$updated} parcelas da forma de pagamento: {$method} -> {$recipient}");
                }
                $count += $updated;
            }
        }

        $this->info("Concluído! Total de parcelas antigas atualizadas: {$count}");
    }
}
