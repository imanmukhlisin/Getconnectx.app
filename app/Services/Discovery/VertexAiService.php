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
     * Fetch a public LinkedIn profile page and use Gemini to extract
     * structured professional data from it.
     *
     * Returns a normalized array ready to be persisted to the database,
     * or null if the page could not be fetched or parsed.
     *
     * @param  string $linkedinUrl The public LinkedIn profile URL (e.g. https://www.linkedin.com/in/username)
     * @return array|null
     */
    public function scrapeAndParseLinkedIn(string $linkedinUrl): ?array
    {
        // ── 1. Fetch the public profile HTML ─────────────────────────
        try {
            $httpResponse = \Illuminate\Support\Facades\Http::withHeaders([
                    // Impersonate a real browser so LinkedIn returns the public page
                    'User-Agent'      => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
                    'Accept-Language' => 'en-US,en;q=0.9',
                    'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ])
                ->timeout(15)
                ->get($linkedinUrl);

            if (!$httpResponse->successful()) {
                Log::warning('VertexAiService@scrapeAndParseLinkedIn: LinkedIn returned non-200.', [
                    'url'    => $linkedinUrl,
                    'status' => $httpResponse->status(),
                ]);
                return null;
            }

            $html = $httpResponse->body();
        } catch (\Throwable $e) {
            Log::warning('VertexAiService@scrapeAndParseLinkedIn: HTTP fetch failed.', [
                'url'   => $linkedinUrl,
                'error' => $e->getMessage(),
            ]);
            return null;
        }

        // ── 2. Strip HTML to readable plain text ──────────────────────
        // Remove script/style blocks entirely before stripping tags
        $plainText = preg_replace('/<(script|style)[^>]*>.*?<\/\1>/si', '', $html);
        $plainText = strip_tags($plainText);
        // Collapse excess whitespace so we don't waste tokens
        $plainText = preg_replace('/\s+/', ' ', $plainText);
        $plainText = trim($plainText);
        // Limit to first 6,000 characters — enough to capture the visible profile
        $plainText = mb_substr($plainText, 0, 6000);

        // ── 3. Ask Gemini to extract structured data ──────────────────
        try {
            $prompt = $this->buildLinkedInExtractionPrompt($plainText, $linkedinUrl);
            $rawJson = $this->callVertexAiWithHighTokens($prompt);

            // Gemini may wrap the JSON in a markdown code block — strip it
            $rawJson = preg_replace('/^```(?:json)?\s*/i', '', trim($rawJson));
            $rawJson = preg_replace('/\s*```$/', '', $rawJson);

            $parsed = json_decode($rawJson, true);

            if (!is_array($parsed) || empty($parsed['headline'])) {
                Log::warning('VertexAiService@scrapeAndParseLinkedIn: Gemini returned invalid or empty JSON.', [
                    'url'      => $linkedinUrl,
                    'raw_json' => $rawJson,
                ]);
                return null;
            }

            // Ensure required keys exist with safe defaults
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
            Log::error('VertexAiService@scrapeAndParseLinkedIn: Gemini parsing failed.', [
                'url'   => $linkedinUrl,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Build the JSON-extraction prompt sent to Gemini for LinkedIn parsing.
     */
    private function buildLinkedInExtractionPrompt(string $profileText, string $linkedinUrl): string
    {
        return <<<PROMPT
You are a professional data extraction assistant. You will receive raw text scraped from a LinkedIn public profile page.
Your ONLY task is to extract the professional data and return it as a single, valid JSON object.

STRICT RULES:
- Output ONLY the raw JSON object. No markdown, no code fences, no explanation.
- If a field cannot be found, use null (for strings) or [] (for arrays).
- For "experiences", return a maximum of 3 most recent entries.
- For "photo_url", extract the full URL of the profile picture if visible in the text.
- For "bio_summary", write a compelling 3–4 sentence first-person professional summary highlighting the person's potential as a Startup Founder or Partner, based on their experience and headline. Do NOT copy the person's own "About" text verbatim.

Required JSON structure (do not add or remove keys):
{
  "name": "Full Name",
  "photo_url": "https://... or null",
  "headline": "Current professional headline",
  "experiences": [
    { "title": "Job Title", "company": "Company Name", "period": "YYYY - YYYY or Present" }
  ],
  "educations": [
    { "degree": "Degree Name", "school": "School Name", "period": "YYYY - YYYY" }
  ],
  "bio_summary": "3-4 sentence first-person professional bio."
}

Profile URL: {$linkedinUrl}

Profile text to extract from:
---
{$profileText}
---
PROMPT;
    }

    /**
     * Internal Vertex AI caller with higher token limit for LinkedIn parsing.
     * Separate from the standard callVertexAi() to avoid changing discovery insights.
     */
    private function callVertexAiWithHighTokens(string $prompt): string
    {
        $envCreds = env('GOOGLE_CLOUD_CREDENTIALS_JSON');

        if ($envCreds) {
            $credentialPath = '/tmp/google-creds.json';
            file_put_contents($credentialPath, $envCreds);
        } else {
            $credentialPath = base_path('storage/service-account.json');
        }

        if (!file_exists($credentialPath)) {
            throw new \Exception('Vertex AI Credentials not found.');
        }

        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $credentialPath);

        $projectId = env('GOOGLE_CLOUD_PROJECT', 'connectx-app-482206');
        $location  = env('VERTEX_LOCATION', 'us-central1');
        $scopes    = ['https://www.googleapis.com/auth/cloud-platform'];

        $middleware = \Google\Auth\ApplicationDefaultCredentials::getMiddleware($scopes);
        $stack      = \GuzzleHttp\HandlerStack::create();
        $stack->push($middleware);

        $client = new \GuzzleHttp\Client([
            'handler'  => $stack,
            'base_uri' => "https://{$location}-aiplatform.googleapis.com/",
            'auth'     => 'google_auth',
            'timeout'  => 30.0, // Higher timeout for parsing heavy HTML
        ]);

        $endpoint = "v1/projects/{$projectId}/locations/{$location}/publishers/google/models/gemini-1.5-pro:generateContent";

        $response = $client->post($endpoint, [
            'json' => [
                'contents' => [
                    [
                        'role'  => 'user',
                        'parts' => [['text' => $prompt]],
                    ],
                ],
                'generationConfig' => [
                    'temperature'     => 0.2, // Low temperature for factual extraction
                    'maxOutputTokens' => 2048,
                ],
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        return $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
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
     */
    private function callVertexAi(string $prompt): string
    {
        // 1. Check if credentials exist in ENV as a JSON string (Vercel way)
        $envCreds = env('GOOGLE_CLOUD_CREDENTIALS_JSON');
        
        if ($envCreds) {
            // We'll write it to /tmp temporarily because the underlying Google library 
            // often expects a file path for the default credentials middleware.
            $credentialPath = '/tmp/google-creds.json';
            file_put_contents($credentialPath, $envCreds);
        } else {
            // Fallback to local file path (Development way)
            $credentialPath = base_path('storage/service-account.json');
        }

        if (!file_exists($credentialPath)) {
            throw new \Exception("Vertex AI Credentials not found. Please set GOOGLE_CLOUD_CREDENTIALS_JSON in .env or provide storage/service-account.json");
        }

        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $credentialPath);

        // Read Project ID and Location
        $projectId = env('GOOGLE_CLOUD_PROJECT', 'connectx-app-482206');
        $location  = env('VERTEX_LOCATION', 'us-central1');

        $scopes = ['https://www.googleapis.com/auth/cloud-platform'];
        
        // This middleware automatically fetches the OAuth2 Bearer token
        $middleware = ApplicationDefaultCredentials::getMiddleware($scopes);
        $stack = HandlerStack::create();
        $stack->push($middleware);

        $client = new Client([
            'handler'  => $stack,
            'base_uri' => "https://{$location}-aiplatform.googleapis.com/",
            'auth'     => 'google_auth', // Triggers the middleware
            'timeout'  => 10.0,
        ]);

        $endpoint = "v1/projects/{$projectId}/locations/{$location}/publishers/google/models/gemini-1.5-pro:generateContent";

        $response = $client->post($endpoint, [
            'json' => [
                'contents' => [
                    [
                        'role'  => 'user',
                        'parts' => [['text' => $prompt]]
                    ]
                ],
                'generationConfig' => [
                    'temperature'     => 0.7,
                    'maxOutputTokens' => 150,
                ]
            ]
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'AI insight unavailable.';
    }
}
