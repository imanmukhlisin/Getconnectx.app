<?php

namespace App\Services;

use App\Models\User;
use App\Models\Startup;
use App\Models\StartupMember;
use Illuminate\Http\JsonResponse;

/**
 * ViewerContextService
 *
 * CON-71 + CON-72: Resolves and validates the viewer_context parameter.
 *
 * viewer_context=startup → user is acting as a Founder/Startup
 * viewer_context=talent  → user is acting as an Individual/Talent
 *
 * Returns the resolved context or throws a 409 DISCOVERY_ONBOARDING_REQUIRED
 * JSON response when the user tries to access a context they haven't set up yet.
 */
class ViewerContextService
{
    /**
     * Resolve and validate the viewer_context for a request.
     *
     * Returns an array with:
     *   - 'context'    : 'startup' | 'talent'
     *   - 'has_startup': bool — user owns a startup
     *   - 'has_talent' : bool — user has any talent/builder profile (always true for onboarded users)
     *   - 'startup'    : Startup|null
     *   - 'membership' : StartupMember|null
     *
     * Returns a JsonResponse (409) if context is unavailable.
     */
    public function resolve(User $user, ?string $requestedContext): array|JsonResponse
    {
        $hasStartup  = $this->hasStartupProfile($user);
        $hasTalent   = $this->hasTalentProfile($user);

        // Fall back to user's natural role if viewer_context is omitted
        $context = $requestedContext ?? $this->defaultContext($user, $hasStartup, $hasTalent);

        // Validate access
        if ($context === 'startup' && !$hasStartup) {
            return $this->onboardingRequiredResponse(
                'MISSING_STARTUP_PROFILE',
                'startup',
                $context
            );
        }

        if ($context === 'talent' && !$hasTalent) {
            return $this->onboardingRequiredResponse(
                'MISSING_TALENT_PROFILE',
                'talent',
                $context
            );
        }

        // Load related models
        $startup    = $hasStartup ? Startup::where('owner_id', $user->id)->first() : null;
        $membership = StartupMember::where('user_id', $user->id)->first();

        return [
            'context'    => $context,
            'has_startup' => $hasStartup,
            'has_talent'  => $hasTalent,
            'startup'    => $startup,
            'membership' => $membership,
        ];
    }

    // ─── Private Helpers ──────────────────────────────────────────────────────

    /**
     * A user has a startup profile if they own a startup record.
     */
    private function hasStartupProfile(User $user): bool
    {
        return Startup::where('owner_id', $user->id)->exists();
    }

    /**
     * A user has a talent profile if they have completed onboarding.
     * All onboarded users are considered to have a talent profile.
     */
    private function hasTalentProfile(User $user): bool
    {
        return (bool) $user->is_onboarded;
    }

    /**
     * Derive default context from user's role when viewer_context is omitted.
     * Priority: startup owner > talent
     */
    private function defaultContext(User $user, bool $hasStartup, bool $hasTalent): string
    {
        if ($hasStartup) return 'startup';
        return 'talent';
    }

    /**
     * Build and return a 409 DISCOVERY_ONBOARDING_REQUIRED response (CON-71).
     */
    private function onboardingRequiredResponse(
        string $reason,
        string $requiredProfileType,
        string $requestedViewerContext
    ): JsonResponse {
        $message = $requiredProfileType === 'startup'
            ? 'Set up your startup before using this mode.'
            : 'Create your individual profile before using this mode.';

        return response()->json([
            'success' => false,
            'message' => $message,
            'error'   => [
                'code'    => 'DISCOVERY_ONBOARDING_REQUIRED',
                'details' => [
                    'reason'                   => $reason,
                    'required_profile_type'    => $requiredProfileType,
                    'requested_viewer_context' => $requestedViewerContext,
                    'next_action'              => 'START_ONBOARDING',
                ],
            ],
        ], 409);
    }
}
