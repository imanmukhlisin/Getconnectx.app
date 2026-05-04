<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Exceptions\WhatsAppDeliveryException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginOtpSendRequest;
use App\Http\Requests\Auth\LoginOtpVerifyRequest;
use App\Http\Requests\Auth\LoginRequest;

use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\SendWhatsAppOtpRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Http\Requests\Auth\VerifyWhatsAppRequest;
use App\Models\User;
use App\Services\EmailVerificationService;
use App\Services\OtpService;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function __construct(
        private readonly EmailVerificationService $emailService,
        private readonly WhatsAppService          $whatsAppService,
        private readonly OtpService               $otpService,
        private readonly \App\Services\SupabaseAuthService $supabaseAuth,
    ) {}

    // =========================================================================
    //  STEP 1 – Register (Manual)
    // =========================================================================

    /**
     * POST /api/v1/auth/register
     *
     * Membuat akun baru dengan tipe entitas yang ditentukan.
     * Mengembalikan temporary Sanctum token untuk digunakan di step berikutnya.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request) {
            $email = strtolower($request->email);
            $user = User::where('email', $email)->first();

            if ($user) {
                // Re-register inactive user
                $updateData = [
                    'password'  => $request->password, // auto-hashed via cast
                    'fcm_token' => $request->fcm_token,
                    'latitude'  => $request->latitude,
                    'longitude' => $request->longitude,
                ];

                // Jika belum verify email, kembali ke step awal (1)
                // Jika sudah verify email tapi belum verify WA, kembali ke step email verified (3)
                if (!$user->hasVerifiedEmail()) {
                    $updateData['registration_step'] = User::STEP_REGISTERED;
                } else if (!$user->hasVerifiedWhatsApp()) {
                    $updateData['registration_step'] = User::STEP_EMAIL_VERIFIED;
                }

                $user->update($updateData);

                // Revoke old tokens
                $user->tokens()->where('name', 'registration-token')->delete();

                return $user;
            }

            return User::create([
                'email'             => $email,
                'password'          => $request->password, // auto-hashed via cast
                'fcm_token'         => $request->fcm_token,
                'latitude'          => $request->latitude,
                'longitude'         => $request->longitude,
                'registration_step' => User::STEP_REGISTERED,
                'is_active'         => false,
            ]);
        });

        // Issue temporary registration token (expires in N minutes per config)
        $token = $user->createToken(
            'registration-token',
            ['registration'],
            now()->addMinutes((int) config('sanctum.registration_token_expiry', 60))
        )->plainTextToken;

        return $this->successResponse(
            message : __('messages.registration_success'),
            nextStep: $user->nextStep(),
            data    : ['user' => $user->registrationSummary()],
            token   : $token,
            status  : 201
        );
    }

    // =========================================================================
    //  STEP 2 – Send Email OTP
    // =========================================================================

    /**
     * POST /api/v1/auth/email/send-otp
     *
     * Mengirim 6-digit OTP ke email user. Rate limit: max 3x per 10 menit.
     */
    public function sendEmailOtp(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->errorResponse(__('messages.email_already_verified'), 'EMAIL_ALREADY_VERIFIED', 409);
        }

        $this->emailService->sendOtp($user);

        $user->update(['registration_step' => max($user->registration_step, User::STEP_EMAIL_OTP_SENT)]);

        return $this->successResponse(
            message : __('messages.email_otp_sent', ['email' => $user->email]),
            nextStep: 'NEED_EMAIL_VERIFICATION',
        );
    }

    // =========================================================================
    //  STEP 3 – Verify Email OTP
    // =========================================================================

    /**
     * POST /api/v1/auth/verify-email
     *
     * Memverifikasi kode OTP email. Menandai email_verified_at dan advance step.
     */
    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->successResponse(
                message : __('messages.email_already_verified'),
                nextStep: 'NEED_WHATSAPP_VERIFICATION',
                data    : ['user' => $user->registrationSummary()],
            );
        }

        // Throws ValidationException if code is wrong/expired
        $this->otpService->verify($user, 'email', $request->otp_code);

        $user->update([
            'email_verified_at'  => now(),
            'registration_step'  => User::STEP_EMAIL_VERIFIED,
        ]);

        return $this->successResponse(
            message : __('messages.email_verify_success'),
            nextStep: 'NEED_WHATSAPP_VERIFICATION',
            data    : ['user' => $user->fresh()->registrationSummary()],
        );
    }

    // =========================================================================
    //  STEP 4 – Send WhatsApp OTP
    // =========================================================================

    /**
     * POST /api/v1/auth/whatsapp/send-otp
     *
     * Menerima whatsapp_number (format internasional) dan mengirimkan OTP via WA.
     */
    public function sendWhatsAppOtp(SendWhatsAppOtpRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $phoneNumber = $request->whatsapp_number ?? $user->whatsapp_number;

        if ($user->hasVerifiedWhatsApp()) {
            return $this->errorResponse(__('messages.whatsapp_already_verified'), 'WHATSAPP_ALREADY_VERIFIED', 409);
        }

        // --- BYPASS WHATSAPP VERIFICATION TEMPORARILY ---
        // try {
        //     $this->whatsAppService->sendOtp($user, $phoneNumber);
        // } catch (WhatsAppDeliveryException $e) {
        //     return $this->errorResponse(__('messages.whatsapp_delivery_failed'), 'WHATSAPP_DELIVERY_FAILED', 502);
        // }

        // Instead of waiting for OTP, complete registration right away
        DB::transaction(function () use ($user, $phoneNumber) {
            $user->update([
                'whatsapp_number'   => $phoneNumber,
                // 'whatsapp_verified_at' => now(), // Do not set as verified (is_wa_verified=false)
                'registration_step' => User::STEP_WHATSAPP_VERIFIED,
                'is_active'         => true,
            ]);

            // Revoke all temporary registration tokens
            $user->tokens()->where('name', 'registration-token')->delete();
        });

        // Sinkronisasi ke Supabase Auth (auth.users)
        $this->supabaseAuth->syncUserToSupabase($user);

        // Issue permanent (full-access) token
        $finalToken = $user->createToken('auth-token', ['*'])->plainTextToken;
        $supabaseToken = $this->supabaseAuth->generateSupabaseToken($user);

        Log::info('Registration completed (Bypass WA Verify)', [
            'user_id'     => $user->id,
            'email'       => $user->email,
        ]);

        return $this->successResponse(
            message : __('messages.registration_complete'),
            nextStep: $user->fresh()->nextStep(),
            data    : ['user' => $user->fresh()->registrationSummary()],
            token   : $finalToken,
            extra   : ['supabase_token' => $supabaseToken]
        );
    }

    // =========================================================================
    //  STEP 5 – Verify WhatsApp OTP
    // =========================================================================

    /**
     * POST /api/v1/auth/verify-whatsapp
     *
     * Memverifikasi OTP WhatsApp. Jika berhasil, akun diaktifkan (is_active: true)
     * dan registration_step di-set ke 5.
     */
    public function verifyWhatsApp(VerifyWhatsAppRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasVerifiedWhatsApp()) {
            return $this->successResponse(
                message : __('messages.whatsapp_verify_success'),
                nextStep: $user->nextStep(),
                data    : ['user' => $user->registrationSummary()],
            );
        }

        $this->otpService->verify($user, 'whatsapp', $request->otp_code);

        DB::transaction(function () use ($user) {
            $user->update([
                'whatsapp_verified_at' => now(),
                'registration_step'    => User::STEP_WHATSAPP_VERIFIED,
                'is_active'            => true,
            ]);

            // Revoke all temporary registration tokens
            $user->tokens()->where('name', 'registration-token')->delete();
        });

        // Sinkronisasi ke Supabase Auth (auth.users)
        $this->supabaseAuth->syncUserToSupabase($user);

        // Issue permanent (full-access) token
        $finalToken = $user->createToken('auth-token', ['*'])->plainTextToken;
        $supabaseToken = $this->supabaseAuth->generateSupabaseToken($user);

        Log::info('Registration completed', [
            'user_id'     => $user->id,

            'email'       => $user->email,
        ]);

        return $this->successResponse(
            message : __('messages.registration_complete'),
            nextStep: $user->fresh()->nextStep(),
            data    : ['user' => $user->fresh()->registrationSummary()],
            token   : $finalToken,
            extra   : ['supabase_token' => $supabaseToken]
        );
    }

    // =========================================================================
    //  LOGIN OTP
    // =========================================================================

    /**
     * POST /api/v1/auth/login/otp/send

     */
    public function loginOtpSend(LoginOtpSendRequest $request): JsonResponse
    {
        $user = User::where('email', strtolower($request->email))->first();

        if (! $user) {
            return $this->errorResponse(__('messages.val_email_not_found'), 'USER_NOT_FOUND', 404);
        }

        // Inactive users can login to finish registration


        $this->emailService->sendOtp($user);

        return $this->successResponse(
            message : __('messages.login_otp_sent', ['email' => $user->email]),
            nextStep: 'NEED_LOGIN_VERIFICATION'
        );
    }

    /**
     * POST /api/v1/auth/login/otp/verify

     */
    public function loginOtpVerify(LoginOtpVerifyRequest $request): JsonResponse
    {
        $user = User::where('email', strtolower($request->email))->first();

        if (! $user) {
            return $this->errorResponse(__('messages.val_email_not_found'), 'USER_NOT_FOUND', 404);
        }

        $this->otpService->verify($user, 'email', $request->otp_code);

        // Sinkronisasi ke Supabase Auth (untuk jaga-jaga kalau belum ada)
        $this->supabaseAuth->syncUserToSupabase($user);

        $token = $user->createToken('auth-token', ['*'])->plainTextToken;
        $supabaseToken = $this->supabaseAuth->generateSupabaseToken($user);

        $nextStep = $user->nextStep();

        return $this->successResponse(
            message : __('messages.login_success'),
            nextStep: $nextStep,
            data    : ['user' => $user->registrationSummary()],
            token   : $token,
            extra   : ['supabase_token' => $supabaseToken]
        );
    }

    // =========================================================================
    //  LOGIN PASSWORD
    // =========================================================================

    /**
     * POST /api/v1/auth/login/password
     *
     * Login tradisional menggunakan email dan password.
     */
    public function loginWithPassword(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', strtolower($request->email))->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->errorResponse(__('messages.login_failed'), 'INVALID_CREDENTIALS', 401);
        }

        // Inactive users can login to finish registration


        // Sinkronisasi ke Supabase Auth
        $this->supabaseAuth->syncUserToSupabase($user);

        $token = $user->createToken('auth-token', ['*'])->plainTextToken;
        $supabaseToken = $this->supabaseAuth->generateSupabaseToken($user);

        $nextStep = $user->nextStep();

        return $this->successResponse(
            message : __('messages.login_success'),
            nextStep: $nextStep,
            data    : ['user' => $user->registrationSummary()],
            token   : $token,
            extra   : ['supabase_token' => $supabaseToken]
        );
    }

    // =========================================================================
    //  TOKEN REFRESH
    // =========================================================================

    /**
     * POST /api/v1/auth/refresh
     *
     * Memperbarui token Sanctum yang kedaluwarsa secara otomatis
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $user = $request->user();

        // Cabut (revoke) token yang sedang digunakan untuk request ini
        $request->user()->currentAccessToken()->delete();

        // Buat token baru
        $newToken = $user->createToken('auth-token', ['*'])->plainTextToken;

        return $this->successResponse(
            message : 'Token berhasil diperbarui.',
            nextStep: 'TOKEN_REFRESHED',
            data    : ['user' => $user->registrationSummary()],
            token   : $newToken
        );
    }

    // =========================================================================
    //  SESSION
    // =========================================================================

    /**
     * GET /api/v1/auth/session
     *
     * Return the authenticated user's normalized app session.
     */
    public function session(Request $request): JsonResponse
    {
        $user = $request->user();

        // Dynamic discovery mode mapping from onboarding
        $discoveryMode = 'explore_startups';
        if ($user->is_onboarded) {
            $session = \App\Models\Onboarding\OnboardingSession::where('user_id', $user->id)
                ->where('status', 'completed')
                ->latest('completed_at')
                ->first();

            if ($session) {
                $responses = \App\Models\Onboarding\OnboardingResponse::where('session_id', $session->id)
                    ->get()
                    ->keyBy('question_id');

                $getVal = fn($key) => isset($responses[$key]) ? (is_array($responses[$key]->value) ? ($responses[$key]->value[0] ?? null) : $responses[$key]->value) : null;

                $action = $getVal('q_use_connectx');
                if ($action === 'startup') {
                    $lookingFor = $getVal('q_su_need_type') ?? $getVal('q_fdr_looking');
                    $discoveryMode = match ($lookingFor) {
                        'cofounder' => 'finding_cofounder',
                        'team'      => 'building_team',
                        'both'      => 'building_team',
                        default     => 'finding_cofounder'
                    };
                } else {
                    $discoveryMode = 'joining_startups';
                }
            }
        }

        return $this->successResponse(
            message: 'Session loaded.',
            nextStep: $user->nextStep(),
            data: [
                'user' => [
                    'id'                   => $user->id,
                    'entity_type'          => null,
                    'email'                => $user->email,
                    'email_verified_at'    => $user->email_verified_at,
                    'whatsapp_number'      => $user->whatsapp_number,
                    'whatsapp_verified_at' => $user->whatsapp_verified_at,
                    'registration_step'    => $user->registration_step,
                    'is_active'            => $user->is_active,
                    'is_onboarded'         => (bool) $user->is_onboarded,
                ],
                'discovery_preferences' => [
                    'default_discovery_mode' => $discoveryMode,
                ],
                'premium' => [
                    'boost'     => 3,
                    'spotlight' => 1,
                    'isPremium' => (bool) $user->is_pro,
                ],
            ]
        );
    }

    // =========================================================================

    private function successResponse(
        string  $message,
        string  $nextStep = 'REGISTRATION_COMPLETE',
        array   $data     = [],
        ?string $token    = null,
        int     $status   = 200,
        array   $extra    = [],
    ): JsonResponse {
        $payload = [
            'status'    => 'success',
            'message'   => $message,
            'next_step' => $nextStep,
            'data'      => $data,
        ];

        if ($token !== null) {
            $payload['token'] = $token;
            $payload['token_type'] = 'Bearer';
        }

        if (!empty($extra)) {
            $payload = array_merge($payload, $extra);
        }

        return response()->json($payload, $status);
    }

    private function errorResponse(
        string $message,
        string $code   = 'ERROR',
        int    $status = 400,
    ): JsonResponse {
        return response()->json([
            'status'  => 'error',
            'message' => $message,
            'code'    => $code,
        ], $status);
    }
}
