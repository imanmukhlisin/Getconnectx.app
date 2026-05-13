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

        // Location as Object (CON-60)
        $locationDisplay = collect([$user->city, $user->country])->filter()->implode(', ');
        $locationBlock = [
            'city'       => $user->city ?? null,
            'country'    => $user->country ?? null,
            'display'    => $locationDisplay ?: null,
            'distanceKm' => $distanceKm,
        ];

        // Match Block (CON-60)
        $matchBlock = null;
        if ($matchResult) {
            $matchBlock = [
                'score'      => $matchResult['score'],
                'label'      => $matchResult['label'],
            ];
            if ($isPro) {
                $matchBlock['highlights'] = $matchResult['highlights'] ?? [];
                $matchBlock['reason']     = $matchReason;
            }
        }

        // Age Calculation
        $age = null;
        if ($user->date_of_birth) {
            try {
                $age = Carbon::parse($user->date_of_birth)->age;
            } catch (\Throwable $e) {
                $age = null;
            }
        }

        return [
            'entityType'   => 'profile',
            'id'           => "card_{$user->id}_{$index}",
            'profileId'    => $user->id,
            'photoUrl'     => $user->avatar_url,
            'name'         => $user->name,
            'age'          => $age,
            'headline'     => $user->position,
            'location'     => $locationBlock,
            'match'        => $matchBlock,
            'badges'       => [], // Placeholder for badges
            'bio'          => $user->bio,
            'startupIdea'  => $user->startup_idea ?? null,
            'linkedinUrl'  => $user->linkedin_url ?? null,
            'industry'     => $user->industry ?? $this->getFirstIndustryTag($user),
            'industries'   => $this->buildIndustries($user),
            'interests'    => $this->buildInterests($user),
            'skills'       => $this->buildSkills($user),
            'certifications' => $this->buildCertifications($user),
            'languages'    => $this->buildLanguages($user),
            'experience'   => $this->buildExperience($user),
            'education'    => $this->buildEducation($user),
            'socials'      => $this->buildSocialLinks($user),
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

        // Match Block (CON-60)
        $matchBlock = null;
        if ($matchResult) {
            $matchBlock = [
                'score'      => $matchResult['score'],
                'label'      => $matchResult['label'],
            ];
            if ($isPro) {
                $matchBlock['highlights'] = $matchResult['highlights'] ?? [];
                $matchBlock['reason']     = $matchReason;
            }
        }

        // Industry Object (CON-60)
        $industryDisplay = collect([$startup->industry, $startup->secondary_industry ?? null])->filter()->implode(' · ');
        $industryBlock = [
            'primary'   => $startup->industry,
            'secondary' => $startup->secondary_industry ?? null,
            'display'   => $industryDisplay ?: null,
        ];

        // Team Object (CON-60)
        $teamBlock = [
            'memberCount' => $startup->team_size ?? 1,
            'display'     => $startup->team_size ? "{$startup->team_size} members" : "1 member",
        ];

        // TeamStage Object (API-MACHMAKING)
        $openRoles = is_array($startup->open_roles) ? $startup->open_roles : [];
        $teamStageBlock = [
            'teamSize'    => $startup->team_size ?? 1,
            'stage'       => $startup->stage ? strtoupper($startup->stage) : null,
            'industry'    => $startup->industry,
            'hiringCount' => count($openRoles)
        ];

        // Journey Object (API-MACHMAKING)
        $journeyBlock = $this->buildJourney($startup->stage);

        return [
            'entityType'   => 'startup',
            'id'           => "card_startup_{$startup->id}_{$index}",
            'startupId'    => $startup->id,
            'name'         => $startup->name,
            'logoUrl'      => $startup->logo_url,
            'badge'        => ['label' => $startup->stage ? strtoupper($startup->stage) : null],
            'founder'      => $owner ? [
                'name'  => $owner->name,
                'title' => $owner->position ?? 'Founder',
            ] : null,
            'match'        => $matchBlock,
            'industry'     => $industryBlock,
            'team'         => $teamBlock,
            'summary'      => $startup->description,
            'openRoles'    => $openRoles,
            'lookingFor'   => $this->buildLookingFor($startup),
            'teamStage'    => $teamStageBlock,
            'journey'      => $journeyBlock,
        ];
    }

    // ─── Sub-builders ─────────────────────────────────────────────────────────

    private function buildSkills(User $user): array
    {
        if (!$user->relationLoaded('tags')) return [];
        return $user->tags->where('type', 'skill')->pluck('name')->values()->toArray();
    }

    private function buildInterests(User $user): array
    {
        if (!$user->relationLoaded('tags')) return [];
        return $user->tags->where('type', 'interest')->pluck('name')->values()->toArray();
    }

    private function buildIndustries(User $user): array
    {
        if (!$user->relationLoaded('tags')) return [];
        return $user->tags->where('type', 'industry')->pluck('name')->values()->toArray();
    }

    private function buildCertifications(User $user): array
    {
        if (!$user->relationLoaded('credentials')) return [];

        $allCerts = [];
        foreach ($user->credentials as $cred) {
            $data  = is_array($cred->raw_data) ? $cred->raw_data : [];
            $certs = $data['certifications'] ?? [];

            if (is_array($certs)) {
                foreach ($certs as $cert) {
                    // LinkedIn scrape key: 'title' bukan 'name'
                    // issuedByLogo adalah object {url, sizes[]}
                    $logoUrl = null;
                    if (isset($cert['issuedByLogo']['url'])) {
                        $logoUrl = $cert['issuedByLogo']['url'];
                    } elseif (isset($cert['issuedByLogo']['sizes'][0]['url'])) {
                        $logoUrl = $cert['issuedByLogo']['sizes'][0]['url'];
                    }

                    $allCerts[] = [
                        'name'    => $cert['title'] ?? $cert['name'] ?? 'Certification',
                        'issuer'  => $cert['issuedBy'] ?? '',
                        'logoUrl' => $logoUrl,
                        'date'    => $cert['issuedAt'] ?? null,
                        'link'    => $cert['link'] ?? null,
                    ];
                }
            }
        }

        return $allCerts;
    }

    private function buildLanguages(User $user): array
    {
        // 1. Prioritas kolom users.languages (dari onboarding)
        if (!empty($user->languages) && is_array($user->languages)) {
            return $user->languages;
        }

        // 2. Fallback: dari raw_data LinkedIn scrape
        if (!$user->relationLoaded('credentials')) return [];

        foreach ($user->credentials as $cred) {
            $data  = is_array($cred->raw_data) ? $cred->raw_data : [];
            $langs = $data['languages'] ?? [];

            if (!empty($langs) && is_array($langs)) {
                // Format LinkedIn: [{name, proficiency}] → ambil nama saja
                return array_values(array_filter(array_map(
                    fn($l) => $l['name'] ?? null,
                    $langs
                )));
            }
        }

        return [];
    }

    private function buildSocialLinks(User $user): array
    {
        return [
            'linkedin' => $user->linkedin_url ?? null,
        ];
    }

    private function buildExperience(User $user): array
    {
        if (!$user->relationLoaded('credentials')) return [];

        foreach ($user->credentials as $cred) {
            if (!empty($cred->experience) && is_array($cred->experience)) {
                return $cred->experience;
            }
        }

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

    private function buildJourney(?string $currentStage): array
    {
        $allStages = ['idea', 'mvp', 'pre_seed', 'seed'];
        // Normalize DB stage value to array values if needed, fallback to idea
        $normalizedStage = strtolower(str_replace('-', '_', $currentStage ?? 'idea'));

        $currentIndex = array_search($normalizedStage, $allStages);
        if ($currentIndex === false) $currentIndex = 0;

        $stagesList = [];
        foreach ($allStages as $i => $stageId) {
            $labelMap = [
                'idea' => 'Idea',
                'mvp' => 'MVP',
                'pre_seed' => 'Pre-Seed',
                'seed' => 'Seed'
            ];

            $state = 'upcoming';
            if ($i < $currentIndex) {
                $state = 'completed';
            } elseif ($i === $currentIndex) {
                $state = 'current';
            }

            $stagesList[] = [
                'id' => $stageId,
                'label' => $labelMap[$stageId] ?? ucfirst($stageId),
                'state' => $state
            ];
        }

        return [
            'currentStage' => $normalizedStage,
            'stages' => $stagesList
        ];
    }
}
