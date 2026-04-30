<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LinkedInScraperService
{
    protected string $token;
    protected string $actorId = 'dev_fusion~linkedin-profile-scraper';

    public function __construct()
    {
        // Token will be config('services.apify.token') but fallback if not set.
        $this->token = config('services.apify.token', env('APIFY_TOKEN'));
    }

    /**
     * Jalankan scraper secara Asynchronous ke API Apify dan sisipkan Webhook URL.
     * Metode ini 100% aman untuk Vercel (Serverless) karena tidak memblokir respon PHP (non-blocking).
     *
     * @param User $user
     * @param string $linkedinUrl
     * @return bool
     */
    public function triggerScrapeAsync(User $user, string $linkedinUrl): bool
    {
        if (empty($this->token)) {
            Log::error('LinkedInScraperService: APIFY_TOKEN is missing.');
            return false;
        }

        if (!str_contains($linkedinUrl, 'linkedin.com/in/')) {
            Log::warning('LinkedInScraperService: Invalid LinkedIn URL provided: ' . $linkedinUrl);
            return false;
        }

        try {
            // Secret token untuk memvalidasi request masuk dari Apify
            $secretToken = config('services.apify.webhook_token', md5($this->token . 'webhook'));
            
            // Tambahkan user_id dan secret_token ke query parameter agar webhook tahu data milik siapa
            $webhookUrl = config('app.url') . "/api/v1/webhooks/apify/linkedin?user_id={$user->id}&token={$secretToken}";
            
            // Konfigurasi Webhook spesifik dari APIFY (via query parameter webhooks base64 encoded)
            $webhooksJson = json_encode([
                [
                    'eventTypes' => ['ACTOR.RUN.SUCCEEDED'],
                    'requestUrl' => $webhookUrl
                ]
            ]);
            $webhooksBase64 = base64_encode($webhooksJson);

            // Kita hit endpoint Asynchronous: /runs (BUKAN run-sync) ditambah webhook
            $endpoint = "https://api.apify.com/v2/acts/{$this->actorId}/runs?token={$this->token}&webhooks={$webhooksBase64}";

            // Payload sesuai dokumentasi dev_fusion~linkedin-profile-scraper
            $response = Http::timeout(2)->post($endpoint, [
                'profileUrls' => [$linkedinUrl],
            ]);

            if ($response->successful()) {
                Log::info("LinkedInScraperService: Triggered Apify Async Run for $linkedinUrl");
                return true;
            }

            Log::error('LinkedInScraperService: Failed to trigger scrape.', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

        } catch (\Exception $e) {
            Log::error('LinkedInScraperService: Exception occurred.', [
                'message' => $e->getMessage()
            ]);
        }

        return false;
    }

    /**
     * Memproses hasil raw dari scraper dan memperbarui profil pengguna (avatar, bio, position, dll).
     */
    public function syncToUserProfile(User $user, array $scrapedData): void
    {
        $updateData = [];

        // Save raw data to json column
        $updateData['linkedin_data'] = $scrapedData;

        // Try mapping commonly scraped fields from LinkedIn
        // Sesuaikan keys di bawah ini dengan struktur response yang dikembalikan oleh Apify Actor dev_fusion!
        if (!empty($scrapedData['profilePictureUrl'])) {
            $updateData['avatar_url'] = $scrapedData['profilePictureUrl'];
        } elseif (!empty($scrapedData['avatar'])) {
            $updateData['avatar_url'] = $scrapedData['avatar'];
        }

        if (!empty($scrapedData['headline'])) {
            // Jika user belum mengisi jabatan di onboarding, kita isi dari LinkedIn
            if (empty($user->position)) {
                $updateData['position'] = $scrapedData['headline'];
            }
        }

        // Kalau misalnya ingin meniban atau cross-check nama asli
        if (!empty($scrapedData['firstName']) && empty($user->name)) {
            $name = trim($scrapedData['firstName'] . ' ' . ($scrapedData['lastName'] ?? ''));
            $updateData['name'] = $name;
        }

        if (!empty($updateData)) {
            $user->update($updateData);
        }
    }
}
