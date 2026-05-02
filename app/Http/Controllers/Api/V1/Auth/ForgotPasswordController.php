<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use App\Services\PasswordResetService;
use Illuminate\Http\JsonResponse;

class ForgotPasswordController extends Controller
{
    public function __construct(
        private readonly PasswordResetService $resetService,
    ) {}

    /**
     * POST /api/v1/auth/forgot-password
     *
     * Mengirim link reset password ke email user via template Brevo.
     * Rate limit: max 3 permintaan per 60 menit.
     * Link berlaku selama 60 menit.
     */
    public function sendResetLink(ForgotPasswordRequest $request): JsonResponse
    {
        $user = User::where('email', strtolower($request->email))->first();

        // Always return success to prevent email enumeration attacks
        if (! $user) {
            return response()->json([
                'status'  => 'success',
                'message' => __('messages.reset_link_if_exists'),
            ]);
        }

        $this->resetService->sendResetLink($user);

        return response()->json([
            'status'  => 'success',
            'message' => __('messages.reset_link_sent'),
        ]);
    }

    /**
     * POST /api/v1/auth/reset-password
     *
     * Reset password menggunakan token dari email.
     * Token harus valid dan belum kadaluarsa (60 menit).
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->resetService->resetPassword(
            email: strtolower($request->email),
            token: $request->token,
            newPassword: $request->password,
        );

        return response()->json([
            'status'  => 'success',
            'message' => __('messages.reset_password_success'),
        ]);
    }
}
