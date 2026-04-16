<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Onboarding\OnboardingSession;
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
     * Start onboarding session.
     * POST /api/v1/onboarding/sessions
     * Body: { "locale": "en" }   (optional, also via Accept-Language header)
     */
    public function start(Request $request)
    {
        $session = $this->engine->startSession($request->user());

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
    public function current(Request $request, OnboardingSession $session)
    {
        // Ensure session belongs to user
        if ($session->user_id !== $request->user()->id) {
            abort(403, __('messages.unauthorized'));
        }

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
    public function answer(Request $request, OnboardingSession $session)
    {
        if ($session->user_id !== $request->user()->id) {
            abort(403, __('messages.unauthorized'));
        }

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
    public function back(Request $request, OnboardingSession $session)
    {
        if ($session->user_id !== $request->user()->id) {
            abort(403, __('messages.unauthorized'));
        }

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
    public function show(Request $request, OnboardingSession $session)
    {
        if ($session->user_id !== $request->user()->id) {
            abort(403, __('messages.unauthorized'));
        }

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
}
