<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Laravel\Socialite\Two\User as SocialiteUser;

/**
 * GoogleIdTokenVerifier
 *
 * Memverifikasi Firebase ID Token yang dikirim dari Flutter.
 *
 * Flow (Firebase Auth approach):
 *   1. Flutter → google_sign_in SDK → dapat Google credential
 *   2. Flutter → firebase_auth.signInWithCredential(googleCredential)
 *   3. Flutter → FirebaseAuth.instance.currentUser.getIdToken() → Firebase ID Token
 *   4. Flutter → POST /api/v1/auth/oauth/google/verify-token { provider_token: firebaseIdToken }
 *   5. Backend → GoogleIdTokenVerifier → verify via Firebase Admin SDK
 *   6. Backend → processOAuthUser() → create/update user → return Sanctum token
 *
 * Keuntungan pakai Firebase Auth:
 *   - Semua credential dari 1 Firebase project (connectx-50b64)
 *   - Tidak perlu manage Google OAuth Client ID terpisah di backend
 *   - Firebase Admin SDK handle semua validasi (signature, expiry, issuer)
 *   - Support Google, Apple, dan provider lain yang diaktifkan di Firebase Console
 */
class GoogleIdTokenVerifier
{
    public function __construct(
        private readonly FirebaseAuth $firebaseAuth,
    ) {}

    /**
     * Verifikasi Firebase ID Token dan kembalikan SocialiteUser-compatible object.
     *
     * @param  string  $idToken  Firebase ID Token dari Flutter
     * @return SocialiteUser      Socialite-compatible user object
     *
     * @throws \InvalidArgumentException  Jika token tidak valid
     */
    public function verify(string $idToken): SocialiteUser
    {
        try {
            // Firebase Admin SDK handles semua verification:
            // - JWT signature validation (via Google public keys)
            // - Issuer check (harus dari Firebase project kita)
            // - Audience check (harus match Firebase project ID)
            // - Expiry check
            $verifiedToken = $this->firebaseAuth->verifyIdToken($idToken);
        } catch (\Throwable $e) {
            Log::warning('Firebase ID Token verification failed', [
                'error' => $e->getMessage(),
            ]);
            throw new \InvalidArgumentException(
                'Firebase ID Token tidak valid: ' . $e->getMessage()
            );
        }

        // Extract claims dari verified token
        $claims = $verifiedToken->claims();

        $uid   = $claims->get('sub');       // Firebase UID
        $email = $claims->get('email');
        $name  = $claims->get('name');
        $picture = $claims->get('picture');
        $emailVerified = $claims->get('email_verified', false);

        // Pastikan email tersedia
        if (empty($email)) {
            throw new \InvalidArgumentException(
                'Email tidak tersedia dari Firebase token. Pastikan scope email diaktifkan.'
            );
        }

        // Pastikan email terverifikasi
        if (! $emailVerified) {
            throw new \InvalidArgumentException(
                'Email dari Google belum terverifikasi.'
            );
        }

        // Build SocialiteUser-compatible object
        $socialiteUser = new SocialiteUser();
        $socialiteUser->id       = $uid;
        $socialiteUser->name     = $name;
        $socialiteUser->email    = $email;
        $socialiteUser->avatar   = $picture;
        $socialiteUser->token    = $idToken;

        // Raw claims untuk debugging jika diperlukan
        $socialiteUser->user = [
            'sub'            => $uid,
            'email'          => $email,
            'name'           => $name,
            'picture'        => $picture,
            'email_verified' => $emailVerified,
            'firebase'       => $claims->get('firebase', []),
        ];

        Log::info('Firebase ID Token verified successfully', [
            'uid'      => $uid,
            'email'    => $email,
            'provider' => $claims->get('firebase', [])['sign_in_provider'] ?? 'unknown',
        ]);

        return $socialiteUser;
    }
}
