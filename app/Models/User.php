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

    protected $fillable = [
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
        'fcm_token',
        'avatar_url',
        'username',
        'position',
        'role_category',
        'commitment_level',
        'startup_stage',
        'is_onboarded',
        'latitude',
        'longitude',
        'date_of_birth',
        'gender',
        'primary_role',
        'years_experience',
        'startup_experience',
        'cofounder_type',
        'linkedin_url',
        'linkedin_data',
        'startup_name',
        'startup_tagline',
        'open_to_remote',
        'willing_to_relocate',
        'is_pro',
        'city',
        'country',
        'bio',
        'startup_idea',
        'education',
        'languages',
        'leadership_style',
        'work_arrangement',
        'remote_ready',
        'last_device_id',
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
            'is_onboarded'         => 'boolean',
            'is_pro'               => 'boolean',
            'remote_ready'         => 'boolean',
            'education'            => 'array',
            'languages'            => 'array',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function startup()
    {
        return $this->hasOne(\App\Models\Startup::class, 'owner_id');
    }

    /**
     * Kredensial OAuth user (LinkedIn, Google, dll).
     */
    public function credentials()
    {
        return $this->hasMany(UserCredential::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'user_tags');
    }

    public function otpCodes()
    {
        return $this->hasMany(OtpCode::class);
    }

    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
                    ->withTimestamps();
    }

    // ─── Location Scopes (Haversine Formula) ─────────────────────────────────

    /**
     * Scope: tambahkan kolom 'distance_km' ke query berdasarkan titik referensi.
     *
     * Usage: User::withDistance(-6.2, 106.8)->orderBy('distance_km')->get()
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  float  $latitude   Latitude titik referensi
     * @param  float  $longitude  Longitude titik referensi
     */
    public function scopeWithDistance($query, float $latitude, float $longitude)
    {
        $haversine = sprintf(
            '(6371 * acos(cos(radians(%F)) * cos(radians(latitude)) * cos(radians(longitude) - radians(%F)) + sin(radians(%F)) * sin(radians(latitude))))',
            $latitude, $longitude, $latitude
        );

        return $query
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw("{$haversine} AS distance_km");
    }

    /**
     * Scope: filter user dalam radius tertentu (km) dan urutkan dari terdekat.
     *
     * Usage: User::nearby(-6.2, 106.8, 50)->get()  // within 50km
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  float  $latitude   Latitude titik referensi
     * @param  float  $longitude  Longitude titik referensi
     * @param  float  $radiusKm   Radius maksimum dalam kilometer (default: 50)
     */
    public function scopeNearby($query, float $latitude, float $longitude, float $radiusKm = 50)
    {
        $haversine = sprintf(
            '(6371 * acos(cos(radians(%F)) * cos(radians(latitude)) * cos(radians(longitude) - radians(%F)) + sin(radians(%F)) * sin(radians(latitude))))',
            $latitude, $longitude, $latitude
        );

        return $query
            ->select('*')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw("{$haversine} AS distance_km")
            ->havingRaw("{$haversine} <= ?", [$radiusKm])
            ->orderByRaw("{$haversine} ASC");
    }

    /**
     * Hitung jarak (km) dari user ini ke titik koordinat tertentu.
     *
     * Usage: $user->distanceTo(-6.2, 106.8) // returns float km
     */
    public function distanceTo(float $latitude, float $longitude): ?float
    {
        if ($this->latitude === null || $this->longitude === null) {
            return null;
        }

        // Haversine formula in PHP
        $earthRadiusKm = 6371;
        $dLat = deg2rad($latitude - $this->latitude);
        $dLng = deg2rad($longitude - $this->longitude);

        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($this->latitude)) * cos(deg2rad($latitude))
           * sin($dLng / 2) ** 2;

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
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
            'email'                 => $this->email,
            'email_verified_at'     => $this->email_verified_at,
            'whatsapp_number'       => $this->whatsapp_number,
            'whatsapp_verified_at'  => $this->whatsapp_verified_at,
            'registration_step'     => $this->registration_step,
            'is_active'             => $this->is_active,
            'is_onboarded'          => $this->is_onboarded,

        ];
    }
}
