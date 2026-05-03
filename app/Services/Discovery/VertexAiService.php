<?php

namespace App\Services\Discovery;

use App\Models\User;
use Google\Auth\ApplicationDefaultCredentials;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class VertexAiService
{
    private const CACHE_TTL = 3600 * 24; // Cache AI insights for 24 hours to save API cost
    private const CACHE_PREFIX = 'connectx:discovery:ai_insight:';

    /**
     * Generate an insight string based on the user's profile and discovery filters.
     * Uses Gemini 1.5 Pro via Google Cloud Vertex AI.
     */
    public function generateInsight(User $user, array $filters, string $mode): string
    {
        // 1. Build a unique cache key based on user ID, mode, and filter hash
        $filterHash = md5(json_encode($filters));
        $cacheKey   = self::CACHE_PREFIX . "{$user->id}:{$mode}:{$filterHash}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user, $filters, $mode) {
            try {
                $prompt = $this->buildPrompt($user, $filters, $mode);
                return $this->callVertexAi($prompt);
            } catch (\Throwable $e) {
                Log::error('Vertex AI Error: ' . $e->getMessage());
                // Fallback static text if AI fails
                return "Our matchmaking algorithm is analyzing profiles to find your perfect {$this->getModeLabel($mode)}.";
            }
        });
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Generate Biografi dari Data LinkedIn
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Generate biografi profesional 3-4 kalimat dari data LinkedIn user.
     * Digunakan oleh ProcessLinkedInProfileJob setelah sync data dari LinkedIn API.
     *
     * Fallback jika AI gagal: return headline saja sebagai bio.
     *
     * @param string $headline Jabatan/headline dari LinkedIn
     * @param array  $experiences Riwayat pekerjaan (maks 3 item)
     * @return string Biografi profesional siap simpan ke kolom bio
     */
    public function generateLinkedInBio(string $headline, array $experiences): string
    {
        try {
            // Susun ringkasan experience untuk dimasukkan ke prompt
            $experienceSummary = collect($experiences)
                ->map(fn($e) => "- {$e['title']} at {$e['company']} ({$e['period']})")
                ->implode("\n");

            if (empty($experienceSummary)) {
                $experienceSummary = '(No work experience provided)';
            }

            $prompt = <<<PROMPT
You are ConnectX AI, a professional writing assistant for a startup networking platform.
Generate a compelling, professional bio in 3-4 sentences based on the following professional profile.
The tone should be first-person, confident, and concise. Do NOT use bullet points or headers.

Profile:
- Headline: {$headline}
- Work Experience:
{$experienceSummary}

Write the bio directly, without any introduction or explanation.
PROMPT;

            return $this->callVertexAi($prompt);

        } catch (\Throwable $e) {
            Log::error('VertexAiService: Gagal generate LinkedIn bio.', [
                'message' => $e->getMessage(),
            ]);

            // Fallback: gunakan headline saja agar bio tidak kosong
            return $headline;
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Scrape & Parse LinkedIn Profile via Gemini AI
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Extract structured professional data from a LinkedIn public profile.
     *
     * Strategy — tries sources in order, stops at first success:
     *   1. Direct fetch with LinkedIn-compatible User-Agent headers
     *   2. Google Cache of the LinkedIn page
     *   3. Mobile LinkedIn endpoint (often less aggressively blocked)
     *
     * The resulting plain text is then parsed by Gemini 1.5 Pro (no grounding
     * required — uses the same credential infrastructure as Discovery insights).
     *
     * @param  string $linkedinUrl  Public LinkedIn profile URL.
     * @return array|null           Structured profile data, or null if unavailable.
     */
    public function scrapeAndParseLinkedIn(string $linkedinUrl): ?array
    {
        Log::info('VertexAiService@scrapeAndParseLinkedIn: Extracting profile via Gemini Google Search Grounding.', [
            'url' => $linkedinUrl,
        ]);

        try {
            $prompt  = $this->buildLinkedInExtractionPrompt($linkedinUrl);
            $rawJson = $this->callGeminiForExtraction($prompt);

            // Strip markdown code fences Gemini may add
            $rawJson = preg_replace('/^```(?:json)?\s*/i', '', trim($rawJson));
            $rawJson = preg_replace('/\s*```$/', '', $rawJson);

            $parsed = json_decode($rawJson, true);

            if (!is_array($parsed) || empty($parsed['headline'])) {
                Log::warning('VertexAiService@scrapeAndParseLinkedIn: Gemini returned invalid or empty JSON.', [
                    'url'      => $linkedinUrl,
                    'raw_json' => substr($rawJson, 0, 500),
                ]);
                return null;
            }

            return [
                'name'        => trim($parsed['name'] ?? ''),
                'photo_url'   => $parsed['photo_url'] ?? null,
                'headline'    => trim($parsed['headline'] ?? ''),
                'experiences' => array_slice(array_map(function ($exp) {
                    return [
                        'title'   => $exp['title']   ?? null,
                        'company' => $exp['company']  ?? null,
                        'period'  => $exp['period']   ?? '',
                    ];
                }, $parsed['experiences'] ?? []), 0, 3),
                'educations'  => array_map(function ($edu) {
                    return [
                        'degree' => $edu['degree'] ?? null,
                        'school' => $edu['school'] ?? null,
                        'period' => $edu['period'] ?? '',
                    ];
                }, $parsed['educations'] ?? []),
                'bio_summary' => trim($parsed['bio_summary'] ?? ''),
            ];

        } catch (\Throwable $e) {
            Log::error('VertexAiService@scrapeAndParseLinkedIn: Gemini extraction failed.', [
                'url'   => $linkedinUrl,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }



    /**
     * Build the extraction prompt for Gemini — Google Search Grounding version.
     */
    private function buildLinkedInExtractionPrompt(string $linkedinUrl): string
    {
        return <<<PROMPT
You are a professional data extraction assistant.
I need you to extract structured professional information for the person at this LinkedIn profile:
Profile URL: {$linkedinUrl}

Use the Google Search tool to find information about this person's professional background, current role, past experiences, and education based on their public LinkedIn footprint or other professional directories.

STRICT OUTPUT RULES:
- Output ONLY the raw JSON object. No markdown, no code fences, no explanation.
- If a field cannot be found, use null (for strings) or [] (for arrays).
- For "experiences", include a maximum of 3 most recent roles.
- For "photo_url", always return null — image URLs are not parseable from text.
- For "bio_summary", write a compelling 3-4 sentence FIRST-PERSON professional summary
  highlighting the person's potential as a Startup Founder or Partner. Do NOT copy
  the person's own About section verbatim.

Required JSON structure (do not add or remove any keys):
{
  "name": "Full Name",
  "photo_url": null,
  "headline": "Current professional headline",
  "experiences": [
    { "title": "Job Title", "company": "Company Name", "period": "YYYY - YYYY or Present" }
  ],
  "educations": [
    { "degree": "Degree Name", "school": "School Name", "period": "YYYY - YYYY" }
  ],
  "bio_summary": "3-4 sentence first-person professional bio."
}
}
PROMPT;
    }


    /**
     * Build the contextual prompt for Gemini 1.5 Pro.
     */
    private function buildPrompt(User $user, array $filters, string $mode): string
    {
        $role = $user->position ?? 'Professional';
        $industry = $user->industry ?? 'Tech';
        
        $modeLabel = $this->getModeLabel($mode);
        $filterSummary = json_encode($filters); // Can be formatted prettier if needed

        return <<<PROMPT
You are ConnectX AI, an expert matchmaking assistant for a platform connecting startup founders and talents.
Your goal is to generate a short, engaging, and professional 1-2 sentence insight about a user's current discovery search.

Context:
- User's Role: {$role}
- User's Industry: {$industry}
- What they are doing: {$modeLabel}
- Their current search filters (JSON): {$filterSummary}

Generate a personalized, encouraging insight explaining why the platform is uniquely positioned to find them great matches based on these precise filters. 
Keep it under 30 words. Do not use hashtags or emojis. 
Be direct, professional, and slightly enthusiastic.
PROMPT;
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Shared Infrastructure
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Build an authenticated Guzzle client pointed at Vertex AI.
     *
     * Credential resolution order:
     *   1. GOOGLE_CLOUD_CREDENTIALS_JSON env var (JSON string — Vercel way)
     *   2. storage/service-account.json file (local dev way)
     *
     * @param  float $timeout Request timeout in seconds.
     * @return Client
     */
    private function buildVertexAiClient(float $timeout = 10.0): Client
    {
        // Support both variable names — .env.example uses GOOGLE_CLOUD_KEY_JSON,
        // legacy code used GOOGLE_CLOUD_CREDENTIALS_JSON. Accept either.
        $envCreds = env('GOOGLE_CLOUD_CREDENTIALS_JSON') ?: env('GOOGLE_CLOUD_KEY_JSON');

        if ($envCreds) {
            $credentialPath = '/tmp/google-creds.json';
            file_put_contents($credentialPath, $envCreds);
        } else {
            $credentialPath = base_path('storage/service-account.json');
        }

        if (!file_exists($credentialPath)) {
            throw new \Exception(
                'Vertex AI Credentials not found. '
                . 'Set GOOGLE_CLOUD_CREDENTIALS_JSON or GOOGLE_CLOUD_KEY_JSON in Vercel env, '
                . 'or provide storage/service-account.json for local dev.'
            );
        }

        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $credentialPath);

        $location = env('VERTEX_LOCATION', 'us-central1');
        $scopes   = ['https://www.googleapis.com/auth/cloud-platform'];

        $middleware = ApplicationDefaultCredentials::getMiddleware($scopes);
        $stack      = HandlerStack::create();
        $stack->push($middleware);

        return new Client([
            'handler'  => $stack,
            'base_uri' => "https://{$location}-aiplatform.googleapis.com/",
            'auth'     => 'google_auth',
            'timeout'  => $timeout,
        ]);
    }

    private function getModeLabel(string $mode): string
    {
        return match ($mode) {
            'finding_cofounder' => 'searching for a Co-Founder',
            'building_team'     => 'building their startup team',
            'explore_startups'  => 'exploring startup opportunities',
            'joining_startups'  => 'looking to join an early-stage startup',
            default             => 'networking',
        };
    }

    /**
     * Call the Google Cloud Vertex AI REST API Using Guzzle & google/auth.
     * Used by: generateInsight() and generateLinkedInBio().
     */
    private function callVertexAi(string $prompt): string
    {
        $client    = $this->buildVertexAiClient(10.0);
        $projectId = env('GOOGLE_CLOUD_PROJECT', 'connectx-app-482206');
        $location  = env('VERTEX_LOCATION', 'us-central1');
        $endpoint  = "v1/projects/{$projectId}/locations/{$location}/publishers/google/models/gemini-1.5-pro:generateContent";

        $response = $client->post($endpoint, [
            'json' => [
                'contents' => [
                    [
                        'role'  => 'user',
                        'parts' => [['text' => $prompt]],
                    ],
                ],
                'generationConfig' => [
                    'temperature'     => 0.7,
                    'maxOutputTokens' => 150,
                ],
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'AI insight unavailable.';
    }


    /**
     * Call Gemini for LinkedIn profile data extraction.
     *
     * Primary: Google Gemini API (generativelanguage.googleapis.com)
     *   Uses a simple GEMINI_API_KEY env var — no service account, no Vertex AI
     *   permissions required. Get a free key at https://ai.google.dev
     *
     * Fallback: Vertex AI endpoint (same as Discovery insights).
     *   Only used if GEMINI_API_KEY is not set.
     */
    private function callGeminiForExtraction(string $prompt): string
    {
        $apiKey = env('GEMINI_API_KEY');

        if ($apiKey) {
            return $this->callGeminiApiDirect($prompt, $apiKey);
        }

        // Fallback: Vertex AI (requires aiplatform.user role on service account)
        Log::warning('VertexAiService@callGeminiForExtraction: GEMINI_API_KEY not set, falling back to Vertex AI.');
        return $this->callGeminiViaVertexAi($prompt);
    }

    /**
     * Call the public Google Gemini API with an API key.
     * Simple, reliable, no service account or Vertex AI setup required.
     * Free tier at https://ai.google.dev supports up to 1500 requests/day.
     */
    private function callGeminiApiDirect(string $prompt, string $apiKey): string
    {
        $model    = 'gemini-2.5-flash'; // Confirmed to work with googleSearch grounding on user's API key
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->timeout(45)->post($endpoint, [
            'contents' => [
                [
                    'role'  => 'user',
                    'parts' => [['text' => $prompt]],
                ],
            ],
            'tools' => [
                [
                    'googleSearch' => new \stdClass()
                ]
            ],
            'generationConfig' => [
                'temperature'     => 0.1,
                'maxOutputTokens' => 2048,
            ],
        ]);

        if (!$response->successful()) {
            throw new \Exception('Gemini API error: ' . $response->status() . ' ' . $response->body());
        }

        $data = $response->json();

        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            \Illuminate\Support\Facades\Log::error('Gemini returned unexpected structure', ['data' => $data]);
        }

        return $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
    }

    /**
     * Call Gemini via Vertex AI endpoint.
     * Requires the service account to have roles/aiplatform.user.
     */
    private function callGeminiViaVertexAi(string $prompt): string
    {
        $client    = $this->buildVertexAiClient(30.0);
        $projectId = env('GOOGLE_CLOUD_PROJECT', 'connectx-app-482206');
        $location  = env('VERTEX_LOCATION', 'us-central1');
        $endpoint  = "v1/projects/{$projectId}/locations/{$location}/publishers/google/models/gemini-1.5-pro:generateContent";

        $response = $client->post($endpoint, [
            'json' => [
                'contents' => [
                    [
                        'role'  => 'user',
                        'parts' => [['text' => $prompt]],
                    ],
                ],
                'generationConfig' => [
                    'temperature'     => 0.1,
                    'maxOutputTokens' => 2048,
                ],
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        return $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
    }
}

