<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Migrasikan data Startup yang sudah lama tersimpan di kolom `users`
     * (startup_name, startup_tagline, startup_stage) ke tabel `startups` yang baru.
     *
     * Migration ini bersifat idempotent (aman dijalankan berkali-kali).
     */
    public function up(): void
    {
        // Ambil semua user yang punya startup_name dan role_category = 'Startup'
        $startupUsers = DB::table('users')
            ->whereNotNull('startup_name')
            ->where('startup_name', '!=', '')
            ->where('role_category', 'Startup')
            ->select([
                'id',
                'startup_name',
                'startup_tagline',
                'startup_stage',
                'city',
                'latitude',
                'longitude',
            ])
            ->get();

        foreach ($startupUsers as $user) {
            // Cek apakah startup untuk user ini sudah ada (idempotent)
            $existing = DB::table('startups')
                ->where('owner_id', $user->id)
                ->first();

            if ($existing) {
                // Hanya update jika memang ada perubahan data baru
                DB::table('startups')
                    ->where('owner_id', $user->id)
                    ->update([
                        'name'       => $user->startup_name,
                        'tagline'    => $user->startup_tagline ?? $existing->tagline,
                        'stage'      => $user->startup_stage   ?? $existing->stage,
                        'city'       => $user->city            ?? $existing->city,
                        'latitude'   => $user->latitude        ?? $existing->latitude,
                        'longitude'  => $user->longitude       ?? $existing->longitude,
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('startups')->insert([
                    'id'         => Str::uuid()->toString(),
                    'owner_id'   => $user->id,
                    'name'       => $user->startup_name,
                    'tagline'    => $user->startup_tagline,
                    'stage'      => $user->startup_stage,
                    'city'       => $user->city,
                    'latitude'   => $user->latitude,
                    'longitude'  => $user->longitude,
                    'team_size'  => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Rollback: hapus semua startup yang owner-nya punya startup_name di tabel users.
     * Data di tabel users TIDAK dihapus (aman).
     */
    public function down(): void
    {
        $ownerIds = DB::table('users')
            ->whereNotNull('startup_name')
            ->where('role_category', 'Startup')
            ->pluck('id');

        DB::table('startups')
            ->whereIn('owner_id', $ownerIds)
            ->delete();
    }
};
