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
}
