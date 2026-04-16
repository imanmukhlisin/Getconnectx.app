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

    protected int | string | array $columnSpan = 'full';

    // Auto refresh setiap 60 detik
    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $totalUsers    = User::count();
        $verifiedUsers = User::where('registration_step', 5)->count();
        $totalConnects = Like::where('type', 'connect')->count();
        $totalMatches  = Like::where('is_mutual', true)->count();
        $industryTags  = Tag::where('type', 'industry')->count();
        $skillTags     = Tag::where('type', 'skill')->count();

        $verifiedPct = $totalUsers > 0
            ? round(($verifiedUsers / $totalUsers) * 100)
            : 0;

        $matchRate = $totalConnects > 0
            ? round(($totalMatches / $totalConnects) * 100)
            : 0;

        return [
            Stat::make('Total User Terdaftar', number_format($totalUsers))
                ->description('Semua akun yang sudah registrasi')
                ->descriptionIcon('heroicon-m-user-plus')
                ->icon('heroicon-o-users')
                ->color('info'),

            Stat::make('User Terverifikasi', number_format($verifiedUsers))
                ->description("{$verifiedPct}% dari total user sudah lengkap")
                ->descriptionIcon('heroicon-m-check-circle')
                ->icon('heroicon-o-shield-check')
                ->color('success'),

            Stat::make('Total Koneksi', number_format($totalConnects))
                ->description('Aksi swipe kanan antar user')
                ->descriptionIcon('heroicon-m-hand-thumb-up')
                ->icon('heroicon-o-link')
                ->color('warning'),

            Stat::make('Match Mutual', number_format($totalMatches))
                ->description("Match rate: {$matchRate}% dari total koneksi")
                ->descriptionIcon('heroicon-m-heart')
                ->icon('heroicon-o-heart')
                ->color('danger'),

            Stat::make('Total Tags', number_format($industryTags + $skillTags))
                ->description("Industri: {$industryTags}  ·  Skill: {$skillTags}")
                ->descriptionIcon('heroicon-m-tag')
                ->icon('heroicon-o-tag')
                ->color('gray'),
        ];
    }
}
