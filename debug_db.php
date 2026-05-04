<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $count = DB::table('onboarding_options')->count();
    $questions = DB::table('onboarding_options')->select('question_id')->distinct()->pluck('question_id')->toArray();
    
    echo "Count: " . $count . "\n";
    echo "Questions: " . implode(', ', $questions) . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
