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
        $cacheKey   = self::CACHE_PREFIX . "insight:{$user->id}:{$mode}:{$filterHash}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user, $filters, $mode) {
            try {
                $prompt = $this->buildInsightPrompt($user, $filters, $mode);
                return $this->callVertexAi($prompt);
            } catch (\Throwable $e) {
                Log::error('Vertex AI Error: ' . $e->getMessage());
                // Fallback static text if AI fails
                return "Our matchmaking algorithm is analyzing profiles to find your perfect {$this->getModeLabel($mode)}.";
            }
        });
    }

    /**
     * Generate a specific reason why two users match.
     */
    public function generateMatchReason(User $authUser, User $targetUser, array $highlights): string
    {
        if (empty($highlights)) {
            return "Based on your preferences, this profile is a potential match for your startup journey.";
        }

        $cacheKey = self::CACHE_PREFIX . "reason:{$authUser->id}:{$targetUser->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($authUser, $targetUser, $highlights) {
            try {
                $prompt = $this->buildMatchReasonPrompt($authUser, $targetUser, $highlights);
                return $this->callVertexAi($prompt);
            } catch (\Throwable $e) {
                Log::error('Vertex AI Reason Error: ' . $e->getMessage());
                return implode(' and ', array_slice($highlights, 0, 2)) . '.';
            }
        });
    }





    /**
     * Build the contextual prompt for Discovery-wide insights.
     */
    private function buildInsightPrompt(User $user, array $filters, string $mode): string
    {
        $role = $user->position ?? 'Professional';
        $industry = $user->industry ?? 'Tech';
        
        $modeLabel = $this->getModeLabel($mode);
        $filterSummary = json_encode($filters);

        return <<<PROMPT
You are ConnectX AI, an expert matchmaking assistant.
Generate a short, engaging 1-sentence insight about the user's current search.
Context:
- User's Role: {$role}
- What they are doing: {$modeLabel}
- Search filters: {$filterSummary}

Explain why the platform is uniquely positioned to find them matches. Keep it under 25 words. Professional and enthusiastic.
PROMPT;
    }

    /**
     * Build the prompt for a specific user-to-user match reason.
     */
    private function buildMatchReasonPrompt(User $authUser, User $targetUser, array $highlights): string
    {
        $highlightsStr = implode(', ', $highlights);

        return <<<PROMPT
You are ConnectX AI. 
Explain in one very short, friendly sentence (max 15 words) why these two people are a great match.
Key Highlights: {$highlightsStr}
Example: "Both of you are based in Jakarta and share a passion for AI."
Be direct and warm.
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
