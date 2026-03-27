<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sequential Registration Lock Middleware
 *
 * Memastikan user hanya bisa mengakses endpoint tertentu jika
 * registration_step mereka sudah mencapai step yang diperlukan.
 *
 * Usage in routes:
 *   ->middleware('registration.progress:3')  // require step >= 3
 */
class RegistrationProgress
{
    /**
     * Handle an incoming request.
     *
     * @param  int $requiredStep  Minimum registration_step yang dibutuhkan
     */
    public function handle(Request $request, Closure $next, int $requiredStep = 1): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthenticated. Silakan daftar atau login terlebih dahulu.',
            ], 401);
        }

        if ($user->registration_step < $requiredStep) {
            $nextStepLabel = $this->getNextStepLabel($user->registration_step);
            return response()->json([
                'status'    => 'error',
                'message'   => 'Anda belum menyelesaikan tahap registrasi sebelumnya.',
                'next_step' => $nextStepLabel,
                'data'      => [
                    'current_step'  => $user->registration_step,
                    'required_step' => $requiredStep,
                ],
            ], 403);
        }

        return $next($request);
    }

    /**
     * Translate current step to a human-readable next_step label.
     */
    private function getNextStepLabel(int $currentStep): string
    {
        return User::NEXT_STEP_MAP[$currentStep] ?? 'NEED_EMAIL_OTP';
    }
}
