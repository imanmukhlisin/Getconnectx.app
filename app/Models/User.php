<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable, SoftDeletes;

    // ─── Registration Step Constants ──────────────────────────────────────────
    public const STEP_REGISTERED          = 1;
    public const STEP_EMAIL_OTP_SENT      = 2;
    public const STEP_EMAIL_VERIFIED      = 3;
    public const STEP_WHATSAPP_OTP_SENT   = 4;
    public const STEP_WHATSAPP_VERIFIED   = 5;

    // ─── Next-Step Labels ─────────────────────────────────────────────────────
    public const NEXT_STEP_MAP = [
        self::STEP_REGISTERED        => 'NEED_EMAIL_OTP',
        self::STEP_EMAIL_OTP_SENT    => 'NEED_EMAIL_VERIFICATION',
        self::STEP_EMAIL_VERIFIED    => 'NEED_WHATSAPP_VERIFICATION',
        self::STEP_WHATSAPP_OTP_SENT => 'NEED_WHATSAPP_VERIFICATION',
        self::STEP_WHATSAPP_VERIFIED => 'REGISTRATION_COMPLETE',
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'entity_type',
        'name',
        'email',
        'password',
        'whatsapp_number',
        'registration_step',
        'is_active',
        'email_verified_at',
        'whatsapp_verified_at',
        'oauth_provider',
        'oauth_id',
        'oauth_token',
        'avatar_url',
    ];

    /**
     * The attributes that should be hidden from serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
        'oauth_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'    => 'datetime',
            'whatsapp_verified_at' => 'datetime',
            'is_active'            => 'boolean',
            'registration_step'    => 'integer',
            'password'             => 'hashed',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function otpCodes()
    {
        return $this->hasMany(OtpCode::class);
    }

    // ─── Helper Methods ───────────────────────────────────────────────────────

    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function hasVerifiedWhatsApp(): bool
    {
        return $this->whatsapp_verified_at !== null;
    }

    public function isRegistrationComplete(): bool
    {
        return $this->registration_step >= self::STEP_WHATSAPP_VERIFIED
            && $this->is_active;
    }

    public function nextStep(): string
    {
        return self::NEXT_STEP_MAP[$this->registration_step]
            ?? 'UNKNOWN';
    }

    /**
     * Get a sanitized summary for API responses during registration.
     */
    public function registrationSummary(): array
    {
        return [
            'id'                    => $this->id,
            'entity_type'           => $this->entity_type,
            'email'                 => $this->email,
            'email_verified_at'     => $this->email_verified_at,
            'whatsapp_number'       => $this->whatsapp_number,
            'whatsapp_verified_at'  => $this->whatsapp_verified_at,
            'registration_step'     => $this->registration_step,
            'is_active'             => $this->is_active,
        ];
    }
}
