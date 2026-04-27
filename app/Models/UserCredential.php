<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Model UserCredential.
 *
 * Menyimpan data kredensial dari penyedia OAuth (LinkedIn, Google, dll).
 * Setiap user bisa punya beberapa baris berbeda (satu per provider).
 *
 * Kolom experience dan education wajib default array kosong [],
 * BUKAN null, agar Scoring Engine tidak crash saat menghitung variabel G dan J.
 */
class UserCredential extends Model
{
    use HasUuids;

    protected $table = 'user_credentials';

    protected $fillable = [
        'user_id',
        'provider',
        'experience',
        'education',
        'raw_data',
    ];

    protected function casts(): array
    {
        return [
            'experience' => 'array',
            'education'  => 'array',
            'raw_data'   => 'array',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    /**
     * User pemilik kredensial ini.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
