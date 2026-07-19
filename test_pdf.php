<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$controller = app()->make(\App\Http\Controllers\SalesServiceController::class);
$service = \App\Models\SalesService::find(39);
if (!$service) {
    echo 'Service not found';
    exit;
}
try {
    $response = $controller->pdfProposta($service);
    dump($response);
} catch (\Exception $e) {
    echo 'EXCEPTION: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
}
