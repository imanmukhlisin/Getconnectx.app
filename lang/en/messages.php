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
        ":greeting!\n\nThank you for choosing ConnectX. To ensure the highest level of security for your account, please use the following One-Time Password (OTP) to complete your verification process:\n\n*👉 :code 👈*\n\nThis code will strictly expire in :expiry minutes. For your protection, please do not share this code with anyone under any circumstances.\n\nBest regards,\nConnectX Security Team",
        
        ":greeting!\n\nWe have received a verification request for your ConnectX account. Please proceed by entering the authentication code below:\n\n*👉 :code 👈*\n\nFor privacy and security reasons, this code is valid for only :expiry minutes. Please ensure that you do not forward this message to unauthorized individuals.\n\nSincerely,\nConnectX Customer Service",
        
        ":greeting! Welcome to the ConnectX ecosystem.\n\nTo verify your identity and secure your account access, please input the following OTP on your application screen:\n\n*👉 :code 👈*\n\nAs a gentle reminder, this code is strictly confidential and will remain valid for the next :expiry minutes. Thank you for your trust.\n\nWarm regards,\nThe ConnectX Team",
        
        ":greeting!\n\nOur system has detected an authorization attempt for your ConnectX account. Use the security code below to finalize the verification procedure:\n\n*👉 :code 👈*\n\nThe code will automatically expire after :expiry minutes. If you did not initiate this request, please disregard this message to keep your account secure.\n\nThank you,\nConnectX Support",
        
        ":greeting!\n\nThank you for joining ConnectX. The final step to activate your secure session is to enter the verification code provided below:\n\n*👉 :code 👈*\n\nThe validity period for this code is :expiry minutes. We are fully committed to maintaining the confidentiality of your data and account.\n\nBest regards,\nThe ConnectX Team",

        ":greeting!\n\nIt is time to connect your ideas with the world through ConnectX! Please enter the authentication code below to continue your secure session:\n\n*👉 :code 👈*\n\nThis verification code will expire after :expiry minutes. Keep it strictly confidential to protect your data.\n\nTo your success,\nConnectX IT Department",

        ":greeting! Let us make great collaborations happen together.\n\nHere is your unique OTP code to access our platform:\n\n*👉 :code 👈*\n\nThe validity period for this code is limited to :expiry minutes. Please enter it immediately and do not share it with external parties.\n\nYours innovatively,\nConnectX Global",

        ":greeting!\n\nWe highly value your privacy and security. To confirm that this is truly you, please use the secret code provided below:\n\n*👉 :code 👈*\n\nEnter this code within :expiry minutes before it expires. If you did not register for an account, please ignore this message.\n\nThank you,\nConnectX Security Unit",

        ":greeting!\n\nCongratulations! You are just one step away from your new ConnectX dashboard. Please use the verification OTP below:\n\n*👉 :code 👈*\n\nThis security code is exclusively generated for your account and will expire in :expiry minutes. Beware of scams.\n\nWarm regards,\nConnectX Customer Care",

        ":greeting! We are thrilled to see your enthusiasm for ConnectX.\n\nPlease complete your account authentication process by accurately entering the following code:\n\n*👉 :code 👈*\n\nRemember, this code can only be used once and is valid for :expiry minutes. Do not share it with anyone.\n\nTo your continuous success,\nConnectX Management",
    ],
];
