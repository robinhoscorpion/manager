<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$request = Illuminate\Http\Request::create('/admin/modelo-rci/1/file', 'GET');
$response = app()->handle($request);
echo 'STATUS: ' . $response->getStatusCode() . "\n";
