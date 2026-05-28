<?php

namespace App\Services\Discovery;

use App\Models\Startup;
use App\Models\User;
use App\Models\Tag;
use App\Models\Onboarding\OnboardingOption;
use App\Services\Discovery\MatchmakingScoringService;
use App\Services\Discovery\VertexAiService;
use Carbon\Carbon;

class CardTransformerService
{
    private array $optionLabelCache = [];

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

        $industries = $this->buildIndustries($user);

        return [
            'entityType'   => 'profile',
            'id'           => "card_{$user->id}_{$index}",
            'profileId'    => $user->id,
            'photoUrl'     => $user->avatar_url,
            'avatarUrl'    => $user->avatar_url, // Legacy FE fallback
            'name'         => $user->name,
            'age'          => $age,
            'headline'     => $user->position,
            'position'     => $user->position,   // Legacy FE fallback
            'location'     => $locationBlock,
            'match'        => $matchBlock,
            'matchmaking'  => $matchBlock,       // Legacy FE fallback
            'badges'       => $this->buildBadges($user),
            'bio'          => $user->bio,
            'about'        => $user->bio,        // Legacy FE fallback
            'startupIdea'  => $user->startup_idea ?? null,
            'linkedinUrl'  => $user->linkedin_url ?? null,
            'industries'   => $industries,
            'industry'     => $user->industry ?? ($industries[0]['name'] ?? null),
            'interests'    => array_values(array_merge($industries, $this->buildInterests($user))),
            'skills'       => $this->buildSkills($user),
            'commitment'   => [
                'title' => 'Commitment',
                'value' => $user->commitment_level ?? ($user->builder->commitment_level ?? null),
                'label' => ($user->commitment_level ?? ($user->builder->commitment_level ?? null)) 
                    ? ucwords(str_replace('_', ' ', $user->commitment_level ?? ($user->builder->commitment_level ?? null))) 
                    : null,
            ],
            'role'         => $user->position ?? 'Developer',
            'experience'   => $this->buildExperience($user),
            'education'    => $this->buildEducation($user),
            'certifications' => [
                'title' => 'Certifications',
                'items' => $this->buildCertifications($user),
            ],
            'languages'    => [
                'title' => 'Languages',
                'items' => $this->buildLanguages($user),
            ],
            'socials'      => $this->buildSocialLinks($user),
            'sections'     => [
                'skills' => [
                    'title' => 'Expertise',
                    'items' => $this->buildSkills($user),
                ],
                'interests' => [
                    'title' => 'Focus',
                    'items' => array_merge($this->buildIndustries($user), $this->buildInterests($user)),
                ],
                'personalityAndHobbies' => [
                    'title' => 'Personality & Hobbies',
                    'items' => [],
                ],
                'languages' => [
                    'title' => 'Languages',
                    'items' => $this->buildLanguages($user),
                ],
                'highlights' => [
                    'items' => $matchResult['highlights'] ?? [],
                ],
            ],
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

        // Match Block
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

        // Industry Object
        $primaryLabel = $this->resolveLabel($startup->industry);
        $secondaryLabel = $this->resolveLabel($startup->secondary_industry);
        
        $industryDisplay = collect([$primaryLabel, $secondaryLabel])->filter()->implode(' · ');
        $industryBlock = [
            'primary'   => $primaryLabel ?? $startup->industry,
            'secondary' => $secondaryLabel ?? $startup->secondary_industry ?? null,
            'display'   => $industryDisplay ?: null,
        ];

        // TeamStage Object
        $openRoles = is_array($startup->open_roles) ? $startup->open_roles : [];
        $stageMap = [
            'idea'     => 'Idea',
            'mvp'      => 'MVP',
            'pre_seed' => 'Pre-Seed',
            'seed'     => 'Seed',
            'series_a' => 'Series A'
        ];
        $stageValue = $startup->stage ? strtolower($startup->stage) : 'idea';
        $stageLabel = $stageMap[$stageValue] ?? ucwords(str_replace('_', ' ', $startup->stage ?? 'Idea'));

        $teamStageBlock = [
            'teamSize'    => $startup->team_size ?? 1,
            'stage'       => $stageLabel,
            'industry'    => $industryDisplay,
            'hiringCount' => count($openRoles)
        ];

        // Journey Object
        $journeyBlock = $this->buildJourney($startup->stage);

        // --- Founders, Team, Roles ---
        $members = $startup->relationLoaded('members') ? $startup->members : collect();
        $founderCount    = $members->where('role_id', 'founder')->count() ?: 1;
        $rolesCovered    = $members->pluck('role_id')->filter()->unique()->values()->toArray();

        $missingRoles = array_values(array_diff(
            array_map('strtolower', $openRoles),
            array_map('strtolower', $rolesCovered)
        ));

        // --- CON-75 Specific Fields ---
        $problem = $owner ? $this->getOnboardingValue($owner, 'q_problem') : null;
        $solution = $owner ? $this->getOnboardingValue($owner, 'q_solution') : null;
        $targetUsers = $owner ? $this->getOnboardingValue($owner, 'q_target_users') : null;

        $descriptionBlock = [
            'intro' => $startup->tagline ?? $startup->description,
            'problem' => is_array($problem) ? implode(', ', $problem) : $problem,
            'solution' => is_array($solution) ? implode(', ', $solution) : $solution,
            'targetUsers' => is_array($targetUsers) ? implode(', ', $targetUsers) : $targetUsers,
        ];

        $interests = $owner ? $this->getOnboardingValuesAsLabels($owner, ['q_industries_interest']) : [];
        
        $workArrangementRaw = $owner ? $this->getOnboardingValue($owner, 'q_work_arrangement') : null;
        $workArrangement = [];
        if (is_array($workArrangementRaw)) {
             foreach($workArrangementRaw as $wa) {
                 $workArrangement[] = ['id' => $wa, 'label' => ucwords(str_replace('_', ' ', $wa))];
             }
        } elseif (is_string($workArrangementRaw)) {
             $workArrangement[] = ['id' => $workArrangementRaw, 'label' => ucwords(str_replace('_', ' ', $workArrangementRaw))];
        } else {
             $workArrangement = [
                ['id' => 'remote', 'label' => 'Remote'],
                ['id' => 'hybrid', 'label' => 'Hybrid']
             ];
        }

        $openRolesList = [];
        foreach ($openRoles as $idx => $role) {
             $openRolesList[] = [
                 'id' => "startup_{$startup->id}_{$idx}",
                 'title' => ucwords(str_replace('_', ' ', $role)),
             ];
        }

        $lookingFor = is_array($startup->looking_for) ? $startup->looking_for : [];
        $lookingForDisplay = [];
        if (!empty($lookingFor['commitment'])) {
            $lookingForDisplay[] = ucwords(str_replace('_', ' ', $lookingFor['commitment']));
        }
        if (empty($lookingForDisplay)) {
             $lookingForDisplay = ["Co-Founder", "Team members"];
        }

        // --- Premium Block ---
        $premiumBlock = [
            'locked' => !$isPro,
            'unlockMessage' => 'Upgrade to premium to see all information.',
            'fields' => [
                'traction' => [
                    'locked' => !$isPro,
                    'label' => 'Traction',
                    'preview' => !$isPro ? 'User metrics and growth details' : null,
                ],
                'links' => [
                    'locked' => !$isPro,
                    'label' => 'Website & social links',
                    'preview' => !$isPro ? 'Website, LinkedIn, X, Instagram, pitch deck' : null,
                ],
                'teamComposition' => [
                    'locked' => !$isPro,
                    'label' => 'Team composition',
                    'preview' => !$isPro ? 'Founder setup and joined team details' : null,
                ],
                'compensation' => [
                    'locked' => !$isPro,
                    'label' => 'Equity & salary',
                    'preview' => !$isPro ? 'Compensation expectations and offer details' : null,
                ],
            ],
        ];

        if ($isPro) {
            unset($premiumBlock['fields']['traction']['preview']);
            $premiumBlock['fields']['traction']['value'] = $this->buildPremiumTraction($startup, $owner);

            unset($premiumBlock['fields']['links']['preview']);
            $premiumBlock['fields']['links']['value'] = $this->buildPremiumLinks($startup, $owner);

            unset($premiumBlock['fields']['teamComposition']['preview']);
            $premiumBlock['fields']['teamComposition']['value'] = $this->buildPremiumTeamComposition($startup, $owner, $founderCount, $rolesCovered);

            unset($premiumBlock['fields']['compensation']['preview']);
            $premiumBlock['fields']['compensation']['value'] = $this->buildPremiumCompensation($lookingFor);
        }

        // For backward compatibility
        $commitmentLabel = $lookingFor['commitment'] ?? null;
        $commitmentBlock = [
            'value' => $commitmentLabel,
            'label' => $commitmentLabel ? ucwords(str_replace('_', ' ', $commitmentLabel)) : 'Open to discuss',
        ];

        $offeringBlock = [
            'equity'  => $lookingFor['equity'] ?? null,
            'paid'    => $lookingFor['paid'] ?? null,
            'label'   => $this->buildOfferingLabel($lookingFor),
        ];

        return [
            'entityType'    => 'startup',
            'id'            => "card_startup_{$startup->id}_{$index}",
            'startupId'     => $startup->id,
            'name'          => $startup->name,
            'logoUrl'       => $startup->logo_url,
            'badge'         => ['label' => $startup->stage ? strtoupper($startup->stage) : null],
            'businessStage' => [
                'value' => $stageValue,
                'label' => $stageLabel
            ],
            'description'   => $descriptionBlock,
            'industry'      => $industryBlock,
            'interests'     => $interests,
            'workArrangement' => $workArrangement,
            'founder'       => $owner ? [
                'name'  => $owner->name,
                'title' => $owner->position ?? 'Founder',
            ] : null,
            'match'         => $matchBlock,
            'team'          => [
                'memberCount'    => $startup->team_size ?? 1,
                'display'        => ($startup->team_size ?? 1) . ' member' . (($startup->team_size ?? 1) > 1 ? 's' : ''),
            ],
            'summary'       => $startup->description ?? $startup->tagline ?? '',
            'openRoles'     => $openRolesList,
            'lookingFor'    => $lookingForDisplay,
            'premium'       => $premiumBlock,
            'teamStage'     => $teamStageBlock,
            'journey'       => $journeyBlock,

            // Legacy compatibility fields
            'tagline'       => $startup->tagline,
            'missingRoles'  => $missingRoles,
            'commitment'    => $commitmentBlock,
            'offering'      => $offeringBlock,
            'traction'      => $this->buildTraction($startup),
            'links'         => $this->buildStartupLinks($startup, $owner),
            'sections'      => [
                'team' => [
                    'title' => 'Team & Stage',
                    'items' => array_map(fn ($r) => ['id' => $r, 'name' => ucwords(str_replace('_', ' ', $r))], $rolesCovered),
                ],
                'lookingFor' => [
                    'title' => 'Looking For',
                    'items' => array_map(fn ($r) => ['id' => $r, 'name' => ucwords(str_replace('_', ' ', $r))], $openRoles),
                ],
                'missingSkills' => [
                    'title' => 'Skills Needed',
                    'items' => array_map(fn ($r) => ['id' => $r, 'name' => ucwords(str_replace('_', ' ', $r))], $missingRoles),
                ],
                'highlights' => [
                    'items' => $matchResult['highlights'] ?? [],
                ],
            ],
        ];
    }

