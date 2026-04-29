<?php

namespace App\Services;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Two\User as SocialiteUser;

/**
 * GoogleIdTokenVerifier
 *
 * Memverifikasi Google ID Token (JWT) yang dikirim dari Flutter/mobile SDK.
 *
 * Flutter `google_sign_in` mengembalikan ID Token (JWT) — bukan Access Token.
 * Socialite::userFromToken() mengharapkan Access Token dan akan gagal untuk
 * mobile Google Sign-In. Service ini mem-bypass Socialite dan memverifikasi
 * JWT secara langsung menggunakan Google JWKS public keys.
 *
 * Flow:
 *   1. Flutter → google_sign_in SDK → mendapatkan `idToken`
 *   2. Flutter → POST /api/v1/auth/oauth/google/verify-token { provider_token: idToken }
 *   3. Backend → GoogleIdTokenVerifier → decode & verify JWT
 *   4. Backend → processOAuthUser() → create/update user → return token
 */
class GoogleIdTokenVerifier
{
    /**
     * Google's public key endpoint (JWKS format).
     */
    private const GOOGLE_JWKS_URL = 'https://www.googleapis.com/oauth2/v3/certs';

    /**
     * Allowed issuers for Google ID Tokens.
     */
    private const ALLOWED_ISSUERS = [
        'https://accounts.google.com',
        'accounts.google.com',
    ];

    /**
     * Cache key for Google JWKS public keys.
     */
    private const JWKS_CACHE_KEY = 'google_jwks_keys';

    /**
     * Cache duration for JWKS keys (in seconds).
     * Google rotates keys roughly every ~6 hours. We cache for 1 hour.
     */
    private const JWKS_CACHE_TTL = 3600;

    /**
     * Verifikasi Google ID Token dan kembalikan SocialiteUser-compatible object.
     *
     * @param  string  $idToken  JWT ID Token dari Flutter google_sign_in SDK
     * @return SocialiteUser      Socialite-compatible user object
     *
     * @throws \InvalidArgumentException  Jika token tidak valid
     * @throws \RuntimeException          Jika gagal fetch Google public keys
     */
    public function verify(string $idToken): SocialiteUser
    {
        // 1. Ambil Google JWKS public keys (di-cache)
        $keys = $this->getGooglePublicKeys();

        // 2. Decode & verifikasi JWT signature
        try {
            $payload = JWT::decode($idToken, JWK::parseKeySet($keys));
        } catch (\Exception $e) {
            Log::warning('Google ID Token JWT decode failed', [
                'error' => $e->getMessage(),
            ]);
            throw new \InvalidArgumentException(
                'Google ID Token tidak valid: ' . $e->getMessage()
            );
        }

        // 3. Verifikasi issuer
        if (! isset($payload->iss) || ! in_array($payload->iss, self::ALLOWED_ISSUERS, true)) {
            throw new \InvalidArgumentException(
                'Google ID Token issuer tidak valid: ' . ($payload->iss ?? 'missing')
            );
        }

        // 4. Verifikasi audience (harus cocok dengan salah satu client ID kita)
        $allowedAudiences = array_filter([
            config('services.google.client_id'),          // Web Client ID
            config('services.google.ios_client_id'),      // iOS Client ID
            config('services.google.android_client_id'),  // Android Client ID (future)
        ]);

        if (! isset($payload->aud) || ! in_array($payload->aud, $allowedAudiences, true)) {
            Log::warning('Google ID Token audience mismatch', [
                'token_aud'       => $payload->aud ?? 'missing',
                'allowed'         => $allowedAudiences,
            ]);
            throw new \InvalidArgumentException(
                'Google ID Token audience tidak cocok dengan client ID yang terdaftar.'
            );
        }

        // 5. Verifikasi expiry
        if (! isset($payload->exp) || $payload->exp < time()) {
            throw new \InvalidArgumentException('Google ID Token sudah kedaluwarsa.');
        }

        // 6. Verifikasi email terverifikasi
        if (isset($payload->email_verified) && ! $payload->email_verified) {
            throw new \InvalidArgumentException(
                'Email dari Google belum terverifikasi.'
            );
        }

        // 7. Build SocialiteUser-compatible object
        $socialiteUser = new SocialiteUser();
        $socialiteUser->id       = $payload->sub;
        $socialiteUser->name     = $payload->name ?? null;
        $socialiteUser->email    = $payload->email ?? null;
        $socialiteUser->avatar   = $payload->picture ?? null;
        $socialiteUser->token    = $idToken; // store original token as reference

        // Tambahkan raw payload agar accessible jika diperlukan
        $socialiteUser->user = (array) $payload;

        Log::info('Google ID Token verified successfully', [
            'sub'   => $payload->sub,
            'email' => $payload->email ?? 'N/A',
        ]);

        return $socialiteUser;
    }

    /**
     * Fetch dan cache Google JWKS public keys.
     *
     * @return array  JWKS key set
     *
     * @throws \RuntimeException  Jika gagal fetch keys dari Google
     */
    private function getGooglePublicKeys(): array
    {
        return Cache::remember(self::JWKS_CACHE_KEY, self::JWKS_CACHE_TTL, function () {
            $response = Http::timeout(10)->get(self::GOOGLE_JWKS_URL);

            if (! $response->successful()) {
                Log::error('Failed to fetch Google JWKS keys', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                throw new \RuntimeException(
                    'Gagal mengambil Google public keys. Status: ' . $response->status()
                );
            }

            return $response->json();
        });
    }
}
