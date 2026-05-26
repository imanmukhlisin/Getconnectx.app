<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$founder = \App\Models\User::has('startup')->where('is_onboarded', true)->first();

echo "User.startup_tagline: '{$founder->startup_tagline}'\n";
echo "Startup.tagline: '{$founder->startup->tagline}'\n";
