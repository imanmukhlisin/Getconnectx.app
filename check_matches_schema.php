<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$columns = \Illuminate\Support\Facades\Schema::getColumnListing('matches');
print_r($columns);

$columns2 = \Illuminate\Support\Facades\Schema::getColumnListing('likes');
print_r($columns2);

$columns3 = \Illuminate\Support\Facades\Schema::getColumnListing('conversations');
print_r($columns3);
