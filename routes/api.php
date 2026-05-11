<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\LinkedInSyncController;
use App\Http\Controllers\Api\V1\Auth\OAuthController;
use App\Http\Controllers\Api\V1\Profile\ProfileController;
use App\Http\Controllers\Api\V1\Chat\MessageController;
use App\Http\Controllers\Api\V1\MediaUploadController;
use App\Http\Controllers\Api\V1\OnboardingController;
use App\Http\Controllers\Api\V1\Discovery\FeedController;
use App\Http\Controllers\Api\V1\Discovery\SwipeController;
use App\Http\Controllers\Api\V1\Discovery\DiscoveryController;
use App\Http\Controllers\Api\V1\WhatsappWebhookController;

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

        // ── Forgot / Reset Password ──────────────────────────────────────────
        Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLink'])
            ->name('auth.forgot-password');
        Route::post('reset-password', [ForgotPasswordController::class, 'resetPassword'])
            ->name('auth.reset-password');
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

        // ── Session ───────────────────────────────────────────────────────────
        Route::get('session', [AuthController::class, 'session'])
            ->name('auth.session');

        // ── LinkedIn Sync (Async Background Job) ──────────────────────────────
        // Terima linkedin_url LinkedIn, trigger Apify
        // Response langsung dalam < 100ms
        Route::post('linkedin-sync', [LinkedInSyncController::class, 'sync'])
            ->name('auth.linkedin-sync');
    });


    // ─── Authenticated: Profile & Onboarding ──────────────────────────────────
    Route::middleware(['auth:sanctum', 'registration.progress:5'])->group(function () {
        Route::get('me/profile', [ProfileController::class, 'me'])->name('profile.me');
        Route::patch('me/profile', [ProfileController::class, 'updateMe'])->name('profile.update_me');
        Route::get('profile-options', [ProfileController::class, 'options'])->name('profile.options');
        Route::get('profiles/{id}', [ProfileController::class, 'show'])->name('profile.show');

        // endpoint tambahan untuk fitur FCM flutter
        Route::put('profile/fcm-token', [ProfileController::class, 'updateFcmToken'])
            ->name('profile.update.fcm-token');

        // endpoint untuk update koordinat lokasi user
        Route::put('profile/location', [ProfileController::class, 'updateLocation'])
            ->name('profile.update.location');
    });

    // ─── Authenticated: Dynamic Onboarding Engine ─────────────────────────────
    Route::prefix('onboarding')->middleware('auth:sanctum')->group(function () {
        Route::get('resume',                    [OnboardingController::class, 'resume'])->name('onboarding.resume');
        Route::post('sessions',                 [OnboardingController::class, 'start'])->name('onboarding.sessions.start');
        Route::get('sessions/{session}/current',[OnboardingController::class, 'current'])->name('onboarding.sessions.current');
        Route::post('sessions/{session}/answer',[OnboardingController::class, 'answer'])->name('onboarding.sessions.answer');
        Route::post('sessions/{session}/back',  [OnboardingController::class, 'back'])->name('onboarding.sessions.back');
        Route::get('sessions/{session}',        [OnboardingController::class, 'show'])->name('onboarding.sessions.show');

        // Simplified Onboarding Endpoints
        Route::get('status',                    [OnboardingController::class, 'status'])->name('onboarding.status');
        Route::post('role',                     [OnboardingController::class, 'saveRole'])->name('onboarding.role');
        Route::post('builder-type',              [OnboardingController::class, 'saveBuilderType'])->name('onboarding.builder-type');
        Route::post('preferences',              [OnboardingController::class, 'savePreferences'])->name('onboarding.preferences');
    });

    // ─── Authenticated: Media Upload ──────────────────────────────────────────
    Route::prefix('media')->middleware('auth:sanctum')->group(function () {
        Route::post('upload-url', [\App\Http\Controllers\Api\V1\MediaController::class, 'generateUploadUrl'])
            ->name('media.upload-url');
    });

    // ─── Authenticated: Matchmaking System ────────────────────────────────────
    Route::middleware(['auth:sanctum', 'registration.progress:5'])->group(function () {
        Route::get('likes-you', [\App\Http\Controllers\Api\V1\MatchmakingController::class, 'likesYouList'])
            ->name('likes-you.index');
    });

    Route::prefix('matches')->middleware(['auth:sanctum', 'registration.progress:5'])->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V1\MatchmakingController::class, 'index'])
            ->name('matches.index');
        Route::get('{match}/analysis', [\App\Http\Controllers\Api\V1\MatchmakingController::class, 'analysis'])
            ->name('matches.analysis');
        Route::post('like', [\App\Http\Controllers\Api\V1\MatchmakingController::class, 'like'])
            ->name('matches.like');
    });

    // ─── Authenticated: Discovery — Feed ─────────────────────────────────────
    Route::prefix('feed')->middleware(['auth:sanctum', 'registration.progress:5'])->group(function () {
        Route::get('/', [FeedController::class, 'index'])->name('feed.index');
    });

    // ─── Authenticated: Discovery — Swipe ────────────────────────────────────
    Route::prefix('swipe')->middleware(['auth:sanctum', 'registration.progress:5'])->group(function () {
        Route::post('connect', [SwipeController::class, 'connect'])->name('swipe.connect');
        Route::post('skip',    [SwipeController::class, 'skip'])->name('swipe.skip');
    });

    // ─── Authenticated: Discovery V2 — Filter Engine ─────────────────────────
    Route::prefix('discovery')->middleware(['auth:sanctum', 'registration.progress:5'])->group(function () {
        Route::get('filter-options',         [DiscoveryController::class, 'filterOptions'])->name('discovery.filter-options');
        Route::post('cards',                 [DiscoveryController::class, 'cards'])->name('discovery.cards');
        Route::post('cards/{targetId}/action', [DiscoveryController::class, 'swipeAction'])->name('discovery.swipe-action');
        Route::post('swipes/rewind',         [DiscoveryController::class, 'rewind'])->name('discovery.rewind');
    });

    // ─── Authenticated: Media Upload (Chat) ───────────────────────────────────
    Route::middleware(['auth:sanctum', 'registration.progress:5'])->group(function () {
        Route::post('upload', [MediaUploadController::class, 'upload'])
            ->name('media.upload');
    });

    // ─── Authenticated: Chat System ───────────────────────────────────────────
    Route::prefix('conversations')->middleware(['auth:sanctum', 'registration.progress:5'])->group(function () {
        // 1. GET /conversations — list conversations (paginated)
        Route::get('/', [MessageController::class, 'index'])
            ->name('conversations.index');

        // 2. GET /conversations/{id}/messages — cursor-based history
        Route::get('{conversation}/messages', [MessageController::class, 'messages'])
            ->name('conversations.messages');

        // 3. POST /conversations/{id}/messages — send a message
        Route::post('{conversation}/messages', [MessageController::class, 'sendMessage'])
            ->name('conversations.messages.send');

        // 4. POST /conversations/{id}/read — mark as read
        Route::post('{conversation}/read', [MessageController::class, 'markRead'])
            ->name('conversations.read');

        // 5. GET /conversations/{id}/media — shared media gallery
        Route::get('{conversation}/media', [MessageController::class, 'media'])
            ->name('conversations.media');
    });

    // ─── Authenticated: Team & Startup Management ─────────────────────────────
    Route::middleware(['auth:sanctum', 'registration.progress:5'])->group(function () {
        // Active Startup Context
        Route::prefix('me/startup')->group(function () {
            Route::get('team-overview', [\App\Http\Controllers\Api\V1\TeamOverviewController::class, 'index']);
            Route::get('invitation-options', [\App\Http\Controllers\Api\V1\StartupInvitationController::class, 'options']);
            Route::post('invitations', [\App\Http\Controllers\Api\V1\StartupInvitationController::class, 'store']);
            Route::delete('invitations/{invitationId}', [\App\Http\Controllers\Api\V1\StartupInvitationController::class, 'destroy']);
        });

        // Incoming Invitations (Talent Context)
        Route::prefix('me/startup-invitations')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\IncomingInvitationController::class, 'index']);
            Route::post('{invitationId}/respond', [\App\Http\Controllers\Api\V1\IncomingInvitationController::class, 'respond']);
        });

        // Member Management
        Route::prefix('startups/{startupId}/team-members')->group(function () {
            Route::patch('{memberId}', [\App\Http\Controllers\Api\V1\TeamMemberController::class, 'update']);
            Route::delete('{memberId}', [\App\Http\Controllers\Api\V1\TeamMemberController::class, 'destroy']);
        });

        // Applications
        Route::get('applications', [\App\Http\Controllers\Api\V1\ApplicationController::class, 'index']);
    });

    // ─── Admin API ────────────────────────────────────────────────────────────
    Route::prefix('admin')->middleware('auth:sanctum')->group(function () {
        Route::get('stats',                  [\App\Http\Controllers\Admin\AdminUserController::class, 'stats']);
        Route::get('users',                  [\App\Http\Controllers\Admin\AdminUserController::class, 'index']);
        Route::get('users/{id}',             [\App\Http\Controllers\Admin\AdminUserController::class, 'show']);
        Route::post('users/{id}/block',      [\App\Http\Controllers\Admin\AdminUserController::class, 'block']);
        Route::post('users/{id}/unblock',    [\App\Http\Controllers\Admin\AdminUserController::class, 'unblock']);
        Route::get('notifications',          [\App\Http\Controllers\Admin\AdminUserController::class, 'notifications']);
        Route::patch('notifications/{id}',   [\App\Http\Controllers\Admin\AdminUserController::class, 'updateNotification']);
    });

    // ─── Webhooks ─────────────────────────────────────────────────────────────
    Route::prefix('webhooks')->group(function () {
        Route::post('apify/linkedin', [\App\Http\Controllers\Api\V1\WebhookController::class, 'apifyLinkedIn'])
            ->name('webhooks.apify.linkedin');
    });

    // ─── WhatsApp Meta WABA Webhook ───────────────────────────────────────────
    // GET  — Challenge verification (Meta calls this once when you register webhook)
    // POST — Receive events: messages, delivery receipts, read receipts
    // NOTE: No auth middleware — Meta hits these directly without Bearer token
    Route::prefix('webhook/whatsapp')->group(function () {
        Route::get('/',  [WhatsappWebhookController::class, 'verify'])->name('webhook.whatsapp.verify');
        Route::post('/', [WhatsappWebhookController::class, 'handle'])->name('webhook.whatsapp.handle');
    });
});

