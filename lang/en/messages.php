<?php

return [
    // Auth Responses
    'registration_success' => 'Registration successful. Please verify your email.',
    'email_already_verified' => 'Email has already been verified.',
    'email_otp_sent' => 'OTP Verification Code has been sent to :email. This code is valid for 10 minutes.',
    'login_otp_sent' => 'Login code has been sent to :email.',
    'email_verify_success' => 'Email verified successfully.',
    'whatsapp_already_verified' => 'WhatsApp has already been verified.',
    'whatsapp_otp_sent' => 'OTP Verification Code has been sent to WhatsApp :number. This code is valid for 10 minutes.',
    'whatsapp_delivery_failed' => 'Failed to send WhatsApp OTP. Please try again.',
    'whatsapp_verify_success' => 'WhatsApp has already been verified. Registration complete.',
    'registration_complete' => 'Registration complete! Welcome to ConnectX.',
    'login_success' => 'Login successful! Welcome back.',
    'login_failed' => 'Invalid email or password. Please try again.',
    'inactive_user' => 'Your account is not active yet. Please complete the registration or verification process first.',

    // Password Reset
    'reset_link_sent'        => 'Password reset link has been sent to your email. Valid for 60 minutes.',
    'reset_link_if_exists'   => 'If that email is registered, a reset link will be sent.',
    'reset_password_success' => 'Password updated successfully. You can now log in with your new password.',
    'reset_token_expired'    => 'The reset link has expired or is invalid. Please request a new one.',
    
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
'val_email_required' => 'Email address is required.',
    'val_email_format' => 'Invalid email format (e.g., name@email.com).',
    'val_email_max' => 'Email is too long (maximum 255 characters).',
    'val_email_unique' => 'This email is already registered. Please use another email or login.',
    'val_email_not_found' => 'We could not find an account with that email address.',
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
    // WhatsApp Delivery Content
    'wa_otp_messages' => [
        ":greeting! Welcome to ConnectX. Your verification code is: *:code*. Valid for :expiry minutes. Do not share this code with anyone.",
        ":greeting! Before starting your journey on ConnectX, please verify your account. Your OTP code is: *:code*. It expires in :expiry minutes.",
        ":greeting! Ready to build something great? Use this OTP to log in to ConnectX: *:code*. Valid for :expiry minutes.",
        ":greeting! Here is your ConnectX verification code: *:code*. Please do not share it. It expires in :expiry minutes.",
        ":greeting! Let's get you connected! Your ConnectX OTP code is: *:code*. Valid for the next :expiry minutes.",
        ":greeting! To secure your ConnectX account, please use this verification code: *:code*. It is valid for :expiry minutes.",
        ":greeting! You are almost there! Enter this OTP to continue to ConnectX: *:code*. Expires in :expiry minutes.",
        ":greeting! Your ConnectX login code is: *:code*. Do not share this with anyone. Valid for :expiry minutes.",
        ":greeting! Welcome back to ConnectX. Please enter this verification code: *:code*. It will expire in :expiry minutes.",
        ":greeting! Verify your ConnectX account using this OTP: *:code*. Keep it secret. Valid for :expiry minutes."
    ],
];
