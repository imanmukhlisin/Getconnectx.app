<?php

namespace App\Services;

use App\Mail\EmailOtpMail;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class EmailVerificationService
{
    public function __construct(
        private readonly OtpService $otpService
    ) {}

    /**
     * Generate an OTP and send it to the user's email.
     *
     * @param  User  $user
     * @return OtpCode
     */
    public function sendOtp(User $user): OtpCode
    {
        $otp = $this->otpService->generate($user, 'email');

        Mail::to($user->email)->send(new EmailOtpMail($user, $otp->code));

        return $otp;
    }
}