    // ─── Sub-builders ─────────────────────────────────────────────────────────

    /**
     * Build badge pills for the profile card.
     * Each badge: { id, label, color, icon }
     */
    private function buildBadges(User $user): array
    {
        $badges = [];

        // 1. ConnectX Pro badge
        if ($user->is_pro) {
            $badges[] = [
                'id'    => 'pro',
                'label' => 'ConnectX Pro',
                'color' => '#F59E0B', // amber
                'icon'  => 'star',
            ];
        }

        // 2. LinkedIn Verified — has credentials synced from LinkedIn
        if ($user->relationLoaded('credentials') && $user->credentials->isNotEmpty()) {
            $badges[] = [
                'id'    => 'linkedin_verified',
                'label' => 'LinkedIn Verified',
                'color' => '#0A66C2', // LinkedIn blue
                'icon'  => 'linkedin',
            ];
        }

        // 3. New Member — joined within the last 14 days
        if ($user->created_at && \Carbon\Carbon::parse($user->created_at)->diffInDays(now()) <= 14) {
            $badges[] = [
                'id'    => 'new_member',
                'label' => 'New Member',
                'color' => '#10B981', // emerald
                'icon'  => 'sparkle',
            ];
        }

        // 4. Active Today — last seen within the last 24 hours
        if ($user->last_active_at && \Carbon\Carbon::parse($user->last_active_at)->diffInHours(now()) <= 24) {
            $badges[] = [
                'id'    => 'active_today',
                'label' => 'Active Today',
                'color' => '#6366F1', // indigo
                'icon'  => 'bolt',
            ];
        }

        // 5. Top Builder — has 5+ skills OR 3+ work experiences
        $skillCount = $user->relationLoaded('tags')
            ? $user->tags->where('type', 'skill')->count()
            : 0;
        $expCount = ($user->relationLoaded('credentials') && $user->credentials->isNotEmpty())
            ? count($user->credentials->first()?->experience ?? [])
            : 0;

        if ($skillCount >= 5 || $expCount >= 3) {
            $badges[] = [
                'id'    => 'top_builder',
                'label' => 'Top Builder',
                'color' => '#EC4899', // pink
                'icon'  => 'trophy',
            ];
        }

        return $badges;
    }

