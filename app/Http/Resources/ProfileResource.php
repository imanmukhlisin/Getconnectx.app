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
                $expCount = count($cred->experience ?? []);
                if ($expCount > 0) {
                    $highlights[] = $expCount . '+ years startup experience';
                }
                $edu = collect($cred->education ?? [])->first();
                if ($edu && !empty($edu['degree']) && !empty($edu['school'])) {
                    $highlights[] = $edu['degree'] . ', ' . $edu['school'];
                }
            }
        }

        if (!empty($this->languages)) {
            $highlights[] = $this->languages;
        }

        return [
            'id'          => $this->id,
            'teamId'      => $this->startup->id ?? 'no_team',
            'profileType' => $this->role_category ?? 'builder',
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
            ],
            'sections' => [
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
            ],
            'createdAt' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updatedAt' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
            'isLinkedInSynced' => !empty($this->linkedin_url) || ($this->relationLoaded('credentials') && $this->credentials->where('provider', 'linkedin')->isNotEmpty()),
        ];
    }
}
