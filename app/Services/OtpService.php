<?php

namespace App\Services;

use App\Exceptions\OtpRateLimitException;
use App\Models\OtpCode;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class OtpService
{
    private int $otpExpiryMinutes;
    private int $maxSends;
    private int $windowMinutes;

    public function __construct()
    {
        $this->otpExpiryMinutes = (int) config('otp.expiry_minutes', 10);
        $this->maxSends         = (int) config('otp.max_sends_per_window', 3);
        $this->windowMinutes    = (int) config('otp.window_minutes', 10);
    }

    /**
     * Generate a new OTP for the given user and channel.
     * Enforces rate limiting (max 3 sends per 10-minute window).
     *
     * @param  User   $user
     * @param  string $channel  'email' | 'whatsapp'
     * @return OtpCode
     *
     * @throws OtpRateLimitException
     */
    public function generate(User $user, string $channel): OtpCode
    {
        $this->checkRateLimit($user, $channel);

        $code = $this->generateCode();

        /** @var OtpCode|null $existing */
        $existing = OtpCode::where('user_id', $user->id)
            ->where('channel', $channel)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        // Re-use same window tracker record if within same rate-limit window
        if ($existing && $existing->send_window_start
            && $existing->send_window_start->diffInMinutes(now()) < $this->windowMinutes) {
            $existing->update([
                'code'       => $code,
                'expires_at' => now()->addMinutes($this->otpExpiryMinutes),
                'send_count' => $existing->send_count + 1,
            ]);
            return $existing->refresh();
        }

        // Create new OTP record with fresh window
        return OtpCode::create([
            'user_id'           => $user->id,
            'channel'           => $channel,
            'code'              => $code,
            'expires_at'        => now()->addMinutes($this->otpExpiryMinutes),
            'send_count'        => 1,
            'send_window_start' => now(),
        ]);
    }

    /**
     * Verify the OTP code for the given user and channel.
     *
     * @param  User   $user
     * @param  string $channel
     * @param  string $inputCode
     * @return OtpCode
     *
     * @throws ValidationException
     */
    public function verify(User $user, string $channel, string $inputCode): OtpCode
    {
        /** @var OtpCode|null $otp */
        $otp = OtpCode::latestActiveFor($user, $channel)->first();

        if (! $otp) {
            throw ValidationException::withMessages([
                'otp_code' => [__('messages.otp_not_found')],
            ]);
        }

        if (! hash_equals($otp->code, $inputCode)) {
            throw ValidationException::withMessages([
                'otp_code' => [__('messages.otp_invalid')],
            ]);
        }

        // Mark as verified
        $otp->update(['verified_at' => now()]);

        return $otp->refresh();
    }

    /**
     * Check rate limit – max sends per window.
     *
     * @throws OtpRateLimitException
     */
    public function checkRateLimit(User $user, string $channel): void
    {
        $latest = OtpCode::where('user_id', $user->id)
            ->where('channel', $channel)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (! $latest || ! $latest->send_window_start) {
            return; // No previous OTP, no rate limit concern
        }

        $windowStart = $latest->send_window_start;
        $minutesSinceWindow = $windowStart->diffInMinutes(now());

        if ($minutesSinceWindow < $this->windowMinutes
            && $latest->send_count >= $this->maxSends) {
            $retryAfter = $this->windowMinutes - (int) $minutesSinceWindow;
            throw new OtpRateLimitException(
                __('messages.otp_rate_limit_minute', ['minutes' => $retryAfter]),
                $retryAfter
            );
        }
    }

    /**
     * Generate a cryptographically random 6-digit OTP.
     */
    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
