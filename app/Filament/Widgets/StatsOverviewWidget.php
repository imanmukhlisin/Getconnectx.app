<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $totalUsers       = User::count();
        $today            = User::whereDate('created_at', Carbon::today())->count();
        $onboarded        = User::where('is_onboarded', true)->count();
        $blocked          = User::where('is_blocked', true)->count();
        $onboardingRate   = $totalUsers > 0 ? round(($onboarded / $totalUsers) * 100) : 0;
        $thisWeek         = User::where('created_at', '>=', Carbon::now()->startOfWeek())->count();

        // Spark data — last 7 days
        $spark = [];
        for ($i = 6; $i >= 0; $i--) {
            $spark[] = User::whereDate('created_at', Carbon::now()->subDays($i))->count();
        }

        // Spark data arrays for visualization
        $sparkOnboarded = [];
        $sparkBlocked = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $sparkOnboarded[] = User::where('is_onboarded', true)->whereDate('updated_at', '<=', $date)->count();
            $sparkBlocked[] = User::where('is_blocked', true)->whereDate('updated_at', '<=', $date)->count();
        }
        $activeToday = User::whereHas('tokens', function ($q) {
            $q->whereDate('last_used_at', '>=', Carbon::today());
        })->count();

        $inactive = User::where(function ($query) {
            $query->whereHas('tokens', function ($q) {
                $q->where('last_used_at', '<', Carbon::now()->subDays(30));
            })->orWhere(function ($q2) {
                $q2->whereDoesntHave('tokens')
                   ->where('created_at', '<', Carbon::now()->subDays(30));
            });
        })->count();

        return [
            Stat::make('Total User Terdaftar', number_format($totalUsers))
                ->description("{$thisWeek} user baru minggu ini")
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->icon('heroicon-o-users')
                ->color('info'),

            Stat::make('Daftar Hari Ini', number_format($today))
                ->description('Registrasi baru hari ini')
                ->descriptionIcon('heroicon-m-user-plus')
                ->icon('heroicon-o-calendar-days')
                ->color('success'),

            Stat::make('Sudah Onboarding', "{$onboardingRate}%")
                ->description("{$onboarded} dari {$totalUsers} user sudah mengisi onboarding")
                ->descriptionIcon('heroicon-m-check-circle')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('warning'),

            Stat::make('Aktif Hari Ini', number_format($activeToday))
                ->description('Login / Sesi dalam 24 jam terakhir')
                ->descriptionIcon('heroicon-m-bolt')
                ->icon('heroicon-o-sparkles')
                ->color('success')
                ->extraAttributes([
                    'class' => $activeToday > 0 ? 'animate-pulse shadow-[0_0_15px_rgba(34,197,94,0.6)]' : '',
                ]),

            Stat::make('Tidak Aktif', number_format($inactive))
                ->description('> 1 bulan tanpa aktivitas')
                ->descriptionIcon('heroicon-m-clock')
                ->icon('heroicon-o-moon')
                ->color('danger'),

            Stat::make('Akun Diblokir', number_format($blocked))
                ->description($blocked > 0 ? 'Perlu perhatian admin' : 'Tidak ada akun yang diblokir')
                ->descriptionIcon($blocked > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-shield-check')
                ->icon('heroicon-o-no-symbol')
                ->color($blocked > 0 ? 'danger' : 'gray'),
        ];
    }
}
