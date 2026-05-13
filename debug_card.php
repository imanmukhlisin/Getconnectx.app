<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('name', 'like', '%Dimas%')->with(['tags', 'credentials', 'builder', 'onboardingSession.responses'])->first();
if (!$user) { echo "User not found\n"; exit; }

$transformer = app(App\Services\Discovery\CardTransformerService::class);
$card = $transformer->transformProfileCard($user, 0, null, $user);

echo json_encode($card, JSON_PRETTY_PRINT);
