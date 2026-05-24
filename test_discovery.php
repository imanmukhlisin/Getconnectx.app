<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Find a user who is onboarded and has a talent profile
$user = \App\Models\User::where('is_onboarded', true)->first();

if (!$user) {
    echo "No onboarded user found.\n";
    exit;
}

echo "Testing as User: {$user->name} ({$user->id})\n";

// Set up the request
$request = \Illuminate\Http\Request::create('/api/v1/discovery/cards', 'POST', [
    'context' => [
        'mode' => 'explore_startups'
    ],
    'filters' => [
        // 'industryIds' => ['AI'] // you can add filters here to test
    ]
]);
$request->setUserResolver(function() use ($user) { return $user; });

// Validate request manually since we aren't running through full HTTP pipeline
$validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
    'context.mode' => 'required|string',
    'filters' => 'nullable|array',
    'filters.industryIds' => 'nullable|array',
    'pagination.limit' => 'nullable|integer'
]);

$discoveryCardsRequest = \App\Http\Requests\DiscoveryCardsRequest::createFrom($request);
$discoveryCardsRequest->setContainer($app);
$discoveryCardsRequest->setUserResolver(function() use ($user) { return $user; });
$discoveryCardsRequest->setValidator($validator);

// Call controller
$controller = $app->make(\App\Http\Controllers\Api\V1\Discovery\DiscoveryController::class);
try {
    $response = $controller->cards($discoveryCardsRequest);
    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Response Content:\n";
    echo json_encode(json_decode($response->getContent()), JSON_PRETTY_PRINT) . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
