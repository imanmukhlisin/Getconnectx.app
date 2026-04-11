<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class OAuthController extends Controller
{
    private const ALLOWED_PROVIDERS = ['google', 'apple', 'linkedin'];

    // =========================================================================
    //  Redirect to OAuth Provider (Web Flow)
    // =========================================================================

    /**
     * GET /api/v1/auth/oauth/{provider}
     *
     * Redirect user ke halaman login provider OAuth (untuk web flow).
     */
    public function redirect(string $provider): JsonResponse|RedirectResponse
    {
        if (! $this->isProviderAllowed($provider)) {
            return $this->providerNotSupportedResponse($provider);
        }

        $driverName = $provider === 'linkedin' ? 'linkedin-openid' : $provider;
        return Socialite::driver($driverName)
            ->stateless()
            ->redirect();
    }

    // =========================================================================
    //  Handle OAuth Callback (Web Flow)
    // =========================================================================

    /**
     * GET /api/v1/auth/oauth/{provider}/callback
     *
     * Proses callback dari provider OAuth (web flow).
     * - Email otomatis terverifikasi (skip Step 2 & 3)
     * - User langsung lompat ke Step 4 (WhatsApp OTP)
     */
    public function callback(string $provider, Request $request): JsonResponse
    {
        if (! $this->isProviderAllowed($provider)) {
            return $this->providerNotSupportedResponse($provider);
        }

        // Handle OAuth errors from provider
        if ($request->has('error')) {
            return response()->json([
                'status'  => 'error',
                'message' => __('messages.oauth_login_cancelled'),
                'detail'  => $request->get('error_description'),
            ], 400);
        }

        try {
            $driverName = $provider === 'linkedin' ? 'linkedin-openid' : $provider;
            $oauthUser = Socialite::driver($driverName)->stateless()->user();
        } catch (Throwable $e) {
            Log::error("OAuth callback failed for {$provider}", ['error' => $e->getMessage()]);
            return response()->json([
                'status'  => 'error',
                'message' => __('messages.oauth_info_failed'),
            ], 422);
        }

        return $this->processOAuthUser($provider, $oauthUser, $request->fcm_token);
    }

    // =========================================================================
    //  Verify Native SDK Token (Mobile/Native Flow)
    // =========================================================================

    /**
     * POST /api/v1/auth/oauth/{provider}/verify-token
     *
     * Menerima token dari SDK Native (Google Sign-In, Apple Sign In,
     * LinkedIn SDK) dan memverifikasinya melalui Socialite.
     *
     * Request Body:
     *   - provider_token (string, required): Token/ID Token dari SDK native.
     *
     * Flow:
     *   1. Flutter/FE mendapatkan token dari SDK native provider.
     *   2. FE mengirim token tersebut ke endpoint ini via POST.
     *   3. BE memvalidasi token via Socialite::userFromToken().
     *   4. BE membuat/memperbarui akun user → skip email verification.
     *   5. BE mengembalikan registration token untuk lanjut ke verifikasi WA.
     */
    public function verifyToken(string $provider, Request $request): JsonResponse
    {
        if (! $this->isProviderAllowed($provider)) {
            return $this->providerNotSupportedResponse($provider);
        }

        // Validate request body
        $validator = Validator::make($request->all(), [
            'provider_token' => 'required|string',
        ], [
            'provider_token.required' => __('messages.val_provider_token_required'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $driverName = $provider === 'linkedin' ? 'linkedin-openid' : $provider;

            $oauthUser = Socialite::driver($driverName)
                ->stateless()
                ->userFromToken($request->provider_token);
        } catch (Throwable $e) {
            Log::error("OAuth native token verification failed for {$provider}", [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'status'  => 'error',
                'message' => __('messages.oauth_token_invalid', ['provider' => $provider]),
                'code'    => 'INVALID_PROVIDER_TOKEN',
            ], 401);
        }

        return $this->processOAuthUser($provider, $oauthUser, $request->fcm_token);
    }

    // =========================================================================
    //  Shared Logic: Process OAuth User
    // =========================================================================

    /**
     * Proses data user dari OAuth (digunakan oleh callback & verifyToken).
     *
     * - Jika user sudah ada (by oauth_id atau email) → update & link akun.
     * - Jika user baru → buat akun baru, email otomatis terverifikasi.
     * - Jika user sudah fully active → langsung kasih auth-token penuh.
     * - Jika user belum selesai registrasi → kasih registration-token sementara.
     */
    private function processOAuthUser(string $provider, $oauthUser, ?string $fcmToken = null): JsonResponse
    {
        try {
            $user = DB::transaction(function () use ($provider, $oauthUser, $fcmToken) {
                $email = $oauthUser->getEmail() ? strtolower($oauthUser->getEmail()) : null;

                // 1. Cari user berdasarkan OAuth ID terlebih dahulu
                $user = User::where('oauth_provider', $provider)
                            ->where('oauth_id', $oauthUser->getId())
                            ->first();

                // 2. Jika tidak ada by ID tapi email tersedia, coba cari by email
                if (! $user && $email) {
                    $user = User::where('email', $email)->first();
                }

                // 3. Jika benar-benar user baru tapi tidak ada email dari provider (ex: Apple Sign-In hide email)
                if (! $user && ! $email) {
                    throw new \InvalidArgumentException("Email not provided by {$provider}");
                }

                if ($user) {
                // User sudah ada → update/link OAuth info & fcm token jika ada
                $updateData = [
                    'oauth_provider'    => $provider,
                    'oauth_id'          => $oauthUser->getId(),
                    'oauth_token'       => $oauthUser->token,
                    'email_verified_at' => $user->email_verified_at ?? now(),
                    'registration_step' => max($user->registration_step, User::STEP_EMAIL_VERIFIED),
                ];
                
                if ($fcmToken) {
                    $updateData['fcm_token'] = $fcmToken;
                }
                
                $user->update($updateData);
                } else {
                    // New user via OAuth
                    $user = User::create([

                        'name'              => $oauthUser->getName() ?? 'User',
                        'email'             => $email,
                        'password'          => null,
                        'avatar_url'        => $oauthUser->getAvatar(),
                        'oauth_provider'    => $provider,
                        'oauth_id'          => $oauthUser->getId(),
                        'oauth_token'       => $oauthUser->token,
                        'fcm_token'         => $fcmToken,
                        'email_verified_at' => now(),
                        'registration_step' => User::STEP_EMAIL_VERIFIED,
                        'is_active'         => false,
                    ]);
                }

                return $user;
            });

            // Evaluasi Next Step dan terbitkan token API (Sanctum) di luar transaksi DB 
            // agar token creation tidak menghalangi write-lock DB.
            if ($user->is_active) {
                $fullToken = $user->createToken('auth-token', ['*'])->plainTextToken;

                return response()->json([
                    'status'     => 'success',
                    'message'    => __('messages.oauth_login_success_returning', ['provider' => $provider]),
                    'next_step'  => 'LOGIN_SUCCESS',
                    'token'      => $fullToken,
                    'token_type' => 'Bearer',
                    'data'       => [
                        'user'           => $user->registrationSummary(),
                        'oauth_provider' => $provider,
                    ],
                ]);
            }

        // User baru atau belum selesai registrasi → berikan registration token sementara
        $token = $user->createToken(
            'registration-token',
            ['registration'],
            now()->addMinutes((int) config('sanctum.registration_token_expiry', 60))
        )->plainTextToken;

        Log::info("OAuth registration via {$provider}", [
            'user_id' => $user->id,
            'email'   => $user->email,
            'step'    => $user->registration_step,
        ]);

            return response()->json([
                'status'     => 'success',
                'message'    => __('messages.oauth_login_success_new', ['provider' => $provider]),
                'next_step'  => 'NEED_WHATSAPP_VERIFICATION',
                'token'      => $token,
                'token_type' => 'Bearer',
                'data'       => [
                    'user'           => $user->registrationSummary(),
                    'oauth_provider' => $provider,
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Email wajib diberikan oleh provider OAuth (Privacy Settings).',
                'code'    => 'OAUTH_EMAIL_REQUIRED',
            ], 422);
        } catch (Throwable $e) {
            Log::error("OAuth internal error for {$provider}", ['error' => $e->getMessage()]);
            return response()->json([
                'status'  => 'error',
                'message' => __('messages.oauth_info_failed'),
            ], 500);
        }
    }

    // =========================================================================
    //  Helpers
    // =========================================================================

    private function isProviderAllowed(string $provider): bool
    {
        return in_array(strtolower($provider), self::ALLOWED_PROVIDERS, true);
    }

    private function providerNotSupportedResponse(string $provider): JsonResponse
    {
        return response()->json([
            'status'  => 'error',
            'message' => __('messages.oauth_provider_unsupported', ['provider' => $provider, 'allowed' => implode(', ', self::ALLOWED_PROVIDERS)]),
            'code'    => 'UNSUPPORTED_PROVIDER',
        ], 400);
    }
}
