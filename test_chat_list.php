<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$founder = \App\Models\User::has('startup')->where('is_onboarded', true)->first();
$talent = \App\Models\User::doesntHave('startup')->where('is_onboarded', true)->where('id', '!=', $founder->id)->first();

if (!$founder || !$talent) {
    die("Error: Insufficient users to execute API testing.\n");
}

echo "=== MESSAGE CONTROLLER API TESTING ===\n";
echo "[Participants Data]\n";
echo "Founder (Startup Owner) : {$founder->name}\n";
echo "Associated Startup      : {$founder->startup->name}\n";
echo "Startup Tagline         : {$founder->startup->tagline}\n";
echo "\n";
echo "Talent (Builder)        : {$talent->name}\n";
echo "Talent Headline         : {$talent->position}\n";
echo "--------------------------------------------------\n\n";

// Create temporary conversation to test
$conversation = \App\Models\Conversation::create([
    'last_message_at' => now(),
]);
$conversation->participants()->attach([$founder->id, $talent->id]);

$conversation->messages()->create([
    'sender_id' => $founder->id,
    'type' => 'text',
    'content' => 'API test message payload'
]);

$controller = app()->make(\App\Http\Controllers\Api\V1\Chat\MessageController::class);

// =========================================================
// API REQUEST 1: VIEWER_CONTEXT = TALENT
// =========================================================
$req1 = \Illuminate\Http\Request::create('/api/v1/chat/conversations', 'GET', [
    'viewer_context' => 'talent'
]);
$req1->setUserResolver(fn() => $talent);
$res1 = $controller->index($req1);
$data1 = json_decode($res1->getContent(), true);

echo "=== API REQUEST [viewer_context = talent] ===\n";
echo "Requesting User : {$talent->name}\n";
if (isset($data1['error'])) {
    echo "API Response    : ERROR - " . $data1['message'] . "\n";
} else {
    $chat1 = collect($data1['conversations'])->firstWhere('id', $conversation->id)['other_user'] ?? null;
    if ($chat1) {
        echo "Response Data   : SUCCESS\n";
        echo "Viewed Entity   : " . $chat1['name'] . "\n";
        echo "Viewed Headline : " . $chat1['headline'] . "\n";
    } else {
        echo "Response Data   : FAILED (Conversation not found)\n";
    }
}
echo "\n";


// =========================================================
// API REQUEST 2: VIEWER_CONTEXT = STARTUP
// =========================================================
$req2 = \Illuminate\Http\Request::create('/api/v1/chat/conversations', 'GET', [
    'viewer_context' => 'startup'
]);
$req2->setUserResolver(fn() => $founder);
$res2 = $controller->index($req2);
$data2 = json_decode($res2->getContent(), true);

echo "=== API REQUEST [viewer_context = startup] ===\n";
echo "Requesting User : {$founder->name}\n";
if (isset($data2['error'])) {
    echo "API Response    : ERROR - " . $data2['message'] . "\n";
} else {
    $chat2 = collect($data2['conversations'])->firstWhere('id', $conversation->id)['other_user'] ?? null;
    if ($chat2) {
        echo "Response Data   : SUCCESS\n";
        echo "Viewed Entity   : " . $chat2['name'] . "\n";
        echo "Viewed Headline : " . $chat2['headline'] . "\n";
    } else {
        echo "Response Data   : FAILED (Conversation not found)\n";
    }
}
echo "\n";

// Cleanup 
$conversation->delete();
