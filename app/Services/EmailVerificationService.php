<?php

namespace App\Services;

use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class EmailVerificationService
{
    public function __construct(
        private readonly OtpService    $otpService,
        private readonly BrevoService  $brevoService,
    ) {}

    /**
     * Generate an OTP and send it via Brevo transactional template.
     *
     * @param  User  $user
     * @return OtpCode
     */
    public function sendOtp(User $user): OtpCode
    {
        $otp = $this->otpService->generate($user, 'email');

        $templateId = config('services.brevo.otp_template_id', 3);

        $this->brevoService->sendTemplateEmail(
            templateId: $templateId,
            toEmail: $user->email,
            toName: $user->name ?? $user->email,
            params: [
                'name' => $user->name ?? $user->email,
                'OTP'  => $otp->code,
            ]
        );

        Log::info('OTP email sent via Brevo template', [
            'user_id'     => $user->id,
            'template_id' => $templateId,
        ]);

        return $otp;
    }
}
