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
        $plainText = $this->fetchLinkedInProfileText($linkedinUrl);

        if (!$plainText) {
            Log::warning('VertexAiService@scrapeAndParseLinkedIn: All fetch attempts failed, cannot parse profile.', [
                'url' => $linkedinUrl,
            ]);
            return null;
        }

        try {
            $prompt  = $this->buildLinkedInExtractionPrompt($plainText, $linkedinUrl);
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
     * Try multiple sources to get plain text from a LinkedIn profile page.
     * Returns the first successful result, or null if all sources fail.
     */
    private function fetchLinkedInProfileText(string $linkedinUrl): ?string
    {
        $attempts = [
            // Attempt 1: Direct fetch — mimics LinkedIn's own bot crawler
            fn() => \Illuminate\Support\Facades\Http::withHeaders([
                'User-Agent'      => 'LinkedInBot/1.0 (compatible; LinkedInBot/1.0; +http://www.linkedin.com/)',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ])->timeout(12)->get($linkedinUrl),

            // Attempt 2: Google Cache — may contain an indexed snapshot
            fn() => \Illuminate\Support\Facades\Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
            ])->timeout(12)->get(
                'https://webcache.googleusercontent.com/search?q=cache:' . urlencode($linkedinUrl) . '&hl=en'
            ),

            // Attempt 3: LinkedIn mobile endpoint — sometimes has lighter bot protection
            fn() => \Illuminate\Support\Facades\Http::withHeaders([
                'User-Agent'      => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15',
                'Accept-Language' => 'en-US,en;q=0.9',
            ])->timeout(12)->get(str_replace('www.linkedin.com', 'm.linkedin.com', $linkedinUrl)),
        ];

        foreach ($attempts as $index => $attempt) {
            try {
                $response = $attempt();

                if ($response->successful() && strlen($response->body()) > 500) {
                    $html = $response->body();

                    // Strip scripts, styles, and tags — keep readable text only
                    $text = preg_replace('/<(script|style)[^>]*>.*?<\/\1>/si', '', $html);
                    $text = strip_tags($text);
                    $text = preg_replace('/\s+/', ' ', $text);
                    $text = trim($text);
                    $text = mb_substr($text, 0, 6000);

                    if (strlen($text) > 200) {
                        Log::info('VertexAiService@fetchLinkedInProfileText: Fetched successfully.', [
                            'attempt' => $index + 1,
                            'chars'   => strlen($text),
                        ]);
                        return $text;
                    }
                }
            } catch (\Throwable $e) {
                Log::debug('VertexAiService@fetchLinkedInProfileText: Attempt ' . ($index + 1) . ' failed.', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return null;
    }

    /**
     * Build the extraction prompt for Gemini — plain text version (no grounding).
     */
    private function buildLinkedInExtractionPrompt(string $profileText, string $linkedinUrl): string
    {
        return <<<PROMPT
You are a professional data extraction assistant.
Extract structured professional information from the following LinkedIn profile page text.

Profile URL: {$linkedinUrl}

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

Profile text:
---
{$profileText}
---
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
     * Call Gemini 1.5 Pro for LinkedIn data extraction.
     * Uses the same credential infrastructure as callVertexAi() (Discovery insights)
     * but with a higher token limit appropriate for parsing profile text.
     *
     * This intentionally does NOT use grounding tools — grounding requires
     * additional Vertex AI Search project configuration that is not set up.
     */
    private function callGeminiForExtraction(string $prompt): string
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
                    'temperature'     => 0.1, // Low temperature for factual extraction
                    'maxOutputTokens' => 2048,
                ],
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        return $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
    }
}
