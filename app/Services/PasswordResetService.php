<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordResetService
{
    private const TOKEN_EXPIRY_MINUTES = 60;
    private const MAX_REQUESTS_PER_WINDOW = 3;
    private const WINDOW_MINUTES = 60;

    public function __construct(
        private readonly BrevoService $brevoService,
    ) {}

    /**
     * Generate a password reset token and send it via Brevo template.
     *
     * @throws ValidationException  If rate limit exceeded
     * @throws \RuntimeException    If Brevo API fails
     */
    public function sendResetLink(User $user): void
    {
        // Rate limit check: max 3 requests per 60 minutes
        $this->checkRateLimit($user->email);

        // Generate a secure token
        $token = Str::random(64);

        // Store the token (upsert: if email exists, update)
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token'      => Hash::make($token),
                'created_at' => now(),
            ]
        );

        // Build the reset URL — page served by this Laravel app
        $baseUrl = config('services.brevo.frontend_url', config('app.url'));
        $resetUrl = "{$baseUrl}/reset-password/{$token}";

        // Send via Brevo template ID 4
        $templateId = config('services.brevo.forgot_password_template_id', 4);

        $this->brevoService->sendTemplateEmail(
            templateId: $templateId,
            toEmail: $user->email,
            toName: $user->name ?? $user->email,
            params: [
                'name'      => $user->name ?? $user->email,
                'reset_url' => $resetUrl,
            ]
        );

        Log::info('Password reset link sent', [
            'user_id'     => $user->id,
            'template_id' => $templateId,
        ]);
    }

    /**
     * Validate token and reset password.
     *
     * @throws ValidationException If token is invalid or expired
     */
    public function resetPassword(string $email, string $token, string $newPassword): void
    {
        $record = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (! $record) {
            throw ValidationException::withMessages([
                'email' => [__('messages.reset_token_invalid', [], 'en') ?: 'Token reset password tidak valid atau sudah digunakan.'],
            ]);
        }

        // Check token expiry (60 minutes)
        $createdAt = \Carbon\Carbon::parse($record->created_at);
        if ($createdAt->addMinutes(self::TOKEN_EXPIRY_MINUTES)->isPast()) {
            // Clean up expired token
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            throw ValidationException::withMessages([
                'token' => [__('messages.reset_token_expired', [], 'en') ?: 'Link reset password telah kadaluarsa. Silakan request ulang.'],
            ]);
        }

        // Verify token hash
        if (! Hash::check($token, $record->token)) {
            throw ValidationException::withMessages([
                'token' => [__('messages.reset_token_invalid', [], 'en') ?: 'Token reset password tidak valid atau sudah digunakan.'],
            ]);
        }

        // Update the user's password
        $user = User::where('email', $email)->firstOrFail();
        $user->update(['password' => $newPassword]); // auto-hashed via cast

        // Delete the used token
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        // Revoke all existing tokens for security
        $user->tokens()->delete();

        Log::info('Password reset successful', ['user_id' => $user->id]);
    }

    /**
     * Rate limit: max 3 requests per 60 minutes per email.
     *
     * @throws ValidationException
     */
    private function checkRateLimit(string $email): void
    {
        $record = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (! $record) {
            return; // No existing record, first request
        }

        $createdAt = \Carbon\Carbon::parse($record->created_at);

        // If within the rate limit window
        if (! $createdAt->addMinutes(self::WINDOW_MINUTES)->isPast()) {
            // Count how many times this was requested using cache
            $cacheKey = "password_reset_attempts:{$email}";
            $attempts = cache()->get($cacheKey, 0);

            if ($attempts >= self::MAX_REQUESTS_PER_WINDOW) {
                $minutesLeft = now()->diffInMinutes($createdAt->addMinutes(self::WINDOW_MINUTES));

                throw ValidationException::withMessages([
                    'email' => ["Terlalu banyak permintaan reset password. Coba lagi dalam {$minutesLeft} menit."],
                ]);
            }

            cache()->put($cacheKey, $attempts + 1, now()->addMinutes(self::WINDOW_MINUTES));
        } else {
            // Window expired, reset counter
            cache()->forget("password_reset_attempts:{$email}");
            cache()->put("password_reset_attempts:{$email}", 1, now()->addMinutes(self::WINDOW_MINUTES));
        }
    }
}
