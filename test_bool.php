<?php 
require 'vendor/autoload.php'; 
$app = require_once 'bootstrap/app.php'; 
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap(); 
$validator = Illuminate\Support\Facades\Validator::make(['is_active' => 'true'], ['is_active' => 'boolean']); 
var_dump($validator->fails());
