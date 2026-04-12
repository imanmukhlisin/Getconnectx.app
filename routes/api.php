<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\OAuthController;
use App\Http\Controllers\Api\V1\Profile\ProfileController;
use App\Http\Controllers\Api\V1\Chat\MessageController;
use App\Http\Controllers\Api\V1\OnboardingController;

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ConnectX API Routes – v1
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api (via bootstrap/app.php configuration).
|
| Sequential Registration Lock:
|   Each authenticated step requires registration.progress:{N} middleware,
|   ensuring strict linear ordering. If step N is not reached, the server
|   returns 403 with the correct `next_step` hint.
|
*/

Route::prefix('v1')->group(function () {

    // ─── Public: Registration (No Auth Required) ──────────────────────────────
    Route::prefix('auth')->group(function () {

        // ── Step 1: Manual Register ──────────────────────────────────────────
        Route::post('register', [AuthController::class, 'register'])
            ->name('auth.register');

        // ── Login: Password (Tradisional) ─────────────────────────────────────
        Route::post('login/password', [AuthController::class, 'loginWithPassword'])
            ->name('auth.login.password');

        // ── Login: OTP (Passwordless) ─────────────────────────────────────────
        Route::prefix('login/otp')->group(function () {
            Route::post('send', [AuthController::class, 'loginOtpSend'])
                ->name('auth.login.otp.send');
            Route::post('verify', [AuthController::class, 'loginOtpVerify'])
                ->name('auth.login.otp.verify');
        });

        // ── OAuth: Redirect + Callback + Native SDK Verify ───────────────────
        Route::prefix('oauth/{provider}')->group(function () {
            Route::get('/', [OAuthController::class, 'redirect'])
                ->name('auth.oauth.redirect');
            Route::get('callback', [OAuthController::class, 'callback'])
                ->name('auth.oauth.callback');
            Route::post('verify-token', [OAuthController::class, 'verifyToken'])
                ->name('auth.oauth.verify-token');
        });
    });

    // ─── Authenticated: Registration Flow Steps ────────────────────────────────
    // All routes below require a valid Sanctum token (issued at Step 1 or OAuth)
    Route::prefix('auth')->middleware('auth:sanctum')->group(function () {

        // ── Step 2: Send Email OTP ────────────────────────────────────────────
        // Requires: registration_step >= 1 (just registered)
        Route::post('email/send-otp', [AuthController::class, 'sendEmailOtp'])
            ->middleware('registration.progress:1')
            ->name('auth.email.send-otp');

        Route::post('email/resend-otp', [AuthController::class, 'sendEmailOtp'])
            ->middleware('registration.progress:1')
            ->name('auth.email.resend-otp');

        // ── Step 3: Verify Email OTP ──────────────────────────────────────────
        // Requires: registration_step >= 2 (OTP was sent)
        Route::post('verify-email', [AuthController::class, 'verifyEmail'])
            ->middleware('registration.progress:2')
            ->name('auth.verify-email');

        // ── Step 4: Send WhatsApp OTP ─────────────────────────────────────────
        // Requires: registration_step >= 3 (email verified)
        Route::post('whatsapp/send-otp', [AuthController::class, 'sendWhatsAppOtp'])
            ->middleware('registration.progress:3')
            ->name('auth.whatsapp.send-otp');

        Route::post('whatsapp/resend-otp', [AuthController::class, 'sendWhatsAppOtp'])
            ->middleware('registration.progress:3')
            ->name('auth.whatsapp.resend-otp');

        Route::post('verify-whatsapp', [AuthController::class, 'verifyWhatsApp'])
            ->middleware('registration.progress:4')
            ->name('auth.verify-whatsapp');

        // ── Refresh Token ─────────────────────────────────────────────────────
        Route::post('refresh', [AuthController::class, 'refreshToken'])
            ->name('auth.refresh');
    });

    // ─── Authenticated: Profile & Onboarding ──────────────────────────────────
    Route::prefix('profile')->middleware(['auth:sanctum', 'registration.progress:5'])->group(function () {
        Route::get('/', [ProfileController::class, 'index'])
            ->name('profile.index');
        Route::get('tags', [ProfileController::class, 'tags'])
            ->name('profile.tags');
        Route::put('fcm-token', [ProfileController::class, 'updateFcmToken'])
            ->name('profile.update.fcm-token');
    });

    // ─── Authenticated: Dynamic Onboarding Engine ─────────────────────────────
    Route::prefix('onboarding')->middleware('auth:sanctum')->group(function () {
        Route::post('sessions', [OnboardingController::class, 'start'])->name('onboarding.sessions.start');
        Route::get('sessions/{session}/current', [OnboardingController::class, 'current'])->name('onboarding.sessions.current');
        Route::post('sessions/{session}/answer', [OnboardingController::class, 'answer'])->name('onboarding.sessions.answer');
        Route::post('sessions/{session}/back', [OnboardingController::class, 'back'])->name('onboarding.sessions.back');
        Route::get('sessions/{session}', [OnboardingController::class, 'show'])->name('onboarding.sessions.show');
    });

    // ─── Authenticated: Media Upload ──────────────────────────────────────────
    Route::prefix('media')->middleware('auth:sanctum')->group(function () {
        Route::post('upload-url', [\App\Http\Controllers\Api\V1\MediaController::class, 'generateUploadUrl'])
            ->name('media.upload-url');
    });

    // ─── Authenticated: Chat System ───────────────────────────────────────────
    Route::prefix('conversations')->middleware(['auth:sanctum', 'registration.progress:5'])->group(function () {
        Route::get('/', [MessageController::class, 'index'])
            ->name('conversations.index');
        Route::post('/', [MessageController::class, 'storeConversation'])
            ->name('conversations.store');
        
        Route::get('{conversation}/messages', [MessageController::class, 'messages'])
            ->name('conversations.messages');
        Route::post('{conversation}/messages', [MessageController::class, 'sendMessage'])
            ->name('conversations.messages.send');

        // New requirements from image
        Route::get('{conversation}/media', [MessageController::class, 'media'])
            ->name('conversations.media');
        Route::post('{conversation}/media', [MessageController::class, 'sendMedia'])
            ->name('conversations.media.send');
        Route::post('{conversation}/typing', [MessageController::class, 'signalTyping'])
            ->name('conversations.typing');
    });
});

