<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service untuk berinteraksi dengan LinkedIn Official API menggunakan OAuth Access Token.
 *
 * Berbeda dari LinkedInScraperService (yang menggunakan Apify),
 * service ini langsung hit endpoint resmi LinkedIn API v2.
 */
class LinkedInApiService
{
    private const PROFILE_URL    = 'https://api.linkedin.com/v2/userinfo';
    private const POSITIONS_URL  = 'https://api.linkedin.com/v2/positions';
    private const EDUCATION_URL  = 'https://api.linkedin.com/v2/educations';

    // ═══════════════════════════════════════════════════════════════════
    //  Fetch Profile Dasar (nama, foto, headline, email)
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Ambil data profil dasar user dari LinkedIn API.
     * Endpoint: GET /v2/userinfo (OpenID Connect)
     *
     * @param string $accessToken Token OAuth dari frontend
     * @return array Data profil mentah (name, email, picture, headline, dll)
     * @throws \Exception Jika LinkedIn API gagal merespons
     */
    public function fetchProfile(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->timeout(15)
            ->get(self::PROFILE_URL);

        if (!$response->successful()) {
            Log::error('LinkedInApiService: Gagal fetch profil.', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \Exception("LinkedIn API error: {$response->status()}");
        }

        $data = $response->json();

        // Normalisasi ke format standar internal kita
        return [
            'full_name'  => trim(($data['given_name'] ?? '') . ' ' . ($data['family_name'] ?? '')),
            'email'      => $data['email'] ?? null,
            'avatar_url' => $data['picture'] ?? null,
            'headline'   => $data['headline'] ?? $data['sub'] ?? null, // 'sub' adalah LinkedIn ID
            'locale'     => $data['locale'] ?? null,
            'raw'        => $data, // Simpan raw untuk debugging
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Fetch Riwayat Pekerjaan (Experience)
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Ambil riwayat pekerjaan user dari LinkedIn API.
     * Batasi maksimal 3 pekerjaan terbaru.
     *
     * Catatan: Endpoint positions memerlukan scope `r_liteprofile` atau `r_fullprofile`.
     * Jika scope tidak tersedia, kembalikan array kosong [] — jangan null!
     *
     * @param string $accessToken Token OAuth dari frontend
     * @return array List pekerjaan, maks 3 item
     */
    public function fetchExperience(string $accessToken): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->timeout(15)
                ->get(self::POSITIONS_URL, [
                    'q'             => 'members',
                    'projection'    => '(elements*(title,companyName,timePeriod,isCurrent))',
                ]);

            // Jika scope tidak bisa atau API belum support, return kosong
            if (!$response->successful()) {
                Log::warning('LinkedInApiService: Tidak bisa fetch experience (scope mungkin kurang).', [
                    'status' => $response->status(),
                ]);
                return [];
            }

            $elements = $response->json('elements', []);

            // Normalisasi dan batasi 3 terbaru
            return collect($elements)
                ->take(3)
                ->map(fn($item) => [
                    'title'     => $item['title'] ?? null,
                    'company'   => $item['companyName'] ?? null,
                    'period'    => $this->formatPeriod($item['timePeriod'] ?? []),
                    'isCurrent' => $item['isCurrent'] ?? false,
                ])
                ->values()
                ->toArray();

        } catch (\Exception $e) {
            // Jika gagal sama sekali, kembalikan array kosong (bukan null!)
            Log::warning('LinkedInApiService: Exception saat fetch experience.', [
                'message' => $e->getMessage(),
            ]);
            return [];
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Fetch Riwayat Pendidikan (Education)
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Ambil riwayat pendidikan user dari LinkedIn API.
     * Jika tidak tersedia (scope kurang), kembalikan [] — jangan null!
     *
     * @param string $accessToken Token OAuth dari frontend
     * @return array List pendidikan
     */
    public function fetchEducation(string $accessToken): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->timeout(15)
                ->get(self::EDUCATION_URL, [
                    'q'          => 'members',
                    'projection' => '(elements*(schoolName,degreeName,timePeriod))',
                ]);

            if (!$response->successful()) {
                Log::warning('LinkedInApiService: Tidak bisa fetch education.', [
                    'status' => $response->status(),
                ]);
                return [];
            }

            $elements = $response->json('elements', []);

            return collect($elements)
                ->map(fn($item) => [
                    'degree' => $item['degreeName'] ?? null,
                    'school' => $item['schoolName'] ?? null,
                    'period' => $this->formatPeriod($item['timePeriod'] ?? []),
                ])
                ->values()
                ->toArray();

        } catch (\Exception $e) {
            Log::warning('LinkedInApiService: Exception saat fetch education.', [
                'message' => $e->getMessage(),
            ]);
            return [];
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Helper: Format Periode Waktu LinkedIn
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Format objek timePeriod dari LinkedIn menjadi string yang mudah dibaca.
     * Contoh output: "2021 – 2024" atau "2022 – Present"
     */
    private function formatPeriod(array $timePeriod): string
    {
        $startYear = $timePeriod['startDate']['year'] ?? null;
        $endYear   = $timePeriod['endDate']['year'] ?? null;

        if (!$startYear) return '';

        $end = $endYear ? (string) $endYear : 'Present';

        return "{$startYear} – {$end}";
    }
}
