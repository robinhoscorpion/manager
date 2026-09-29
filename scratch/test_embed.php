<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Client;
use App\Mail\SocioWelcomeMail;

$client = Client::first();
$mail = new SocioWelcomeMail($client);

// Let's test embed
try {
    $cid = $mail->embed(public_path('images/custom-logo.png'));
    echo "Embedded CID for custom-logo.png: " . $cid . "\n";
} catch (\Throwable $e) {
    echo "Embed error: " . $e->getMessage() . "\n";
}

try {
    $cid2 = $mail->embed(public_path('images/logo-itacare.png'));
    echo "Embedded CID for logo-itacare.png: " . $cid2 . "\n";
} catch (\Throwable $e) {
    echo "Embed error 2: " . $e->getMessage() . "\n";
}
