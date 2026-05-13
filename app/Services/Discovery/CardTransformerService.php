<?php

namespace App\Services\Discovery;

use App\Models\Startup;
use App\Models\User;
use App\Models\Tag;
use App\Services\Discovery\MatchmakingScoringService;
use App\Services\Discovery\VertexAiService;
use Carbon\Carbon;

class CardTransformerService
{
    public function __construct(
        private MatchmakingScoringService $scoringService,
        private VertexAiService $vertexAiService
    ) {}

    // ═══════════════════════════════════════════════════════════════════
    //  Profile Card (entityType: "profile")
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Transform a User model into the V2 profile card response format.
     */
    public function transformProfileCard(User $user, int $index, ?float $distanceKm = null, ?User $authUser = null): array
    {
        $distanceKm = isset($user->distance_km) ? round((float) $user->distance_km, 1) : null;
        $isPro = $authUser && $authUser->is_pro;

        // Calculate matchmaking score
        $matchResult = null;
        if ($authUser) {
            $matchResult = $this->scoringService->computeScore($authUser, $user, 'finding_cofounder');
        }

        // Generate AI Match Reason (Only for Pro users with significant highlights)
        $matchReason = null;
        if ($isPro && $matchResult && !empty($matchResult['highlights'])) {
            try {
                $matchReason = $this->vertexAiService->generateMatchReason($authUser, $user, $matchResult['highlights']);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("AI Match Reason Error: " . $e->getMessage());
                $matchReason = null;
            }
        }

        return [
            'entityType'   => 'profile',
            'id'           => "card_{$user->id}_{$index}",
            'profileId'    => $user->id,
            'name'         => $user->name,
            'avatarUrl'    => $user->avatar_url,
            'position'     => $user->position,
            'industry'     => $user->industry ?? $this->getFirstIndustryTag($user),
            'location'     => $user->city ? "{$user->city}, {$user->country}" : $user->country,
            'distanceKm'   => $distanceKm,
            
            'matchmaking'  => $matchResult ? [
                'score'      => $matchResult['score'],
                'label'      => $matchResult['label'],
                'highlights' => $isPro ? $matchResult['highlights'] : [],
                'reason'     => $isPro ? $matchReason : null,
            ] : null,

            'skills'         => $this->buildSkills($user),
            'certifications' => $this->buildCertifications($user),
            'languages'      => is_array($user->languages) ? $user->languages : [],
            'about'          => $user->bio,
            'socials'        => $this->buildSocialLinks($user),
            'experience'     => $this->buildExperience($user),
            'education'      => $this->buildEducation($user),
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Startup Card (entityType: "startup")
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Transform a Startup model into the V2 startup card response format.
     */
    public function transformStartupCard(Startup $startup, int $index, ?float $distanceKm = null, ?User $authUser = null): array
    {
        $owner = $startup->relationLoaded('owner') ? $startup->owner : null;
        $isPro = $authUser && $authUser->is_pro;

        // Calculate matchmaking score with founder
        $matchResult = null;
        if ($authUser && $owner) {
            $matchResult = $this->scoringService->computeScore($authUser, $owner, 'explore_startups');
        }

        // Generate AI Match Reason (Only for Pro)
        $matchReason = null;
        if ($isPro && $owner && $matchResult && !empty($matchResult['highlights'])) {
            try {
                $matchReason = $this->vertexAiService->generateMatchReason($authUser, $owner, $matchResult['highlights']);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("AI Startup Match Reason Error: " . $e->getMessage());
                $matchReason = null;
            }
        }

        return [
            'entityType'   => 'startup',
            'id'           => "card_startup_{$startup->id}_{$index}",
            'startupId'    => $startup->id,
            'name'         => $startup->name,
            'logoUrl'      => $startup->logo_url,
            'tagline'      => $startup->tagline,
            'industry'     => $startup->industry,
            'stage'        => $startup->stage,
            'location'     => $startup->location,
            
            'matchmaking'  => $matchResult ? [
                'score'      => $matchResult['score'],
                'label'      => $matchResult['label'],
                'highlights' => $isPro ? $matchResult['highlights'] : [],
                'reason'     => $isPro ? $matchReason : null,
            ] : null,

            'about'        => $startup->description,
            'website'      => $startup->website,
            'lookingFor'   => $this->buildLookingFor($startup),
            'teamSize'     => $startup->team_size,
            'foundedAt'    => $startup->founded_at ? Carbon::parse($startup->founded_at)->format('Y') : null,
            'founder'      => $owner ? [
                'name'      => $owner->name,
                'avatarUrl' => $owner->avatar_url,
                'position'  => $owner->position,
            ] : null,
        ];
    }

    // ─── Sub-builders ─────────────────────────────────────────────────────────

    private function buildSkills(User $user): array
    {
        if (!$user->relationLoaded('tags')) return [];
        return $user->tags->where('type', 'skill')->pluck('name')->toArray();
    }

    private function buildCertifications(User $user): array
    {
        if (!$user->relationLoaded('credentials')) return [];
        
        $allCerts = [];
        foreach ($user->credentials as $cred) {
            $data = is_array($cred->raw_data) ? $cred->raw_data : json_decode($cred->raw_data, true);
            $certs = $data['certifications'] ?? [];
            
            if (is_array($certs)) {
                foreach ($certs as $cert) {
                    $allCerts[] = [
                        'name'    => $cert['name'] ?? 'Certification',
                        'issuer'  => $cert['issuedBy'] ?? '',
                        'logoUrl' => $cert['issuedByLogo'] ?? null,
                        'date'    => $cert['issuedAt'] ?? null,
                    ];
                }
            }
        }

        return $allCerts;
    }

    private function buildSocialLinks(User $user): array
    {
        return [
            'linkedin' => $user->linkedin_url,
            'github'   => $user->github_url,
        ];
    }

    private function buildExperience(User $user): array
    {
        // Mocked or extracted from credentials
        return [];
    }

    private function buildEducation(User $user): array
    {
        return is_array($user->education) ? $user->education : [];
    }

    private function buildLookingFor(Startup $startup): array
    {
        return is_array($startup->looking_for) ? $startup->looking_for : [];
    }

    private function getFirstIndustryTag(User $user): ?string
    {
        if (!$user->relationLoaded('tags')) return null;
        return $user->tags->where('type', 'industry')->first()?->name;
    }
}
