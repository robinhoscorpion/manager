<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$item = \App\Models\ContractTemplate::first();
echo "Table structure: " . json_encode($item ? array_keys($item->toArray()) : null) . "\n";

$file = 'C:/Users/Robson/Videos/CONTRATO ITACARE NOVO.docx';
if (file_exists($file)) {
    echo "File found!\n";
    // Extract text from docx
    $zip = new ZipArchive;
    if ($zip->open($file) === true) {
        if (($index = $zip->locateName('word/document.xml')) !== false) {
            $data = $zip->getFromIndex($index);
            $zip->close();
            $data = str_replace('</w:p>', "\n", $data);
            $text = strip_tags($data);
            file_put_contents('extracted_contract.txt', $text);
            echo "Extracted text to extracted_contract.txt\n";
        }
    } else {
        echo "Failed to open docx as zip.\n";
    }
} else {
    echo "File not found at $file\n";
}
