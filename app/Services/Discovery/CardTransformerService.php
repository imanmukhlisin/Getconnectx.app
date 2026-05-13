<?php

namespace App\Services\Discovery;

use App\Models\Startup;
use App\Models\User;
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
    //  Profile Card (entityType: "profile") — CON-60
    // ═══════════════════════════════════════════════════════════════════

    public function transformProfileCard(User $user, int $index, ?float $distanceKm = null, ?User $authUser = null): array
    {
        $distanceKm = isset($user->distance_km) ? round((float) $user->distance_km, 1) : null;
        $isPro      = $authUser && $authUser->is_pro;

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

        // Age from date_of_birth
        $age = null;
        if ($user->date_of_birth) {
            try {
                $age = Carbon::parse($user->date_of_birth)->age;
            } catch (\Throwable $e) {
                $age = null;
            }
        }

        // Location as object per CON-60
        $locationDisplay = collect([$user->city, $user->country])->filter()->implode(', ');
        $location = [
            'city'       => $user->city,
            'country'    => $user->country,
            'display'    => $locationDisplay ?: null,
            'distanceKm' => $distanceKm,
        ];

        // Match block per CON-60: { score, label }
        // Pro users also get highlights and reason
        $matchBlock = null;
        if ($matchResult) {
            $matchBlock = [
                'score'      => $matchResult['score'],
                'label'      => $matchResult['label'],
            ];
            if ($isPro) {
                $matchBlock['highlights'] = $matchResult['highlights'];
                $matchBlock['reason']     = $matchReason;
            }
        }

        return [
            'entityType'   => 'profile',
            'id'           => "card_{$user->id}_{$index}",
            'profileId'    => $user->id,
            // ── CON-60 field names ──────────────────────────────────────
            'photoUrl'     => $user->avatar_url,       // was avatarUrl — FIXED
            'name'         => $user->name,
            'age'          => $age,                    // was missing — ADDED
            'headline'     => $user->position,         // was position — FIXED
            'location'     => $location,               // was string — FIXED to object
            'match'        => $matchBlock,             // was matchmaking — FIXED
            'badges'       => [],                      // was missing — ADDED
            'bio'          => $user->bio,              // was about — FIXED
            'startupIdea'  => $user->startup_idea,    // was missing — ADDED
            'interests'    => [],                      // was missing — ADDED
            // ── Extra fields (still useful for FE) ─────────────────────
            'industry'     => $user->industry ?? $this->getFirstIndustryTag($user),
            'skills'       => $this->buildSkills($user),
            'certifications' => $this->buildCertifications($user),
            'languages'    => $this->buildLanguages($user),
            'socials'      => $this->buildSocialLinks($user),
            'experience'   => $this->buildExperience($user),
            'education'    => $this->buildEducation($user),
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Startup Card (entityType: "startup") — CON-60
    // ═══════════════════════════════════════════════════════════════════

    public function transformStartupCard(Startup $startup, int $index, ?float $distanceKm = null, ?User $authUser = null): array
    {
        $owner  = $startup->relationLoaded('owner') ? $startup->owner : null;
        $isPro  = $authUser && $authUser->is_pro;

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

        // Match block per CON-60
        $matchBlock = null;
        if ($matchResult) {
            $matchBlock = [
                'score' => $matchResult['score'],
                'label' => $matchResult['label'],
            ];
            if ($isPro) {
                $matchBlock['highlights'] = $matchResult['highlights'];
                $matchBlock['reason']     = $matchReason;
            }
        }

        // Industry as object per CON-60: { primary, secondary, display }
        $industryDisplay = collect([$startup->industry, $startup->secondary_industry])->filter()->implode(' · ');
        $industryBlock = [
            'primary'   => $startup->industry,
            'secondary' => $startup->secondary_industry,
            'display'   => $industryDisplay ?: null,
        ];

        // Team as object per CON-60: { memberCount, display }
        $teamBlock = [
            'memberCount' => $startup->team_size,
            'display'     => $startup->team_size ? "{$startup->team_size} members" : null,
        ];

        return [
            'entityType'  => 'startup',
            'id'          => "card_startup_{$startup->id}_{$index}",
            'startupId'   => $startup->id,
            // ── CON-60 field names ──────────────────────────────────────
            'name'        => $startup->name,
            'logoUrl'     => $startup->logo_url,
            'badge'       => ['label' => $startup->stage ? strtoupper($startup->stage) : null], // was missing — ADDED
            'founder'     => $owner ? [
                'name'  => $owner->name,
                'title' => $owner->position,              // was missing title — FIXED
            ] : null,
            'match'       => $matchBlock,                 // was matchmaking — FIXED
            'industry'    => $industryBlock,              // was string — FIXED to object
            'team'        => $teamBlock,                  // was teamSize number — FIXED
            // ── CON-60 content fields ────────────────────────────────────
            'summary'     => $startup->description,       // was about — FIXED
            'website'     => $startup->website ?? null,
            'openRoles'   => is_array($startup->open_roles) ? $startup->open_roles : [], // was missing — ADDED
            'lookingFor'  => $this->buildLookingFor($startup),
            'teamStage'   => $startup->stage,             // was stage — FIXED name
            'location'    => collect([$startup->city, $startup->country])->filter()->implode(', ') ?: null,
            'foundedAt'   => $startup->founded_at ? Carbon::parse($startup->founded_at)->format('Y') : null,
        ];
    }

    // ─── Private Helpers ──────────────────────────────────────────────────────

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
            // raw_data sudah di-cast ke array oleh model
            $data  = is_array($cred->raw_data) ? $cred->raw_data : [];
            $certs = $data['certifications'] ?? [];

            if (is_array($certs)) {
                foreach ($certs as $cert) {
                    // Key dari LinkedIn scrape: 'title' (bukan 'name'), 'issuedByLogo' berupa object {url, sizes[]}
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
        // 1. Cek kolom users.languages dulu (disinkron dari onboarding)
        if (!empty($user->languages) && is_array($user->languages)) {
            return $user->languages;
        }

        // 2. Fallback: ambil dari raw_data LinkedIn scrape
        if (!$user->relationLoaded('credentials')) return [];

        foreach ($user->credentials as $cred) {
            $data  = is_array($cred->raw_data) ? $cred->raw_data : [];
            $langs = $data['languages'] ?? [];

            if (!empty($langs) && is_array($langs)) {
                // Format LinkedIn: [{name, proficiency}] → return array of name strings
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
            'linkedin' => $user->linkedin_url,
            'github'   => $user->github_url ?? null,
        ];
    }

    private function buildExperience(User $user): array
    {
        if (!$user->relationLoaded('credentials')) return [];

        foreach ($user->credentials as $cred) {
            // UserCredential punya kolom 'experience' sendiri (bukan dari raw_data)
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
}
