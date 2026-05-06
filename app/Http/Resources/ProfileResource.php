<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // ── Cek kepemilikan Startup ─────────────────────────────
        $hasStartup  = $this->relationLoaded('startup') && $this->startup !== null;
        $aboutKind   = $hasStartup ? 'startupIdea' : 'personalDescription';
        $aboutTitle  = $hasStartup ? 'Startup Idea' : 'Description';
        $aboutValue  = $hasStartup ? ($this->startup_idea ?? '') : ($this->bio ?? '');

        // ── Manipulasi Lokasi ──────────────────────────────────
        // ── Mapping Tags per Tipe ───────────────────────────────
        $hobbies   = [];
        $skills    = [];
        $interests = [];

        if ($this->relationLoaded('tags')) {
            $hobbies = $this->tags
                ->where('type', 'personality_hobbies')
                ->filter(fn($tag) => !empty($tag->code))
                ->map(fn($tag) => ['id' => $tag->code, 'name' => $tag->name])
                ->values()->all();

            $skills = $this->tags
                ->where('type', 'skill')
                ->filter(fn($tag) => !empty($tag->code))
                ->map(fn($tag) => ['id' => $tag->code, 'name' => $tag->name])
                ->values()->all();

            $interests = $this->tags
                ->where('type', 'industry')
                ->filter(fn($tag) => !empty($tag->code))
                ->map(fn($tag) => ['id' => $tag->code, 'name' => $tag->name])
                ->values()->all();
        }

        // ── Highlights: Computed dari user_credentials + user data ──
        $highlights = [];

        if ($this->relationLoaded('credentials')) {
            $cred = $this->credentials->where('provider', 'linkedin')->first();

            if ($cred) {
                // Tampilkan Job Terakhir — support both normalized keys and raw Apify keys
                $exp = collect($cred->experience ?? [])->first();
                if ($exp) {
                    $title   = $exp['title']       ?? $exp['position']    ?? '';
                    $company = $exp['company']      ?? $exp['companyName'] ?? '';
                    if (!empty($title) && !empty($company)) {
                        $highlights[] = $title . ' at ' . $company;
                    } elseif (!empty($title)) {
                        $highlights[] = $title;
                    } elseif (!empty($company)) {
                        $highlights[] = 'Worked at ' . $company;
                    }
                }

                // Tampilkan Pendidikan Terakhir — support both normalized keys and raw Apify keys
                $edu = collect($cred->education ?? [])->first();
                if ($edu) {
                    $degree = $edu['degree']     ?? '';
                    $school = $edu['school']     ?? $edu['schoolName'] ?? '';
                    if (!empty($degree) && !empty($school)) {
                        $highlights[] = $degree . ', ' . $school;
                    } elseif (!empty($degree)) {
                        $highlights[] = $degree;
                    } elseif (!empty($school)) {
                        $highlights[] = 'Studied at ' . $school;
                    }
                }
            }
        }


        if (!empty($this->languages)) {
            $highlights[] = $this->languages;
        }

        // ── Lokasi ──
        $city = $this->city;
        $country = $this->country;
        $locationDisplay = trim(($city ?? '') . ', ' . ($country ?? ''), ', ');

        // ── Startup Metadata (Optional) ──────────────────────────
        $startupData = null;
        $session = \App\Models\Onboarding\OnboardingSession::where('user_id', $this->id)
            ->where('status', 'completed')
            ->latest('completed_at')
            ->first();
        
        $getVal = null;
        if ($session) {
            $responses = \App\Models\Onboarding\OnboardingResponse::where('session_id', $session->id)->get()->keyBy('question_id');
            $getVal = fn($key) => isset($responses[$key]) ? (is_array($responses[$key]->value) ? ($responses[$key]->value[0] ?? null) : $responses[$key]->value) : null;
            
            // Fallback for location from onboarding if DB columns are empty
            if (empty($locationDisplay) || $locationDisplay === 'Location not set') {
                $locVal = $getVal('q_location');
                if ($locVal) {
                    $option = \Illuminate\Support\Facades\DB::table('onboarding_options')
                        ->where('question_id', 'q_location')
                        ->where('value', $locVal)
                        ->first();
                    if ($option) {
                        $labels = json_decode($option->label, true);
                        $locationDisplay = $labels['id'] ?? $labels['en'] ?? $locVal;
                    }
                }
            }
        }

        if (empty($locationDisplay)) {
            $locationDisplay = 'Location not set';
        }

        if ($hasStartup) {
            $startup = $this->startup;
            $links = [];
            $stageDetails = [];
            
            if ($session) {
                $stageValue = strtolower($startup->stage ?? '');
                
                $detailKeys = [];
                if ($stageValue === 'idea') {
                    $detailKeys = [
                        'q_su_tri_prototype' => 'Has prototype',
                        'q_su_tri_prototype_link' => 'Prototype link',
                        'q_su_tri_waitlist' => 'Waitlist size',
                        'q_su_tri_validation' => 'Validation methods'
                    ];
                }
                if ($stageValue === 'mvp') {
                    $detailKeys = [
                        'q_su_trm_users' => 'Users',
                        'q_su_trm_mau' => 'Monthly active users',
                        'q_su_trm_revenue' => 'Revenue',
                        'q_su_trm_growth' => 'Growth rate'
                    ];
                }
                if ($stageValue === 'live') {
                    $detailKeys = [
                        'q_su_trl_mrr' => 'MRR',
                        'q_su_trl_customers' => 'Live users',
                        'q_su_trl_retention' => 'Retention',
                        'q_su_trl_metrics' => 'Key metrics'
                    ];
                }
                if ($stageValue === 'scale') {
                    $detailKeys = [
                        'q_su_trs_funding' => 'Funding raised',
                        'q_su_trs_teamsize' => 'Team size',
                        'q_su_trs_arr' => 'ARR',
                        'q_su_trs_investors' => 'Investors'
                    ];
                }
                
                foreach ($detailKeys as $qId => $label) {
                    $val = $getVal($qId);
                    if ($val !== null && $val !== '') {
                        $stageDetails[] = ['id' => $qId, 'label' => $label, 'value' => $val];
                    }
                }

                $linkMappings = [
                    'q_su_website' => 'Website',
                    'q_su_linkedin' => 'LinkedIn',
                    'q_su_twitter' => 'Twitter / X',
                    'q_su_instagram' => 'Instagram',
                    'q_su_pitchdeck' => 'Pitch deck'
                ];
                foreach ($linkMappings as $qId => $label) {
                    $val = $getVal($qId);
                    if ($val) {
                        if (str_starts_with($val, '@')) {
                            if ($qId === 'q_twitter') $val = 'https://x.com/' . ltrim($val, '@');
                            elseif ($qId === 'q_instagram') $val = 'https://instagram.com/' . ltrim($val, '@');
                        }
                        $links[] = ['label' => $label, 'url' => $val];
                    }
                }

                // Fallback for aboutValue if empty (Startup path)
                if (empty($aboutValue)) {
                    $aboutValue = $getVal('q_su_problem') ?? $getVal('q_su_solution') ?? '';
                }
            }

            $getOnboardingLabel = function ($questionId, $value) {
                if (empty($value)) return $value;
                $option = \Illuminate\Support\Facades\DB::table('onboarding_options')
                    ->where('question_id', $questionId)
                    ->where('value', $value)
                    ->first();
                if ($option) {
                    $labels = json_decode($option->label, true);
                    return $labels['id'] ?? $labels['en'] ?? $value;
                }
                return ucwords(str_replace(['_', '-'], ' ', $value));
            };

            $industries = [];
            if (!empty($startup->industry)) {
                $industries[] = [
                    'id'   => $startup->industry,
                    'name' => $getOnboardingLabel('q_su_industry', $startup->industry)
                ];
            }
            if (!empty($startup->secondary_industry)) {
                $industries[] = [
                    'id'   => $startup->secondary_industry,
                    'name' => $getOnboardingLabel('q_su_industry', $startup->secondary_industry)
                ];
            }

            $startupData = [
                'name' => $startup->name ?? '',
                'tagline' => $startup->tagline ?? '',
                'stage' => [
                    'value' => $startup->stage ?? '',
                    'label' => $getOnboardingLabel('q_su_stage', $startup->stage),
                    'details' => $stageDetails
                ],
                'industries' => $industries,
                'links' => $links,
            ];
        }

        $response = [
            'id'          => $this->id,
            'teamId'      => $this->startup->id ?? 'no_team',
            'profileType' => $this->role_category ? strtolower($this->role_category) : 'builder',
            'name'        => $this->name,
            'headline'    => $this->position ?? ($hasStartup ? 'Startup Founder' : 'Professional'),
            'photoUrl'    => $this->avatar_url,
            'location'    => [
                'city'    => $this->city ?? ($city ?? ''),
                'country' => $this->country ?? ($country ?? ''),
                'display' => $locationDisplay,
            ],
            'stats' => [
                'connections' => $this->connections_count ?? 0,
                'teamsJoined' => $this->teams_joined_count ?? 0,
                'matches'     => $this->matches_count ?? 0,
            ],
            'badges' => []
        ];

        if ($hasStartup) {
            $response['startup'] = $startupData;
            $response['badges'][] = ['id' => 'startup-founder', 'label' => 'Startup Founder'];
        }

        $response['sections'] = [
            'about' => [
                'kind'  => $aboutKind,
                'title' => $aboutTitle,
                'value' => $aboutValue,
            ],
        ];

        // Hide personal sections for Startup profiles as per feedback
        if (!$hasStartup) {
            $response['sections']['personalityAndHobbies'] = [
                'title' => 'Personality & Hobbies',
                'items' => $hobbies,
            ];
            $response['sections']['skills'] = [
                'title' => 'Expertise',
                'items' => $skills,
            ];
            $response['sections']['interests'] = [
                'title' => 'Focus',
                'items' => $interests,
            ];
        }

        $response['sections']['highlights'] = [
            'items' => $highlights,
        ];

        $response['createdAt'] = $this->created_at ? $this->created_at->toIso8601String() : null;
        $response['updatedAt'] = $this->updated_at ? $this->updated_at->toIso8601String() : null;

        return $response;
    }
}