    /**
     * Helper to resolve a single slug value to its human-readable label using OnboardingOption.
     */
    private function resolveLabel(?string $slug): ?string
    {
        if (!$slug) return null;

        if (isset($this->optionLabelCache[$slug])) {
            return $this->optionLabelCache[$slug];
        }

        $opt = OnboardingOption::where('value', $slug)->first(['value', 'label']);
        if ($opt) {
            $labelArray = is_array($opt->label) ? $opt->label : json_decode($opt->label, true);
            $labelStr = $labelArray['en'] ?? $labelArray['id'] ?? $opt->value;
            $this->optionLabelCache[$slug] = $labelStr;
            return $labelStr;
        }

        // Fallback: hapus underscore dan kapitalisasi
        return ucwords(str_replace('_', ' ', $slug));
    }


    private function buildSkills(User $user): array
    {
        return $this->getOnboardingValuesAsLabels($user, ['q_fdr_skills', 'q_tm_skills', 'q_cf_skills', 'q_js_skills', 'q_bld_role', 'q_fdr_bt_roles']);
    }

    private function buildInterests(User $user): array
    {
        return $this->getOnboardingValuesAsLabels($user, ['q_personal_interests', 'q_su_interests']);
    }

    private function buildIndustries(User $user): array
    {
        return $this->getOnboardingValuesAsLabels($user, ['q_fdr_industry', 'q_tm_industry', 'q_cf_industry', 'q_js_industry', 'q_industries_interest']);
    }

