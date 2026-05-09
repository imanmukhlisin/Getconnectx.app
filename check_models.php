<?php
$user = \App\Models\User::first();
dump('User attributes:', array_keys($user->getAttributes()));
$startup = \App\Models\Startup::first();
if ($startup) {
    dump('Startup attributes:', array_keys($startup->getAttributes()));
}
$builder = \Illuminate\Support\Facades\DB::table('builders')->first();
if ($builder) {
    dump('Builder attributes:', array_keys((array)$builder));
}
