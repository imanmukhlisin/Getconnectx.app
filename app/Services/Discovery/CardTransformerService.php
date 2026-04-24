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

        // Build interests from user tags
        $interests = [];
        if ($user->relationLoaded('tags')) {
            $interests = $user->tags->where('type', 'industry')->map(fn($tag) => [
                'id'   => 'in_' . $tag->id,
                'name' => $tag->name,
            ])->values()->toArray();

            // Add availability as interest item
            if ($user->commitment_level) {
                $interests[] = [
                    'id'   => 'avail_' . $user->id,
                    'name' => ucfirst(str_replace('-', ' ', $user->commitment_level)),
                    'type' => 'availability',
                ];
            }
        }

        // Build skills from user tags
        $skills = [];
        if ($user->relationLoaded('tags')) {
            $skills = $user->tags->where('type', 'skill')->map(fn($tag) => [
                'id'   => 'sk_' . $tag->id,
                'name' => $tag->name,
            ])->values()->toArray();
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
                'display'    => $this->buildLocationDisplay($user->city, $user->country),
                'distanceKm' => $distanceKm,
            ],
            'match' => $matchResult,
            'badges'      => $this->buildBadges($user),
            'bio'         => $user->bio,
            'startupIdea' => $user->startup_idea,
            'interests'   => $interests,
            'skills'      => $skills,
            'experience'  => $this->buildExperience($user),
            'education'   => $user->education ?? [],
            'languages'   => $user->languages ?? [],
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

        return [
            'entityType' => 'startup',
            'id'         => 'startup_card_' . substr(md5($startup->id . $index), 0, 8),
            'startupId'  => $startup->id,
            'name'       => $startup->name,
            'logoUrl'    => $startup->logo_url,
            'badge'      => [
                'label' => $startup->stage ? strtoupper(str_replace('-', ' ', $startup->stage)) : null,
            ],
            'founder' => $owner ? [
                'name'  => $owner->name,
                'title' => 'Founder',
            ] : null,
            'match' => $matchResult,
            'industry' => [
                'primary'   => $startup->industry,
                'secondary' => $startup->secondary_industry,
                'display'   => $this->buildIndustryDisplay($startup->industry, $startup->secondary_industry),
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
                'stage'       => $startup->stage ? strtoupper(str_replace('-', ' ', $startup->stage)) : null,
                'industry'    => $this->buildIndustryDisplay($startup->industry, $startup->secondary_industry),
                'hiringCount' => count($startup->open_roles ?? []),
            ],
            'journey' => $this->buildJourney($startup->stage),
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    //  Private Helpers
    // ═══════════════════════════════════════════════════════════════════

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

    private function buildLocationDisplay(?string $city, ?string $country): string
    {
        return collect([$city, $country])->filter()->implode(', ') ?: 'Unknown';
    }

    private function buildIndustryDisplay(?string $primary, ?string $secondary): string
    {
        return collect([$primary, $secondary])->filter()->implode(' / ') ?: 'General';
    }

    private function buildBadges(User $user): array
    {
        $badges = [];
        if ($user->startup_stage) {
            $badges[] = ['id' => 'badge_' . $user->startup_stage, 'label' => strtoupper($user->startup_stage), 'icon' => 'rocket'];
        }
        if ($user->is_pro) {
            $badges[] = ['id' => 'badge_pro', 'label' => 'Pro', 'icon' => 'sparkles'];
        }
        return $badges;
    }

    private function buildExperience(User $user): array
    {
        $exp = [];
        if ($user->position) {
            $exp[] = [
                'id'           => 'exp_' . substr(md5($user->id . '_position'), 0, 4),
                'title'        => $user->position,
                'organization' => $user->startup_name ?? 'ConnectX',
                'period'       => 'Current',
            ];
        }
        return $exp;
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
}
