<?php

namespace App\Filament\Widgets;

use App\Models\Like;
use App\Models\Tag;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalUsers      = User::count();
        $verifiedUsers   = User::where('registration_step', 5)->count();
        $totalConnects   = Like::where('type', 'connect')->count();
        $totalMatches    = Like::where('is_mutual', true)->count();
        $industryTags    = Tag::where('type', 'industry')->count();
        $skillTags       = Tag::where('type', 'skill')->count();

        $verifiedPct = $totalUsers > 0
            ? round(($verifiedUsers / $totalUsers) * 100)
            : 0;

        return [
            Stat::make('👥 Total User Terdaftar', number_format($totalUsers))
                ->description('Semua user yang sudah registrasi')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),

            Stat::make('✅ User Terverifikasi', number_format($verifiedUsers))
                ->description("{$verifiedPct}% dari total user sudah melewati seluruh verifikasi")
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('success'),

            Stat::make('🤝 Total Koneksi (Swipe Kanan)', number_format($totalConnects))
                ->description('Jumlah aksi "connect" yang dilakukan antar user')
                ->descriptionIcon('heroicon-m-hand-thumb-up')
                ->color('warning'),

            Stat::make('💞 Total Match Mutual', number_format($totalMatches))
                ->description('Koneksi dua arah yang saling menyetujui')
                ->descriptionIcon('heroicon-m-heart')
                ->color('danger'),

            Stat::make('🏷️ Total Tags', number_format($industryTags + $skillTags))
                ->description("Industri: {$industryTags} tag · Skill: {$skillTags} tag")
                ->descriptionIcon('heroicon-m-tag')
                ->color('gray'),
        ];
    }
}
