<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$founder = \App\Models\User::where('name', 'Thirza Salendra')->first();
if ($founder) {
    echo "Startup name: " . $founder->startup->name . "\n";
    echo "Startup tagline: '" . $founder->startup->tagline . "'\n";
    echo "User startup_tagline: '" . $founder->startup_tagline . "'\n";
} else {
    echo "User not found\n";
}
