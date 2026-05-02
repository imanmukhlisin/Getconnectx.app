<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/register', function () {
    return view('auth.register');
})->name('register');

Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');

Route::get('/chat', function () {
    return view('chat.index');
})->name('chat');

Route::get('/onboarding', function () {
    return view('auth.onboarding');
})->name('onboarding');

Route::get('/reset-password/{token}', function (string $token, \Illuminate\Http\Request $request) {
    $email = $request->query('email', '');
    $record = DB::table('password_reset_tokens')->where('email', $email)->first();
    
    $isExpired = true;
    if ($record) {
        $createdAt = Carbon::parse($record->created_at);
        if (!$createdAt->addMinutes(60)->isPast()) {
            if (Hash::check($token, $record->token)) {
                $isExpired = false;
            }
        }
    }

    return view('auth.reset-password', [
        'token' => $token,
        'email' => $email,
        'isExpired' => $isExpired,
    ]);
})->name('password.reset');

Route::get('/admin', function () {
    return view('admin.dashboard');
})->name('admin.dashboard');
