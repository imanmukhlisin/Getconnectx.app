<?php

return [
    // Auth Responses
    'registration_success' => 'Registration successful. Please verify your email.',
    'email_already_verified' => 'Email has already been verified.',
    'email_otp_sent' => 'OTP Verification Code has been sent to :email. This code is valid for 10 minutes.',
    'email_verify_success' => 'Email verified successfully.',
    'whatsapp_already_verified' => 'WhatsApp has already been verified.',
    'whatsapp_otp_sent' => 'OTP Verification Code has been sent to WhatsApp :number. This code is valid for 10 minutes.',
    'whatsapp_delivery_failed' => 'Failed to send WhatsApp OTP. Please try again.',
    'whatsapp_verify_success' => 'WhatsApp has already been verified. Registration complete.',
    'registration_complete' => 'Registration complete! Welcome to ConnectX.',
    
    // OAuth Responses
    'oauth_provider_unsupported' => "Provider ':provider' is not supported. Use: :allowed.",
    'oauth_login_cancelled' => "OAuth login was cancelled or an error occurred.",
    'oauth_info_failed' => "Failed to get information from the OAuth provider.",
    'oauth_token_invalid' => "The :provider token is invalid or has expired. Please login again via :provider.",
    'oauth_login_success_new' => "Login via :provider successful. Please complete WhatsApp verification.",
    'oauth_login_success_returning' => "Login via :provider successful. Welcome back!",

    // Exception Responses
    'validation_failed' => 'Oops... The given data was invalid. Please check the fields again.',
    'too_many_requests' => 'Oops... Too many requests. Please wait a moment before trying again.',
    'otp_not_found' => 'Oops... OTP not found or has expired. Please request a new OTP.',
    'otp_invalid' => 'Oops... The OTP code you entered is incorrect.',
    'otp_rate_limit_minute' => 'Oops... Too many OTP requests. Please try again in :minutes minutes.',
    
    // Validations (Register Request)
    'val_entity_type_required' => 'Entity type is required (talent or startup).',
    'val_entity_type_in' => 'Entity type must be either "talent" or "startup".',
    'val_email_required' => 'Email address is required.',
    'val_email_format' => 'Invalid email format (e.g., name@email.com).',
    'val_email_max' => 'Email is too long (maximum 255 characters).',
    'val_email_unique' => 'This email is already registered. Please use another email or login.',
    'val_password_required' => 'Password is required.',
    'val_password_confirmed' => 'Password confirmation does not match the typed password.',
    'val_password_min' => 'Password must be at least 8 characters long.',
    'val_password_mixed' => 'Password must contain at least 1 uppercase and 1 lowercase letter.',
    'val_password_numbers' => 'Password must contain at least 1 number.',
    'val_password_symbols' => 'Password must contain at least 1 symbol / unique character (@, #, !, etc).',
    'val_password_uncompromised' => 'Your password is too common or has been compromised in a data leak. Please create a safer and more unique password combination.',
    'val_password_conf_required' => 'Password confirmation is required.',
    
    // Validations (Email & WhatsApp Verify)
    'val_otp_required' => 'OTP code is required.',
    'val_otp_digits' => 'OTP code must be 6 numeric digits.',
    'val_wa_number_required' => 'WhatsApp number is required.',
    'val_wa_number_regex' => 'WhatsApp number must use an international format, e.g., +6281234567890.',
    'val_provider_token_required' => 'The OAuth provider token is required.',
];
