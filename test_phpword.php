<?php

require __DIR__ . '/vendor/autoload.php';

$ref = new ReflectionClass(\PhpOffice\PhpWord\TemplateProcessor::class);
echo "setComplexValue exists: " . ($ref->hasMethod('setComplexValue') ? "Yes" : "No") . "\n";
