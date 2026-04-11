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

    public function start(Request $request)
    {
        // Request validation could include 'locale'
        $session = $this->engine->startSession($request->user());

        return response()->json([
            'session_id' => $session->id,
            'status' => $session->status,
            'current_step' => $this->engine->getCurrentStep($session),
        ], 201);
    }

    public function current(Request $request, OnboardingSession $session)
    {
        // Ensure session belongs to user
        if ($session->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
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

    public function answer(Request $request, OnboardingSession $session)
    {
        if ($session->user_id !== $request->user()->id || $session->status === 'completed') {
            abort(403, 'Unauthorized or session completed');
        }

        $request->validate([
            'step_id' => 'required|string',
            'answers' => 'required|array',
        ]);

        $result = $this->engine->processAnswer($session, $request->step_id, $request->answers);

        return response()->json($result);
    }

    public function back(Request $request, OnboardingSession $session)
    {
        if ($session->user_id !== $request->user()->id || $session->status === 'completed') {
            abort(403, 'Unauthorized or session completed');
        }

        $result = $this->engine->goBack($session);

        if (!$result) {
            return response()->json([
                'error' => 'Cannot go back further'
            ], 400);
        }

        return response()->json($result);
    }

    public function show(Request $request, OnboardingSession $session)
    {
        if ($session->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $session->load('responses', 'currentStep');

        return response()->json($session);
    }
}
