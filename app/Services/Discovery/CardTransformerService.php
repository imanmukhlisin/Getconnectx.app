<?php

namespace App\Services\Discovery;

use App\Models\Startup;
use App\Models\User;
use App\Services\Discovery\MatchmakingScoringService;
use Carbon\Carbon;

class CardTransformerService
{
    public function __construct(private MatchmakingScoringService $scoringService)
    {
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Profile Card (entityType: "profile")
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Transform a User model into the V2 profile card response format.
     */
    public function transformProfileCard(User $user, int $index, ?float $distanceKm = null, ?User $authUser = null): array
    {
        $distanceKm = isset($user->distance_km) ? round((float) $user->distance_km, 1) : null;

        $matchResult = ['score' => rand(60, 99), 'label' => 'Potential Match'];
        if ($authUser) {
            $matchResult = $this->scoringService->computeScore($authUser, $user, 'finding_cofounder');
        }

        // Build interests from user tags (Industries)
        $interests = [];
        if ($user->relationLoaded('tags')) {
            $interests = $user->tags->where('type', 'industry')->map(fn($tag) => [
                'id'   => 'in_' . $tag->id,
                'name' => $this->getOnboardingLabel(['q_su_industry', 'q_fdr_industry'], $tag->name),
            ])->values()->toArray();

            // Add availability as interest item
            if ($user->commitment_level) {
                $interests[] = [
                    'id'   => 'avail_' . $user->id,
                    'name' => $this->getOnboardingLabel(['q_cf_avail', 'q_fdr_cf_avail', 'q_tm_avail'], $user->commitment_level),
                    'type' => 'availability',
                ];
            }
        }

        // Build skills: dari user tags (onboarding) — fallback ke onboarding_responses jika kosong
        $skills = [];
        if ($user->relationLoaded('tags')) {
            $skills = $user->tags->where('type', 'skill')->map(fn($tag) => [
                'id'   => 'sk_' . $tag->id,
                'name' => $this->getOnboardingLabel('q_tm_skills', $tag->name),
            ])->values()->toArray();
        }

        // Fallback: ambil dari onboarding_responses (q_tm_skills / q_cf_skills)
        if (empty($skills)) {
            // Level 1: cari question skills spesifik
            $skillResponse = \Illuminate\Support\Facades\DB::table('onboarding_responses')
                ->join('onboarding_sessions', 'onboarding_sessions.id', '=', 'onboarding_responses.session_id')
                ->where('onboarding_sessions.user_id', $user->id)
                ->where('onboarding_sessions.status', 'completed')
                ->whereIn('onboarding_responses.question_id', ['q_tm_skills', 'q_cf_skills', 'q_fdr_skills'])
                ->orderBy('onboarding_sessions.completed_at', 'desc')
                ->value('onboarding_responses.value');

            if ($skillResponse) {
                $rawSkills = is_array($skillResponse) ? $skillResponse : json_decode($skillResponse, true);
                if (is_array($rawSkills)) {
                    $skills = collect($rawSkills)->map(fn($s) => [
                        'id'   => 'sk_ob_' . md5($s),
                        'name' => $this->getOnboardingLabel('q_tm_skills', $s),
                    ])->values()->toArray();
                }
            }
        }

        // Level 2: fallback dari q_cf_type (tipe co-founder) + q_bld_role
        if (empty($skills)) {
            $typeResponse = \Illuminate\Support\Facades\DB::table('onboarding_responses')
                ->join('onboarding_sessions', 'onboarding_sessions.id', '=', 'onboarding_responses.session_id')
                ->where('onboarding_sessions.user_id', $user->id)
                ->where('onboarding_sessions.status', 'completed')
                ->whereIn('onboarding_responses.question_id', ['q_cf_type', 'q_bld_role', 'q_fdr_type'])
                ->orderBy('onboarding_sessions.completed_at', 'desc')
                ->get(['onboarding_responses.question_id', 'onboarding_responses.value']);

            foreach ($typeResponse as $resp) {
                $raw = is_array($resp->value) ? $resp->value : json_decode($resp->value, true);
                if (is_array($raw)) {
                    foreach ($raw as $s) {
                        $skills[] = [
                            'id'   => 'sk_ob_' . md5($s),
                            'name' => $this->getOnboardingLabel([$resp->question_id, 'q_cf_type', 'q_bld_role'], $s),
                        ];
                    }
                }
            }
            $skills = array_values(array_unique($skills, SORT_REGULAR));
        }

        return [
            'entityType'  => 'profile',
            'id'          => 'card_' . substr(md5($user->id . $index), 0, 8),
            'profileId'   => $user->id,
            'photoUrl'    => $user->avatar_url,
            'name'        => $user->name,
            'age'         => $user->date_of_birth ? Carbon::parse($user->date_of_birth)->age : null,
            'headline'    => $user->position,
            'location'    => [
                'city'       => $user->city,
                'country'    => $user->country,
                'display'    => $this->buildLocationDisplay($user),
                'distanceKm' => $distanceKm,
            ],
            'match'          => $matchResult,
            'badges'         => $this->buildBadges($user),
            'bio'            => $user->bio,
            'startupIdea'    => $user->startup_idea,
            'interests'      => $interests,
            'skills'         => $skills,
            'experience'     => $this->buildExperience($user),
            'education'      => $this->buildEducation($user),
            'certifications' => $this->buildCertifications($user),
            'languages'      => $this->buildLanguages($user),
            'linkedinUrl'    => $user->linkedin_url ?: null,
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
        
        $matchResult = ['score' => rand(60, 99), 'label' => 'Potential Match'];
        if ($authUser && $owner) {
            $matchResult = $this->scoringService->computeScore($authUser, $owner, 'explore_startups');
        }

        $industryDisplay = $this->buildIndustryDisplay($startup->industry, $startup->secondary_industry);
        $stageLabel      = $this->getOnboardingLabel('q_su_stage', $startup->stage);

        return [
            'entityType' => 'startup',
            'id'         => 'startup_card_' . substr(md5($startup->id . $index), 0, 8),
            'startupId'  => $startup->id,
            'name'       => $startup->name,
            'logoUrl'    => $startup->logo_url,
            'badge'      => [
                'label' => $stageLabel,
            ],
            'founder' => $owner ? [
                'name'  => $owner->name,
                'title' => 'Founder',
            ] : null,
            'match' => $matchResult,
            'industry' => [
                'primary'   => $startup->industry,
                'secondary' => $startup->secondary_industry,
                'display'   => $industryDisplay,
            ],
            'team' => [
                'memberCount' => $startup->team_size,
                'display'     => $startup->team_size . ' member' . ($startup->team_size !== 1 ? 's' : ''),
            ],
            'summary'   => $startup->description,
            'openRoles' => $startup->open_roles ?? [],
            'lookingFor' => $startup->looking_for ?? [],
            'teamStage' => [
                'teamSize'    => $startup->team_size,
                'stage'       => $stageLabel,
                'industry'    => $industryDisplay,
                'hiringCount' => count($startup->open_roles ?? []),
            ],
            'journey' => $this->buildJourney($startup->stage),
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Private Helpers
    // ═══════════════════════════════════════════════════════════════════

    private function getOnboardingLabel($questionIds, ?string $value): string
    {
        if (empty($value)) return '';

        if (is_string($questionIds)) $questionIds = [$questionIds];

        // Cache key pakai hash dari questionIds + value supaya tidak collision
        $cacheKey = 'onboarding_label_' . md5(implode(',', $questionIds) . '_' . $value);

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function() use ($questionIds, $value) {
            $option = \Illuminate\Support\Facades\DB::table('onboarding_options')
                ->whereIn('question_id', $questionIds)
                ->where('value', $value)
                ->first();

            if ($option) {
                $labels = $option->label;
                if (is_string($labels)) $labels = json_decode($labels, true);
                return $labels['id'] ?? $labels['en'] ?? $value;
            }

            // Final fallback: humanize slug (full_time → Full Time)
            return ucwords(str_replace(['_', '-'], ' ', $value));
        });
    }

    private function buildMatchLabel(int $score): string
    {
        return match (true) {
            $score >= 95 => 'Top Match',
            $score >= 90 => 'Perfect Match',
            $score >= 80 => 'Strong Match',
            $score >= 70 => 'Good Match',
            default      => 'Potential Match',
        };
    }

    private function buildLocationDisplay(User $user): string
    {
        // Try to get dynamic label from onboarding if city is a slug
        if ($user->city && !str_contains($user->city, ',')) {
            $label = $this->getOnboardingLabel('q_location', $user->city);
            if ($label !== ucwords(str_replace(['_', '-'], ' ', $user->city))) {
                return $label;
            }
        }

        return collect([$user->city, $user->country])->filter()->implode(', ') ?: 'Unknown';
    }

    private function buildIndustryDisplay(?string $primary, ?string $secondary): string
    {
        $primaryLabel   = $this->getOnboardingLabel(['q_su_industry', 'q_fdr_industry'], $primary);
        $secondaryLabel = $this->getOnboardingLabel(['q_su_industry', 'q_fdr_industry'], $secondary);

        return collect([$primaryLabel, $secondaryLabel])->filter()->implode(' / ') ?: 'General';
    }

    private function buildBadges(User $user): array
    {
        $badges = [];
        if ($user->startup_stage) {
            $label = $this->getOnboardingLabel('q_su_stage', $user->startup_stage);
            $badges[] = ['id' => 'badge_' . $user->startup_stage, 'label' => $label, 'icon' => 'rocket'];
        }
        if ($user->is_pro) {
            $badges[] = ['id' => 'badge_pro', 'label' => 'Pro', 'icon' => 'sparkles'];
        }
        return $badges;
    }

    private function buildExperience(User $user): array
    {
        // Try to get data from LinkedIn Sync first
        if ($user->relationLoaded('credentials')) {
            $linkedIn = $user->credentials->where('provider', 'linkedin')->first();
            if ($linkedIn && !empty($linkedIn->experience)) {
                return $linkedIn->experience;
            }
        }

        // Fallback to basic profile data
        $exp = [];
        if ($user->position) {
            $exp[] = [
                'id'           => 'exp_' . substr(md5($user->id . '_position'), 0, 4),
                'title'        => $user->position,
                'organization' => $user->startup_name,
                'period'       => 'Current',
            ];
        }
        return $exp;
    }

    private function buildEducation(User $user): array
    {
        // Try to get data from LinkedIn Sync first
        if ($user->relationLoaded('credentials')) {
            $linkedIn = $user->credentials->where('provider', 'linkedin')->first();
            if ($linkedIn && !empty($linkedIn->education)) {
                return $linkedIn->education;
            }
        }

        // Fallback to basic profile data
        return $user->education ?? [];
    }

    private function buildJourney(?string $currentStage): array
    {
        $stages = [
            ['id' => 'idea',     'label' => 'Idea'],
            ['id' => 'mvp',      'label' => 'MVP'],
            ['id' => 'pre_seed', 'label' => 'Pre-Seed'],
            ['id' => 'seed',     'label' => 'Seed'],
        ];

        $stageMap = ['idea' => 0, 'mvp' => 1, 'pre-seed' => 2, 'pre_seed' => 2, 'seed' => 3];
        $currentIdx = $stageMap[$currentStage] ?? -1;

        return [
            'currentStage' => $currentStage ? str_replace('-', '_', $currentStage) : null,
            'stages'       => array_map(function ($stage, $idx) use ($currentIdx) {
                $state = $idx < $currentIdx ? 'completed' : ($idx === $currentIdx ? 'current' : 'upcoming');
                return array_merge($stage, ['state' => $state]);
            }, $stages, array_keys($stages)),
        ];
    }

    /**
     * Build certifications from LinkedIn raw_data scraping.
     * raw_data['certifications'] is an array of objects from Apify.
     */
    private function buildCertifications(User $user): array
    {
        if ($user->relationLoaded('credentials')) {
            $linkedIn = $user->credentials->where('provider', 'linkedin')->first();
            if ($linkedIn && !empty($linkedIn->raw_data['certifications'])) {
                return collect($linkedIn->raw_data['certifications'])
                    ->map(fn($cert) => [
                        'name'   => $cert['name']         ?? $cert['title']   ?? '',
                        'issuer' => $cert['authority']    ?? $cert['issuer']  ?? $cert['organization'] ?? '',
                        'date'   => $cert['displayDate']  ?? $cert['date']    ?? null,
                    ])
                    ->filter(fn($c) => !empty($c['name']))
                    ->values()
                    ->toArray();
            }
        }

        return [];
    }

    /**
     * Build languages from LinkedIn raw_data scraping.
     * Falls back to users.languages column if LinkedIn data is not available.
     */
    private function buildLanguages(User $user): array
    {
        if ($user->relationLoaded('credentials')) {
            $linkedIn = $user->credentials->where('provider', 'linkedin')->first();
            if ($linkedIn && !empty($linkedIn->raw_data['languages'])) {
                return collect($linkedIn->raw_data['languages'])
                    ->map(fn($lang) => is_string($lang) ? $lang : ($lang['name'] ?? ''))
                    ->filter()
                    ->values()
                    ->toArray();
            }
        }

        // Fallback: kolom languages langsung di tabel users
        $langs = $user->languages;
        if (!empty($langs)) {
            return is_array($langs) ? $langs : [$langs];
        }

        return [];
    }
}
