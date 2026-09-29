<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$files = ['custom-logo-small.png', 'custom-logo.png', 'custom-logo-.png', 'logo-itacare.png'];
foreach ($files as $f) {
    $p = public_path('images/' . $f);
    if (file_exists($p)) {
        $info = getimagesize($p);
        echo "$f: {$info[0]}x{$info[1]} (" . filesize($p) . " bytes)\n";
    } else {
        echo "$f: NOT FOUND\n";
    }
}