    /**
     * Helper to extract values from onboarding responses and map them to their labels.
     */
    private function getOnboardingValuesAsLabels(User $user, array $questionIds): array
    {
        if (!$user->relationLoaded('onboardingSession') || !$user->onboardingSession) {
            return [];
        }

        $session = $user->onboardingSession;
        if (!$session->relationLoaded('responses')) {
            return [];
        }

        // Get all response values for the matching question IDs
        $values = [];
        foreach ($session->responses as $response) {
            if (in_array($response->question_id, $questionIds)) {
                $val = $response->value;
                if (is_array($val)) {
                    $values = array_merge($values, $val);
                } elseif (is_string($val)) {
                    $values[] = $val;
                }
            }
        }

        $values = array_unique(array_filter($values));
        if (empty($values)) {
            return [];
        }

        // Map values to labels
        $labels = [];
        $valuesToFetch = [];

        foreach ($values as $val) {
            if (isset($this->optionLabelCache[$val])) {
                $labels[$val] = $this->optionLabelCache[$val];
            } else {
                $valuesToFetch[] = $val;
            }
        }

        if (!empty($valuesToFetch)) {
            $options = OnboardingOption::whereIn('value', $valuesToFetch)->get(['value', 'label']);
            foreach ($options as $opt) {
                // Determine label string from JSON. Fallback to English 'en' or first available.
                $labelArray = is_array($opt->label) ? $opt->label : json_decode($opt->label, true);
                $labelStr = $labelArray['en'] ?? $labelArray['id'] ?? $opt->value;
                
                $this->optionLabelCache[$opt->value] = $labelStr;
                $labels[$opt->value] = $labelStr;
            }
        }

        $result = [];
        foreach ($labels as $id => $name) {
            $result[] = ['id' => $id, 'name' => $name];
        }

        return $result;
    }

