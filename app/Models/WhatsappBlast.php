<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappBlast extends Model
{
    protected $fillable = [
        'name',
        'message',
        'status',
        'target_segment',
        'target_filters',
        'recipient_phones',
        'total_recipients',
        'sent_count',
        'success_count',
        'failed_count',
        'scheduled_at',
        'started_at',
        'completed_at',
        'created_by_admin_id',
        'notes',
    ];

    protected $casts = [
        'target_filters'    => 'array',
        'recipient_phones'  => 'array',
        'scheduled_at'      => 'datetime',
        'started_at'        => 'datetime',
        'completed_at'      => 'datetime',
    ];

    // ─── Relationships ─────────────────────────────────────────────────────────

    public function logs(): HasMany
    {
        return $this->hasMany(WhatsappLog::class, 'blast_id');
    }

    public function createdByAdmin(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    public function getSuccessRateAttribute(): int
    {
        return $this->sent_count > 0
            ? (int) round(($this->success_count / $this->sent_count) * 100)
            : 0;
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            'completed'  => 'success',
            'running'    => 'info',
            'scheduled'  => 'warning',
            'failed'     => 'danger',
            'cancelled'  => 'gray',
            'draft'      => 'gray',
            default      => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft'      => '📝 Draft',
            'scheduled'  => '📅 Terjadwal',
            'running'    => '🚀 Berjalan',
            'completed'  => '✅ Selesai',
            'failed'     => '❌ Gagal',
            'cancelled'  => '🚫 Dibatalkan',
            default      => $this->status,
        };
    }
}
