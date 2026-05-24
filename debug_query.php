<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user1Id = 'a1c4aca1-8878-44d7-b07d-88108b012619';
$user2Id = 'a1c3e0df-3691-44c1-bd0a-13de8b773ebf';

// Check if conversation already exists
$conversation = \App\Models\Conversation::whereHas('participants', function ($query) use ($user1Id) {
    $query->where('user_id', $user1Id);
})->whereHas('participants', function ($query) use ($user2Id) {
    $query->where('user_id', $user2Id);
})->first();

if (!$conversation) {
    $conversation = \App\Models\Conversation::create();
    $conversation->participants()->attach([$user1Id, $user2Id]);
    echo "Conversation created successfully!\n";
    echo "Conversation ID: " . $conversation->id . "\n";
} else {
    echo "Conversation already exists!\n";
    echo "Conversation ID: " . $conversation->id . "\n";
}