    private function buildCertifications(User $user): array
    {
        if (!$user->relationLoaded('credentials')) return [];

        $allCerts = [];
        foreach ($user->credentials as $cred) {
            $raw   = $cred->raw_data;
            $data  = is_array($raw) ? $raw : (is_string($raw) ? json_decode($raw, true) : []);
            $certs = $data['certifications'] ?? [];

            if (!is_array($certs) || empty($certs)) continue;

            foreach ($certs as $cert) {
                if (!is_array($cert)) continue;

                // Logo: issuedByLogo can be {url, sizes[]} or just a string
                $logoUrl = null;
                $logo = $cert['issuedByLogo'] ?? null;
                if (is_array($logo)) {
                    $logoUrl = $logo['url']
                        ?? $logo['sizes'][0]['url']
                        ?? null;
                } elseif (is_string($logo)) {
                    $logoUrl = $logo;
                }

                $allCerts[] = [
                    'name'    => $cert['title'] ?? $cert['name'] ?? 'Certification',
                    'issuer'  => $cert['issuedBy'] ?? '',
                    'logoUrl' => $logoUrl,
                    'date'    => $cert['issuedAt'] ?? $cert['date'] ?? null,
                    'link'    => $cert['link'] ?? null,
                ];
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

        // 2. Cek dari Tags (tipe language)
        if ($user->relationLoaded('tags')) {
            $tagLangs = $user->tags->where('type', 'language')->pluck('name')->toArray();
            if (!empty($tagLangs)) {
                return array_values($tagLangs);
            }
        }

        // 3. Fallback: dari raw_data LinkedIn scrape
        if ($user->relationLoaded('credentials')) {
            foreach ($user->credentials as $cred) {
                $raw = $cred->raw_data;
                $data = is_array($raw) ? $raw : (is_string($raw) ? json_decode($raw, true) : []);
                $langs = $data['languages'] ?? [];

                if (!empty($langs) && is_array($langs)) {
                    // Format LinkedIn: [{name, proficiency}] → ambil nama saja
                    return array_values(array_filter(array_map(
                        fn($l) => $l['name'] ?? null,
                        $langs
                    )));
                }
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
        // 1. Try credentials raw_data first (LinkedIn scrape)
        if ($user->relationLoaded('credentials')) {
            foreach ($user->credentials as $cred) {
                $raw  = $cred->raw_data;
                $data = is_array($raw) ? $raw : (is_string($raw) ? json_decode($raw, true) : []);
                $edu  = $data['education'] ?? [];

                if (!empty($edu) && is_array($edu)) {
                    return array_values(array_map(function ($e) {
                        return [
                            'degree'     => $e['degree'] ?? '',
                            'school'     => $e['schoolName'] ?? $e['school'] ?? '',
                            'schoolLogo' => $e['schoolLogo']['url'] ?? $e['schoolLogo']['sizes'][0]['url'] ?? null,
                            'period'     => $e['period'] ?? null,
                            'field'      => $e['fieldOfStudy'] ?? null,
                        ];
                    }, $edu));
                }
            }
        }

        // 2. Fallback: users.education column
        $col = $user->education;
        return is_array($col) ? $col : [];
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

    /**
     * Build traction block — fields shown depend on startup stage.
     * Idea: no numbers needed. MVP+: prototype link, early testers.
     * Pre-Seed+: MAU, revenue signals.
     */
    private function buildTraction(Startup $startup): array
    {
        $stage = strtolower($startup->stage ?? 'idea');
        $lookingFor = is_array($startup->looking_for) ? $startup->looking_for : [];

        $traction = ['stage' => $stage, 'items' => []];

        // Only show traction data if stage is mvp or beyond
        if (in_array($stage, ['mvp', 'pre_seed', 'seed', 'series_a'])) {
            if (!empty($lookingFor['user_count'])) {
                $traction['items'][] = [
                    'label' => 'Users',
                    'value' => $lookingFor['user_count'],
                    'icon'  => 'users',
                ];
            }
            if (!empty($lookingFor['mau'])) {
                $traction['items'][] = [
                    'label' => 'MAU',
                    'value' => $lookingFor['mau'],
                    'icon'  => 'chart',
                ];
            }
            if (!empty($lookingFor['revenue'])) {
                $traction['items'][] = [
                    'label' => 'Revenue',
                    'value' => $lookingFor['revenue'],
                    'icon'  => 'dollar',
                ];
            }
        }

        // Team size always shown
        if ($startup->team_size) {
            $traction['items'][] = [
                'label' => 'Team Size',
                'value' => $startup->team_size . ' member' . ($startup->team_size > 1 ? 's' : ''),
                'icon'  => 'team',
            ];
        }

        return $traction;
    }

    /**
     * Build links block — which links are relevant depends on stage.
     * Idea stage: no website needed. MVP: prototype link. Seed+: full web + socials.
     */
    private function buildStartupLinks(Startup $startup, ?User $owner): array
    {
        $stage = strtolower($startup->stage ?? 'idea');
        $lookingFor = is_array($startup->looking_for) ? $startup->looking_for : [];
        $links = [];

        // Website — only show if MVP or beyond AND filled
        if (in_array($stage, ['mvp', 'pre_seed', 'seed', 'series_a'])) {
            if (!empty($lookingFor['website'])) {
                $links[] = ['type' => 'website', 'url' => $lookingFor['website'], 'label' => 'Website'];
            }
        }

        // Prototype link — show from MVP stage
        if (in_array($stage, ['mvp', 'pre_seed', 'seed', 'series_a'])) {
            if (!empty($lookingFor['prototype_url'])) {
                $links[] = ['type' => 'prototype', 'url' => $lookingFor['prototype_url'], 'label' => 'Try Prototype'];
            }
        }

        // Social media — always show if filled
        foreach (['instagram', 'twitter', 'linkedin', 'tiktok'] as $platform) {
            if (!empty($lookingFor[$platform])) {
                $links[] = ['type' => $platform, 'url' => $lookingFor[$platform], 'label' => ucfirst($platform)];
            }
        }

        // Founder LinkedIn — always show if available
        if ($owner && !empty($owner->linkedin_url)) {
            $links[] = ['type' => 'founder_linkedin', 'url' => $owner->linkedin_url, 'label' => 'Founder LinkedIn'];
        }

        return $links;
    }

    /**
     * Build a human-readable offering label from looking_for data.
     * e.g. "Equity: 1-5% · Paid"
     */
    private function buildOfferingLabel(array $lookingFor): string
    {
        $parts = [];

        if (!empty($lookingFor['equity'])) {
            $parts[] = 'Equity: ' . $lookingFor['equity'];
        }

        if (isset($lookingFor['paid'])) {
            $parts[] = $lookingFor['paid'] ? 'Paid' : 'Unpaid';
        }

        return implode(' · ', $parts) ?: 'Open to discuss';
    }

    private function getOnboardingValue(User $user, string $questionId)
    {
        if (!$user->relationLoaded('onboardingSession') || !$user->onboardingSession) {
            return null;
        }

        $session = $user->onboardingSession;
        if (!$session->relationLoaded('responses')) {
            return null;
        }

        foreach ($session->responses as $response) {
            if ($response->question_id === $questionId) {
                return $response->value;
            }
        }

        return null;
    }

    private function buildPremiumTraction(Startup $startup, ?User $owner): array
    {
        $stage = strtolower($startup->stage ?? 'idea');
        $items = [];

        if ($stage === 'idea') {
            $hasProto = $owner ? $this->getOnboardingValue($owner, 'q_has_prototype') : null;
            $items[] = ['id' => 'q_has_prototype', 'label' => 'Has Prototype', 'value' => $hasProto ? 'Yes' : 'No'];
            $items[] = ['id' => 'q_waitlist_size', 'label' => 'Waitlist', 'value' => $owner ? $this->getOnboardingValue($owner, 'q_waitlist_size') : null];
            $items[] = ['id' => 'q_validation_methods', 'label' => 'Validation', 'value' => $owner ? $this->getOnboardingValue($owner, 'q_validation_methods') : null];
        } elseif ($stage === 'mvp') {
            $items[] = ['id' => 'q_user_count', 'label' => 'Users', 'value' => $owner ? $this->getOnboardingValue($owner, 'q_user_count') : null];
            $items[] = ['id' => 'q_mau', 'label' => 'MAU', 'value' => $owner ? $this->getOnboardingValue($owner, 'q_mau') : null];
            $items[] = ['id' => 'q_mvp_revenue', 'label' => 'Revenue', 'value' => $owner ? $this->getOnboardingValue($owner, 'q_mvp_revenue') : null];
            $items[] = ['id' => 'q_growth_rate', 'label' => 'Growth Rate', 'value' => $owner ? $this->getOnboardingValue($owner, 'q_growth_rate') : null];
        } elseif ($stage === 'live' || $stage === 'seed') {
            $items[] = ['id' => 'q_mrr', 'label' => 'MRR', 'value' => $owner ? $this->getOnboardingValue($owner, 'q_mrr') : null];
            $items[] = ['id' => 'q_live_users', 'label' => 'Active Users', 'value' => $owner ? $this->getOnboardingValue($owner, 'q_live_users') : null];
            $items[] = ['id' => 'q_retention', 'label' => 'Retention', 'value' => $owner ? $this->getOnboardingValue($owner, 'q_retention') : null];
            $items[] = ['id' => 'q_funding_raised', 'label' => 'Funding', 'value' => $owner ? $this->getOnboardingValue($owner, 'q_funding_raised') : null];
        } else {
            $items[] = ['id' => 'team_size', 'label' => 'Team Size', 'value' => $startup->team_size];
        }

        // Clean up nulls
        $items = array_filter($items, fn($item) => $item['value'] !== null);

        return [
            'stage' => $stage,
            'items' => array_values($items)
        ];
    }

    private function buildPremiumLinks(Startup $startup, ?User $owner): array
    {
        $links = [];
        
        $website = $owner ? $this->getOnboardingValue($owner, 'q_website') : null;
        if ($website) $links[] = ['label' => 'Website', 'url' => is_array($website) ? $website[0] : $website];

        $linkedin = $owner ? $this->getOnboardingValue($owner, 'q_startup_linkedin') : null;
        if ($linkedin) $links[] = ['label' => 'LinkedIn', 'url' => is_array($linkedin) ? $linkedin[0] : $linkedin];

        $twitter = $owner ? $this->getOnboardingValue($owner, 'q_twitter') : null;
        if ($twitter) $links[] = ['label' => 'X', 'url' => is_array($twitter) ? $twitter[0] : $twitter];

        $instagram = $owner ? $this->getOnboardingValue($owner, 'q_instagram') : null;
        if ($instagram) $links[] = ['label' => 'Instagram', 'url' => is_array($instagram) ? $instagram[0] : $instagram];

        $pitch = $owner ? $this->getOnboardingValue($owner, 'q_pitch_deck') : null;
        if ($pitch) $links[] = ['label' => 'Pitch deck', 'url' => is_array($pitch) ? $pitch[0] : $pitch];

        return $links;
    }

    private function buildPremiumTeamComposition(Startup $startup, ?User $owner, int $founderCount, array $rolesCovered): array
    {
        $fCount = $owner ? $this->getOnboardingValue($owner, 'q_founder_count') : null;
        if (is_array($fCount)) $fCount = $fCount[0];
        
        $fCountLabel = $fCount;
        if ($fCount === 'solo') $fCountLabel = 'Solo Founder';
        if ($fCount === 'two') $fCountLabel = '2 Founders';
        if ($fCount === 'three_plus') $fCountLabel = '3+ Founders';

        $hasTeamRaw = $owner ? $this->getOnboardingValue($owner, 'q_has_team') : null;
        $hasTeam = false;
        if ($hasTeamRaw === 'yes' || $hasTeamRaw === true || $startup->team_size > 1) {
             $hasTeam = true;
        }

        $teamSize = $owner ? $this->getOnboardingValue($owner, 'q_team_size') : $startup->team_size;
        $teamRolesRaw = $owner ? $this->getOnboardingValue($owner, 'q_team_roles') : $rolesCovered;

        $teamRoles = [];
        if (is_array($teamRolesRaw)) {
             foreach ($teamRolesRaw as $tr) {
                  $teamRoles[] = ucwords(str_replace('_', ' ', $tr));
             }
        } elseif (is_string($teamRolesRaw)) {
             $teamRoles[] = ucwords(str_replace('_', ' ', $teamRolesRaw));
        }

        return [
            'founderCount' => $fCount ?? 'solo',
            'founderCountLabel' => $fCountLabel ?? '1 Founder',
            'coveredRoles' => array_map(fn($r) => ucwords(str_replace('_', ' ', $r)), $rolesCovered),
            'hasTeam' => $hasTeam,
            'teamSize' => is_array($teamSize) ? ($teamSize[0] ?? null) : $teamSize,
            'teamRoles' => $teamRoles,
            'joinedMemberCount' => $startup->team_size ?? 1,
        ];
    }

    private function buildPremiumCompensation(array $lookingFor): array
    {
        $equityRange = $lookingFor['equity'] ?? null;
        $salaryAvailable = $lookingFor['paid'] ?? false;
        $salaryRange = $lookingFor['salary_range'] ?? null;

        return [
            'equityAvailable' => !empty($equityRange),
            'equityRange' => $equityRange,
            'salaryAvailable' => (bool) $salaryAvailable,
            'salaryRange' => $salaryRange,
            'notes' => $lookingFor['compensation_notes'] ?? null,
        ];
    }
}
