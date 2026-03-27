<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Exceptions\WhatsAppDeliveryException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class OAuthController extends Controller
{
    private const ALLOWED_PROVIDERS = ['google', 'apple', 'linkedin'];

    public function __construct(
        private readonly WhatsAppService $whatsAppService,
    ) {}

    // ─── Redirect to OAuth Provider ───────────────────────────────────────────

    /**
     * GET /api/v1/auth/oauth/{provider}
     *
     * Redirect user ke halaman login provider OAuth.
     */
    public function redirect(string $provider): JsonResponse|RedirectResponse
    {
        if (! $this->isProviderAllowed($provider)) {
            return response()->json([
                'status'  => 'error',
                'message' => "Provider '{$provider}' tidak didukung. Gunakan: " . implode(', ', self::ALLOWED_PROVIDERS),
            ], 400);
        }

        return Socialite::driver($provider)->stateless()->redirect();
    }

    // ─── Handle OAuth Callback ────────────────────────────────────────────────

    /**
     * GET /api/v1/auth/oauth/{provider}/callback
     *
     * Proses callback dari provider OAuth.
     * - Email otomatis terverifikasi (skip Step 2 & 3)
     * - User langsung lompat ke Step 4 (WhatsApp OTP)
     */
    public function callback(string $provider, Request $request): JsonResponse
    {
        if (! $this->isProviderAllowed($provider)) {
            return response()->json([
                'status'  => 'error',
                'message' => "Provider '{$provider}' tidak didukung.",
            ], 400);
        }

        // Handle OAuth errors
        if ($request->has('error')) {
            return response()->json([
                'status'  => 'error',
                'message' => 'OAuth login dibatalkan atau terjadi kesalahan.',
                'detail'  => $request->get('error_description'),
            ], 400);
        }

        try {
            $oauthUser = Socialite::driver($provider)->stateless()->user();
        } catch (Throwable $e) {
            Log::error("OAuth callback failed for {$provider}", ['error' => $e->getMessage()]);
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mendapatkan informasi dari provider OAuth.',
            ], 422);
        }

        $user = DB::transaction(function () use ($provider, $oauthUser) {
            // Try to find existing user by OAuth ID or email
            $user = User::where('oauth_provider', $provider)
                        ->where('oauth_id', $oauthUser->getId())
                        ->first()
                ?? User::where('email', strtolower($oauthUser->getEmail()))->first();

            if ($user) {
                // Update OAuth info if linking
                $user->update([
                    'oauth_provider'   => $provider,
                    'oauth_id'         => $oauthUser->getId(),
                    'oauth_token'      => $oauthUser->token,
                    'email_verified_at' => $user->email_verified_at ?? now(),
                    'registration_step' => max($user->registration_step, User::STEP_EMAIL_VERIFIED),
                ]);
            } else {
                // New user via OAuth – entity_type defaults to 'talent'
                // User can change this later in the onboarding flow
                $user = User::create([
                    'entity_type'      => 'talent',
                    'name'             => $oauthUser->getName(),
                    'email'            => strtolower($oauthUser->getEmail()),
                    'password'         => null, // OAuth user, no password
                    'avatar_url'       => $oauthUser->getAvatar(),
                    'oauth_provider'   => $provider,
                    'oauth_id'         => $oauthUser->getId(),
                    'oauth_token'      => $oauthUser->token,
                    'email_verified_at' => now(), // Auto-verified
                    'registration_step' => User::STEP_EMAIL_VERIFIED,
                    'is_active'        => false,
                ]);
            }

            return $user;
        });

        // Issue temporary registration token scoped to registration
        $token = $user->createToken(
            'registration-token',
            ['registration'],
            now()->addMinutes((int) config('sanctum.registration_token_expiry', 60))
        )->plainTextToken;

        return response()->json([
            'status'    => 'success',
            'message'   => "Login via {$provider} berhasil. Silakan lengkapi verifikasi WhatsApp.",
            'next_step' => 'NEED_WHATSAPP_VERIFICATION',
            'token'     => $token,
            'token_type' => 'Bearer',
            'data'      => [
                'user'           => $user->registrationSummary(),
                'oauth_provider' => $provider,
            ],
        ]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function isProviderAllowed(string $provider): bool
    {
        return in_array(strtolower($provider), self::ALLOWED_PROVIDERS, true);
    }
}
