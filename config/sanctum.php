<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Stateful Domains
    |--------------------------------------------------------------------------
    */

    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
        env('APP_URL') ? ','.parse_url(env('APP_URL'), PHP_URL_HOST) : ''
    ))),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Guards
    |--------------------------------------------------------------------------
    */

    'guard' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Expiration Minutes
    |--------------------------------------------------------------------------
    |
    | Null = tokens never expire (not recommended for registration tokens).
    | Set permanent auth tokens to null; registration tokens get explicit
    | expiry via createToken(..., now()->addMinutes(N)).
    |
    */

    'expiration' => null,

    /*
    |--------------------------------------------------------------------------
    | Registration Token Expiry (minutes)
    |--------------------------------------------------------------------------
    |
    | Temporary tokens issued during registration flow expire after this
    | many minutes of inactivity.
    |
    */

    'registration_token_expiry' => env('SANCTUM_TOKEN_EXPIRY_MINUTES', 60),

    /*
    |--------------------------------------------------------------------------
    | Token Prefix
    |--------------------------------------------------------------------------
    */

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Middleware
    |--------------------------------------------------------------------------
    */

    'middleware' => [
        'authenticate_session' => \Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies'      => \Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token'  => \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],

];
