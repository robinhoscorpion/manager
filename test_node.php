<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Symfony\Component\Process\Process;

$path = \Illuminate\Support\Facades\Storage::disk('local')->path(\App\Models\RciTemplate::first()->file_path);
$process = new Process(['C:\\Program Files\\nodejs\\node.exe', base_path('extract_pdf_fields.cjs'), $path]);
$process->run();
echo "Normal: " . $process->getErrorOutput() . "\n";

// With cmd /c
$process2 = new Process(['cmd', '/c', 'C:\\Program Files\\nodejs\\node.exe', base_path('extract_pdf_fields.cjs'), $path]);
$process2->run();
echo "CMD: " . $process2->getErrorOutput() . "\n";
