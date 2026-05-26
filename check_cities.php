<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$count = \Illuminate\Support\Facades\DB::table('onboarding_options')
    ->where('question_id', 'q_location')
    ->count();

echo "Jumlah kota di onboarding_options (q_location): " . $count . "\n";
