<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OTP Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for OTP generation, expiry, and rate limiting.
    |
    */

    'expiry_minutes'        => env('OTP_EXPIRY_MINUTES', 10),
    'max_sends_per_window'  => env('OTP_MAX_SENDS_PER_WINDOW', 3),
    'window_minutes'        => env('OTP_WINDOW_MINUTES', 10),

    'whatsapp' => [
        'provider'  => env('WHATSAPP_PROVIDER', 'fonnte'),
        'api_url'   => env('WHATSAPP_API_URL', 'https://api.fonnte.com/send'),
        'api_token' => env('WHATSAPP_API_TOKEN'),

        // Twilio-specific (used when provider = 'twilio')
        'twilio_sid'   => env('TWILIO_ACCOUNT_SID'),
        'twilio_token' => env('TWILIO_AUTH_TOKEN'),
        'twilio_from'  => env('TWILIO_WHATSAPP_FROM'),

        // Saung WA-specific (used when provider = 'saungwa')
        'saungwa_appkey'  => env('SAUNGWA_APP_KEY'),
        'saungwa_authkey' => env('SAUNGWA_AUTH_KEY'),
    ],
];
