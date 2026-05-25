<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Onboarding\OnboardingSession;
use App\Models\Tag;
use App\Models\Startup;
use App\Models\Builder;
use App\Services\OnboardingEngineService;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    protected $engine;

    public function __construct(OnboardingEngineService $engine)
    {
        $this->engine = $engine;
    }

    /**
     * Resolve session by ID — falls back to user's latest in_progress session
     * if the given ID is stale/missing (handles server restarts gracefully).
     */
    private function resolveSession(Request $request, string $sessionId): OnboardingSession
    {
        $user = $request->user();

        // Try exact session first
        $session = OnboardingSession::where('id', $sessionId)
            ->where('user_id', $user->id)
            ->first();

        // Fallback: user's latest in_progress session
        if (!$session) {
            $session = OnboardingSession::where('user_id', $user->id)
                ->where('status', 'in_progress')
                ->latest()
                ->first();
        }

        if (!$session) {
            abort(404, 'No active onboarding session found. Please start a new session.');
        }

        return $session;
    }

    /**
     * Resume onboarding — no session_id needed.
     * GET /api/v1/onboarding/resume
     *
     * Called by Frontend on app launch to check if user has an active session.
     * Returns the current step so user continues exactly where they left off.
     */
    public function resume(Request $request)
    {
        $user = $request->user();

        $session = OnboardingSession::where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->latest()
            ->first();

        // No active session — user needs to start fresh
        if (!$session) {
            return response()->json([
                'has_active_session' => false,
                'session_id'         => null,
                'current_step'       => null,
            ]);
        }

        return response()->json([
            'has_active_session' => true,
            'session_id'         => $session->id,
            'status'             => $session->status,
            'current_step'       => $this->engine->getCurrentStep($session),
        ]);
    }

    /**
     * Start onboarding session.
     * POST /api/v1/onboarding/sessions
     * Body: { "locale": "en" }   (optional, also via Accept-Language header)
     */
    public function start(Request $request)
    {
        $goal = $request->input('goal') ?? $request->query('goal');
        $session = $this->engine->startSession($request->user(), $goal);

        return response()->json([
            'session_id' => $session->id,
            'status' => $session->status,
            'current_step' => $this->engine->getCurrentStep($session),
        ], 201);
    }

    /**
     * Get current step (for resume or reload).
     * GET /api/v1/onboarding/sessions/{session}/current
     */
    public function current(Request $request, string $session)
    {
        $session = $this->resolveSession($request, $session);

        if ($session->status === 'completed') {
            return response()->json([
                'next_step' => null,
                'completed' => true,
                'profile_id' => $session->user_id,
                'redirect_to' => '/home',
            ]);
        }

        return response()->json([
            'current_step' => $this->engine->getCurrentStep($session),
        ]);
    }

    /**
     * Submit answer + get next step.
     * POST /api/v1/onboarding/sessions/{session}/answer
     * Body: { "step_id": "step_personal_name", "answers": { "q_first_name": "Dimas" } }
     */
    public function answer(Request $request, string $session)
    {
        $session = $this->resolveSession($request, $session);

        if ($session->status === 'completed') {
            return response()->json([
                'next_step' => null,
                'completed' => true,
                'profile_id' => $session->user_id,
                'redirect_to' => '/home',
            ]);
        }

        $request->validate([
            'step_id' => 'required|string',
            'answers' => 'required|array',
        ]);

        $result = $this->engine->processAnswer($session, $request->step_id, $request->answers);

        return response()->json($result);
    }

    /**
     * Go back to previous step.
     * POST /api/v1/onboarding/sessions/{session}/back
     */
    public function back(Request $request, string $session)
    {
        $session = $this->resolveSession($request, $session);

        if ($session->status === 'completed') {
            return response()->json([
                'error' => 'Session already completed'
            ], 400);
        }

        $result = $this->engine->goBack($session);

        if (!$result) {
            return response()->json([
                'error' => app()->getLocale() === 'id'
                    ? 'Tidak bisa mundur lebih jauh lagi'
                    : 'Cannot go back further'
            ], 400);
        }

        return response()->json($result);
    }

    /**
     * Get full session state (for debugging/admin).
     * GET /api/v1/onboarding/sessions/{session}
     */
    public function show(Request $request, string $session)
    {
        $session = $this->resolveSession($request, $session);

        $session->load('responses', 'currentStep');

        return response()->json([
            'session_id' => $session->id,
            'user_id' => $session->user_id,
            'status' => $session->status,
            'current_step_id' => $session->current_step_id,
            'started_at' => $session->started_at?->toISOString(),
            'completed_at' => $session->completed_at?->toISOString(),
            'total_responses' => $session->responses->count(),
            'responses' => $session->responses->map(function ($r) {
                return [
                    'step_id' => $r->step_id,
                    'question_id' => $r->question_id,
                    'value' => $r->value,
                    'answered_at' => $r->answered_at?->toISOString(),
                ];
            }),
        ]);
    }

    /**
     * GET /api/v1/onboarding/status
     * Check onboarding completion status.
     */
    public function status(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'is_onboarded' => (bool) $user->is_onboarded,
                'role_category' => $user->role_category,
                'registration_step' => $user->registration_step,
                'has_active_session' => OnboardingSession::where('user_id', $user->id)
                    ->where('status', 'in_progress')
                    ->exists(),
            ]
        ]);
    }

    /**
     * POST /api/v1/onboarding/role
     * Save user type: builder/startup
     */
    public function saveRole(Request $request)
    {
        $request->validate([
            'type' => 'required|in:builder,startup',
        ]);

        $user = $request->user();
        $type = $request->type;

        // If startup, we can set role_category immediately
        if ($type === 'startup') {
            $user->update(['role_category' => 'Startup']);
        } else {
            // For builder, we wait for builder-type to set the specific role
            // but we can store a temporary hint if needed
            $user->update(['role_category' => 'Builder']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Role saved successfully',
            'next_step' => $type === 'builder' ? 'builder-type' : 'preferences'
        ]);
    }

    /**
     * POST /api/v1/onboarding/builder-type
     * Save builder sub-type: founder/co-founder/team member
     */
    public function saveBuilderType(Request $request)
    {
        $request->validate([
            'type' => 'required|in:founder,co-founder,team member,cofounder,team_member',
        ]);

        $user = $request->user();
        $type = strtolower($request->type);

        $roleMap = [
            'founder' => 'Founder',
            'co-founder' => 'Co-Founder',
            'cofounder' => 'Co-Founder',
            'team member' => 'Team Member',
            'team_member' => 'Team Member',
        ];

        $user->update([
            'role_category' => $roleMap[$type] ?? 'Builder'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Builder type saved successfully',
            'next_step' => 'preferences'
        ]);
    }

    /**
     * POST /api/v1/onboarding/preferences
     * Save industries, skills, co-founder type, availability, location
     * + Startup-specific: tagline, stage, traction, links, commitment, offering
     */
    public function savePreferences(Request $request)
    {
        $request->validate([
            // Common fields
            'industries'        => 'nullable|array',
            'skills'            => 'nullable|array',
            'co_founder_type'   => 'nullable|string',
            'availability'      => 'nullable|string',
            'location'          => 'nullable|string',
            'latitude'          => 'nullable|numeric',
            'longitude'         => 'nullable|numeric',

            // Startup-specific fields
            'startup_name'      => 'nullable|string|max:255',
            'tagline'           => 'nullable|string|max:255',
            'stage'             => 'nullable|in:idea,mvp,pre_seed,seed,series_a',
            'industry'          => 'nullable|string',
            'secondary_industry'=> 'nullable|string',
            'description'       => 'nullable|string',
            'team_size'         => 'nullable|integer|min:1',
            'looking_for'       => 'nullable|array',

            // Traction (saved inside looking_for)
            'user_count'        => 'nullable|string',
            'mau'               => 'nullable|string',
            'revenue'           => 'nullable|string',

            // Links (saved inside looking_for)
            'website'           => 'nullable|url',
            'prototype_url'     => 'nullable|url',
            'instagram'         => 'nullable|string',
            'twitter'           => 'nullable|string',
            'linkedin'          => 'nullable|string',
            'tiktok'            => 'nullable|string',

            // Offering (saved inside looking_for)
            'commitment'        => 'nullable|string',
            'equity'            => 'nullable|string',
            'paid'              => 'nullable|boolean',

            // Open roles & looking for
            'open_roles'        => 'nullable|array',
        ]);

        $user = $request->user();

        $updateData = ['is_onboarded' => true];

        if ($request->has('co_founder_type')) {
            $updateData['cofounder_type'] = $request->co_founder_type;
        }
        if ($request->has('availability')) {
            $updateData['commitment_level'] = $request->availability;
        }
        if ($request->has('location')) {
            $locParts = explode(',', $request->location);
            $cityName = trim($locParts[0] ?? '');
            $countryName = trim($locParts[1] ?? '');
            
            $updateData['city']    = $cityName;
            $updateData['country'] = $countryName;

            if ($request->filled('latitude') && $request->filled('longitude')) {
                // Gunakan GPS dari frontend jika user accept
                $updateData['latitude']  = $request->latitude;
                $updateData['longitude'] = $request->longitude;
            } else {
                // HACK: Auto-Geocoding Gratis Pakai OpenStreetMap (Fallback)
                try {
                    $searchQuery = urlencode($cityName . ($countryName ? ', ' . $countryName : ''));
                    $response = \Illuminate\Support\Facades\Http::withHeaders([
                        'User-Agent' => 'ConnectX-App/1.0' // Wajib diisi agar tidak diblokir Nominatim
                    ])->timeout(5)->get("https://nominatim.openstreetmap.org/search?q={$searchQuery}&format=json&limit=1");

                    if ($response->successful() && !empty($response->json())) {
                        $data = $response->json()[0];
                        $updateData['latitude']  = $data['lat'] ?? null;
                        $updateData['longitude'] = $data['lon'] ?? null;
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("Gagal ambil koordinat OSM untuk kota: " . $cityName);
                }
            }
        }

        $user->update($updateData);

        // ── Sync Tags (Industries & Skills) ───────────────────────────────────────
        $tagNames = array_merge(
            $request->input('industries', []),
            $request->input('skills', [])
        );
        if (!empty($tagNames)) {
            $tagIds = Tag::whereIn('name', $tagNames)->pluck('id')->toArray();
            if (!empty($tagIds)) {
                $user->tags()->sync($tagIds);
            }
        }

        // ── Create/Update Builder or Startup record ───────────────────────────────
        $isStartupContext = in_array($user->role_category, ['Startup', 'Founder']);

        if ($isStartupContext) {

            // Build looking_for JSON — merge incoming nested object + individual fields
            $existingStartup = Startup::where('owner_id', $user->id)->first();
            $existingLookingFor = is_array($existingStartup?->looking_for) ? $existingStartup->looking_for : [];

            $newLookingFor = array_merge($existingLookingFor, array_filter([
                // Traction metrics
                'user_count'    => $request->user_count,
                'mau'           => $request->mau,
                'revenue'       => $request->revenue,

                // Links
                'website'       => $request->website,
                'prototype_url' => $request->prototype_url,
                'instagram'     => $request->instagram,
                'twitter'       => $request->twitter,
                'linkedin'      => $request->linkedin,
                'tiktok'        => $request->tiktok,

                // Offering & commitment
                'commitment'    => $request->commitment,
                'equity'        => $request->equity,
                'paid'          => $request->has('paid') ? (bool) $request->paid : null,
            ], fn ($v) => $v !== null));

            // If FE sends a whole looking_for object, merge it on top
            if ($request->has('looking_for') && is_array($request->looking_for)) {
                $newLookingFor = array_merge($newLookingFor, $request->looking_for);
            }

            Startup::updateOrCreate(
                ['owner_id' => $user->id],
                array_filter([
                    'name'               => $request->startup_name ?? ($user->startup_name ?? ($user->name . "'s Startup")),
                    'tagline'            => $request->tagline,
                    'stage'              => $request->stage,
                    'industry'           => $request->industry ?? ($request->input('industries.0')),
                    'secondary_industry' => $request->secondary_industry,
                    'description'        => $request->description,
                    'team_size'          => $request->team_size,
                    'open_roles'         => $request->open_roles,
                    'looking_for'        => $newLookingFor,
                    'city'               => $updateData['city'] ?? null,
                    'country'            => $updateData['country'] ?? null,
                    'latitude'           => $updateData['latitude'] ?? null,
                    'longitude'          => $updateData['longitude'] ?? null,
                ], fn ($v) => $v !== null)
            );
        }

        // Always create a Builder profile for everyone (including Founder/Startup) 
        // so they have a talent profile and can context-switch seamlessly.
        Builder::updateOrCreate(
            ['user_id' => $user->id],
            [
                'role_category'   => $user->role_category,
                'commitment_level'=> $user->commitment_level,
            ]
        );

        return response()->json([
            'success'     => true,
            'message'     => 'Preferences saved successfully. Onboarding complete.',
            'redirect_to' => '/home',
        ]);
    }
    /**
     * Search options for searchable_dropdown questions (e.g. Cities).
     * GET /api/v1/onboarding/options/search?question_id=q_city&q=Jakarta
     */
    public function searchOptions(Request $request)
    {
        $request->validate([
            'question_id' => 'required|string',
            'q' => 'nullable|string|min:2',
        ]);

        $options = $this->engine->searchOptions(
            $request->question_id,
            $request->q ?? ''
        );

        return response()->json([
            'options' => $options
        ]);
    }
}
