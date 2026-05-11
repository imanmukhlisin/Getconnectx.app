<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class WhatsappWebhookConfig extends Model
{
    protected $fillable = [
        'name',
        'provider',
        'phone_number_id',
        'waba_id',
        'access_token',
        'app_secret',
        'verify_token',
        'api_version',
        'is_active',
        'webhook_verified',
        'last_webhook_at',
        'token_expires_at',
        'webhook_fields',
        'notes',
    ];

    protected $casts = [
        'is_active'        => 'boolean',
        'webhook_verified' => 'boolean',
        'last_webhook_at'  => 'datetime',
        'token_expires_at' => 'datetime',
        'webhook_fields'   => 'array',
    ];

    protected $hidden = ['access_token', 'app_secret'];

    // ─── Helpers ───────────────────────────────────────────────────────────────

    public function getWebhookUrl(): string
    {
        return config('app.url') . '/api/v1/webhook/whatsapp';
    }

    public function getMaskedTokenAttribute(): string
    {
        if (empty($this->access_token)) return '—';
        return substr($this->access_token, 0, 12) . '••••••••••••' . substr($this->access_token, -6);
    }

    public function getIsTokenExpiredAttribute(): bool
    {
        return $this->token_expires_at && $this->token_expires_at->isPast();
    }
}
