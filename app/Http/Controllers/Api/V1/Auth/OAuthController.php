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

    public function __construct(
        private readonly \App\Services\SupabaseAuthService $supabaseAuth
    ) {}

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
        /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
        $driver = Socialite::driver($driverName);
        
        return $driver->stateless()->redirect();
    }

    // =========================================================================
    //  Handle OAuth Callback (Web Flow)
    // =========================================================================

    /**
     * GET /api/v1/auth/oauth/{provider}/callback
     *
     * Proses callback dari provider OAuth (web flow).
     * Jika request berasal dari mobile bridge (terutama LinkedIn),
     * kita akan lempar redirect 'connectx://...' ke app beserta token-nya.
     */
    public function callback(string $provider, Request $request): JsonResponse|RedirectResponse
    {
        if (! $this->isProviderAllowed($provider)) {
            return $this->providerNotSupportedResponse($provider);
        }

        // URL fallback untuk mobile jika terjadi error
        $appCallbackUrl = env(strtoupper($provider) . '_APP_CALLBACK_URL', 'connectx://auth/callback');

        // Handle OAuth errors from provider
        if ($request->has('error')) {
            return redirect()->away($appCallbackUrl . '?error=oauth_cancelled&message=' . urlencode($request->get('error_description') ?? 'Login cancelled'));
        }

        try {
            $driverName = $provider === 'linkedin' ? 'linkedin-openid' : $provider;
            
            /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
            $driver = Socialite::driver($driverName);
            $oauthUser = $driver->stateless()->user();
        } catch (Throwable $e) {
            Log::error("OAuth callback failed for {$provider}", ['error' => $e->getMessage()]);
            return redirect()->away($appCallbackUrl . '?error=oauth_failed&message=' . urlencode('Gagal mendapatkan data dari ' . $provider));
        }

        $jsonResponse = $this->processOAuthUser($provider, $oauthUser, $request->fcm_token);
        
        // Ekstrak token dari hasil JSON untuk diumpankan ke mobile
        $data = $jsonResponse->getData();
        if (isset($data->status) && $data->status === 'success' && isset($data->token)) {
            // Rakit URL Deep Link
            $redirectUrl = $appCallbackUrl . '?token=' . urlencode($data->token) . '&next_step=' . urlencode($data->next_step);
            
            // Sertakan supabase_token jika ada (user sudah aktif/full login)
            if (isset($data->supabase_token)) {
                $redirectUrl .= '&supabase_token=' . urlencode($data->supabase_token);
            }

            // Redirect ke Mobile App via Custom Scheme (Deep Link)
            return redirect()->away($redirectUrl);
        }

        // Fallback jika proses pembuatan user gagal (harapannya tidak pernah terjadi)
        return redirect()->away($appCallbackUrl . '?error=server_error&message=' . urlencode('Gagal memproses user'));
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

            /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
            $driver = Socialite::driver($driverName);
            $oauthUser = $driver->stateless()->userFromToken($request->provider_token);
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
                // Sinkronisasi ke Supabase
                $this->supabaseAuth->syncUserToSupabase($user);
                
                $fullToken = $user->createToken('auth-token', ['*'])->plainTextToken;
                $supabaseToken = $this->supabaseAuth->generateSupabaseToken($user);

                return response()->json([
                    'status'     => 'success',
                    'message'    => __('messages.oauth_login_success_returning', ['provider' => $provider]),
                    'next_step'  => 'LOGIN_SUCCESS',
                    'token'      => $fullToken,
                    'supabase_token' => $supabaseToken,
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
