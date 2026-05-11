<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappLog extends Model
{
    protected $fillable = [
        'recipient_phone',
        'recipient_name',
        'message',
        'message_type',
        'status',
        'provider',
        'category',
        'event_trigger',
        'error_message',
        'provider_response',
        'message_id',
        'blast_id',
        'sent_by_admin_id',
        'sent_at',
        'delivered_at',
        'read_at',
    ];

    protected $casts = [
        'provider_response' => 'array',
        'sent_at'           => 'datetime',
        'delivered_at'      => 'datetime',
        'read_at'           => 'datetime',
    ];

    // ─── Relationships ─────────────────────────────────────────────────────────

    public function blast(): BelongsTo
    {
        return $this->belongsTo(WhatsappBlast::class, 'blast_id');
    }

    public function sentByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'sent_by_admin_id');
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    public function isSuccess(): bool
    {
        return in_array($this->status, ['sent', 'delivered', 'read']);
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            'sent'      => 'info',
            'delivered' => 'success',
            'read'      => 'success',
            'failed'    => 'danger',
            'pending'   => 'warning',
            default     => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'sent'      => '✅ Terkirim',
            'delivered' => '📬 Diterima',
            'read'      => '👀 Dibaca',
            'failed'    => '❌ Gagal',
            'pending'   => '⏳ Menunggu',
            default     => $this->status,
        };
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'otp'          => '🔑 OTP / Verifikasi',
            'blast'        => '📢 Blasting',
            'notification' => '🔔 Notifikasi',
            'manual'       => '✍️ Manual',
            default        => $this->category,
        };
    }
}
