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
        $locationDisplay = trim(($this->city ?? '') . ', ' . ($this->country ?? ''), ', ');
        if (empty($locationDisplay)) {
            $locationDisplay = 'Location not set';
        }

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
                // Tampilkan Job Terakhir secara fleksibel
                $exp = collect($cred->experience ?? [])->first();
                if ($exp) {
                    $title = $exp['title'] ?? '';
                    $company = $exp['company'] ?? '';
                    if (!empty($title) && !empty($company)) {
                        $highlights[] = $title . ' at ' . $company;
                    } elseif (!empty($title)) {
                        $highlights[] = $title;
                    } elseif (!empty($company)) {
                        $highlights[] = 'Worked at ' . $company;
                    }
                }

                // Tampilkan Pendidikan Terakhir secara fleksibel
                $edu = collect($cred->education ?? [])->first();
                if ($edu) {
                    $degree = $edu['degree'] ?? '';
                    $school = $edu['school'] ?? '';
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

        // ── Startup Metadata (Optional) ──────────────────────────
        $startupData = null;
        if ($hasStartup) {
            $startup = $this->startup;

            $session = \App\Models\Onboarding\OnboardingSession::where('user_id', $this->id)
                ->where('status', 'completed')
                ->latest('completed_at')
                ->first();

            $links = [];
            $stageDetails = [];
            
            if ($session) {
                $responses = \App\Models\Onboarding\OnboardingResponse::where('session_id', $session->id)->get()->keyBy('question_id');
                
                $getVal = fn($key) => isset($responses[$key]) ? (is_array($responses[$key]->value) ? ($responses[$key]->value[0] ?? null) : $responses[$key]->value) : null;
                
                $stageValue = strtolower($startup->stage ?? '');
                
                $detailKeys = [];
                if ($stageValue === 'idea') $detailKeys = ['q_has_prototype' => 'Has prototype', 'q_prototype_link' => 'Prototype link', 'q_waitlist_size' => 'Waitlist size', 'q_validation_methods' => 'Validation methods'];
                if ($stageValue === 'mvp') $detailKeys = ['q_user_count' => 'Users', 'q_mau' => 'Monthly active users', 'q_mvp_revenue' => 'Revenue', 'q_growth_rate' => 'Growth rate'];
                if ($stageValue === 'live') $detailKeys = ['q_mrr' => 'MRR', 'q_live_users' => 'Live users', 'q_retention' => 'Retention', 'q_key_metrics' => 'Key metrics'];
                if ($stageValue === 'scale') $detailKeys = ['q_funding_raised' => 'Funding raised', 'q_scale_team_size' => 'Team size', 'q_arr' => 'ARR', 'q_investors' => 'Investors'];
                
                foreach ($detailKeys as $qId => $label) {
                    $val = $getVal($qId);
                    if ($val !== null && $val !== '') {
                        $stageDetails[] = ['id' => $qId, 'label' => $label, 'value' => $val];
                    }
                }

                $linkMappings = [
                    'q_website' => 'Website',
                    'q_startup_linkedin' => 'LinkedIn',
                    'q_twitter' => 'Twitter / X',
                    'q_instagram' => 'Instagram',
                    'q_pitch_deck' => 'Pitch deck'
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
            }

            $industries = [];
            if (!empty($startup->industry)) {
                $industries[] = ['id' => \Illuminate\Support\Str::slug($startup->industry), 'name' => $startup->industry];
            }
            if (!empty($startup->secondary_industry)) {
                $industries[] = ['id' => \Illuminate\Support\Str::slug($startup->secondary_industry), 'name' => $startup->secondary_industry];
            }

            $startupData = [
                'name' => $startup->name ?? '',
                'tagline' => $startup->tagline ?? '',
                'stage' => [
                    'value' => $startup->stage ?? '',
                    'label' => ucfirst($startup->stage ?? ''),
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
            'headline'    => $this->position ?? 'Professional',
            'photoUrl'    => $this->avatar_url,
            'location'    => [
                'city'    => $this->city ?? '',
                'country' => $this->country ?? '',
                'display' => $locationDisplay,
            ],
            'stats' => [
                'connections' => $this->connections_count ?? 0,
                'teamsJoined' => $this->teams_joined_count ?? 0,
                'matches'     => $this->matches_count ?? 0,
            ],
            'badges' => [
                ['id' => 'connectx-user', 'label' => 'ConnectX User']
            ]
        ];

        if ($hasStartup) {
            $response['startup'] = $startupData;
        }

        $response['sections'] = [
            'about' => [
                'kind'  => $aboutKind,
                'title' => $aboutTitle,
                'value' => $aboutValue,
            ],
            'personalityAndHobbies' => [
                'title' => 'Personality & Hobbies',
                'items' => $hobbies,
            ],
            'skills' => [
                'title' => 'Skills',
                'items' => $skills,
            ],
            'interests' => [
                'title' => 'Interests',
                'items' => $interests,
            ],
            'highlights' => [
                'items' => $highlights,
            ],
        ];

        $response['createdAt'] = $this->created_at ? $this->created_at->toIso8601String() : null;
        $response['updatedAt'] = $this->updated_at ? $this->updated_at->toIso8601String() : null;

        return $response;
    }
}
