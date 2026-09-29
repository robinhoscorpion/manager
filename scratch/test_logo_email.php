<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Client;
use App\Mail\SocioWelcomeMail;
use Illuminate\Support\Facades\Mail;

$client = Client::whereNotNull('email')->first();
if (!$client) {
    echo "Nenhum cliente encontrado\n";
    exit;
}

echo "Enviando e-mail de teste para " . $client->email . "...\n";

try {
    Mail::to('nosborsemag@gmail.com')->send(new SocioWelcomeMail($client));
    echo "E-mail enviado com sucesso!\n";
} catch (\Throwable $e) {
    echo "Erro ao enviar e-mail: " . $e->getMessage() . "\n";
}
