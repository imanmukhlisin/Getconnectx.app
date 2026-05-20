<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$count = DB::table('onboarding_options')->where('question_id', 'q_city')->count();
echo "Total q_city options: " . $count . "\n";
