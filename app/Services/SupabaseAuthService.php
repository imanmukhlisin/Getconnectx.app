<?php

namespace App\Services;

use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SupabaseAuthService
{
    protected ?string $url;
    protected ?string $serviceRoleKey;
    protected ?string $jwtSecret;

    public function __construct()
    {
        $this->url = config('services.supabase.url');
        $this->serviceRoleKey = config('services.supabase.service_role_key');
        $this->jwtSecret = config('services.supabase.jwt_secret');
    }

    /**
     * Memastikan user terdaftar di tabel internal Supabase (auth.users).
     * Kita menggunakan Admin API Supabase (GoTrue Admin).
     */
    public function syncUserToSupabase(User $user): bool
    {
        if (empty($this->url) || empty($this->serviceRoleKey)) {
            Log::warning('Supabase URL atau Service Role Key belum dikonfigurasi.');
            return false;
        }

        // Endpoint Admin Supabase untuk membuat/mengelola user
        $adminUrl = rtrim($this->url, '/') . '/auth/v1/admin/users';

        try {
            // Kita coba bikin user di Supabase Auth.
            // Kita paksa ID-nya sama dengan UUID Laravel biar sinkron 100%.
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->serviceRoleKey,
                'apikey'        => $this->serviceRoleKey,
            ])->post($adminUrl, [
                'id'            => $user->id,
                'email'         => $user->email,
                'password'      => bin2hex(random_bytes(16)), // Password dummy karena login via Laravel OTP
                'email_confirm' => true,
                'user_metadata' => [
                    'full_name' => $user->name ?? '',
                ],
            ]);

            if ($response->successful()) {
                Log::info('User sukses disinkronkan ke Supabase Auth', ['user_id' => $user->id]);
                return true;
            }

            // Jika error karena user sudah ada, kita anggap sukses (karena targetnya adalah user eksis di sana)
            if ($response->status() === 422 && str_contains($response->body(), 'already exists')) {
                return true;
            }

            Log::error('Gagal sinkronisasi user ke Supabase', [
                'status' => $response->status(),
                'body'   => $response->body()
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error('Exception saat sinkronisasi Supabase Auth: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Membuat JWT (Token) yang kompatibel dengan Supabase SDK.
     * Token ini ditandatangani menggunakan SUPABASE_JWT_SECRET.
     */
    public function generateSupabaseToken(User $user): ?string
    {
        if (empty($this->jwtSecret)) {
            Log::error('SUPABASE_JWT_SECRET kosong! Tidak bisa membuat token.');
            return null;
        }

        $issuedAt = time();
        $expire = $issuedAt + 3600; // Token berlaku 1 jam (standar Supabase)

        $payload = [
            'aud'  => 'authenticated',
            'exp'  => $expire,
            'iat'  => $issuedAt,
            'sub'  => (string) $user->id, // UUID User
            'email' => $user->email,
            'phone' => (string) $user->whatsapp_number,
            'role'  => 'authenticated',
            'app_metadata' => [
                'provider'  => 'email',
                'providers' => ['email'],
            ],
            'user_metadata' => [
                'full_name' => $user->name ?? '',
            ],
        ];

        try {
            // Encode payload menjadi JWT string
            return JWT::encode($payload, $this->jwtSecret, 'HS256');
        } catch (\Exception $e) {
            Log::error('Gagal membuat Supabase JWT: ' . $e->getMessage());
            return null;
        }
    }
}
