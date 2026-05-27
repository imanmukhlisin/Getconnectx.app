<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\UserMatch;
use App\Models\Like;

echo "=== DIAGNOSTIC DATABASE STATUS ===\n";

// 1. Check jobs table count
$jobsCount = DB::table('jobs')->count();
echo "Jobs pending in queue: {$jobsCount}\n";

$failedJobsCount = DB::table('failed_jobs')->count();
echo "Failed jobs count: {$failedJobsCount}\n";

// 2. Check total matches and matches with analysis
$totalMatches = UserMatch::count();
$matchesWithAnalysis = UserMatch::has('analysis')->count();
echo "Total matches: {$totalMatches}\n";
echo "Matches with analysis: {$matchesWithAnalysis}\n";

// 3. Print last 5 matches
echo "\n--- Last 5 Matches ---\n";
$lastMatches = UserMatch::latest('matched_at')->take(5)->get();
foreach ($lastMatches as $m) {
    $hasScore = $m->scores()->exists() ? "Yes" : "No";
    $hasAnalysis = $m->analysis()->exists() ? "Yes" : "No";
    echo "Match ID: {$m->id} | UserA: {$m->user_id} | UserB: {$m->matched_user_id} | Context: {$m->viewer_context} | Has Score: {$hasScore} | Has Analysis: {$hasAnalysis}\n";
}

// 4. Print last 5 likes
echo "\n--- Last 5 Likes ---\n";
$lastLikes = Like::latest()->take(5)->get();
foreach ($lastLikes as $l) {
    echo "Like ID: {$l->id} | From: {$l->from_user_id} | To: {$l->to_user_id} | Mutual: " . ($l->is_mutual ? "Yes" : "No") . " | Context: {$l->viewer_context}\n";
}
