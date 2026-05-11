<?php

namespace App\Filament\Widgets;

use App\Models\WhatsappLog;
use App\Models\WhatsappBlast;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class WhatsappStatsWidget extends BaseWidget
{
    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $pollingInterval = '30s';

    // Show only on WhatsApp-related pages
    protected static ?string $navigationGroup = '📱 WhatsApp';

    protected function getStats(): array
    {
        // ── Hari ini ──────────────────────────────────────────────────────────
        $today         = WhatsappLog::whereDate('created_at', today());
        $totalToday    = (clone $today)->count();
        $successToday  = (clone $today)->whereIn('status', ['sent', 'delivered', 'read'])->count();
        $failedToday   = (clone $today)->where('status', 'failed')->count();
        $successRate   = $totalToday > 0 ? round(($successToday / $totalToday) * 100) : 0;

        // ── 7 hari terakhir — spark data ──────────────────────────────────────
        $sparkSuccess = [];
        $sparkFailed  = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::now()->subDays($i);
            $sparkSuccess[] = WhatsappLog::whereDate('created_at', $day)
                ->whereIn('status', ['sent', 'delivered', 'read'])->count();
            $sparkFailed[]  = WhatsappLog::whereDate('created_at', $day)
                ->where('status', 'failed')->count();
        }

        // ── OTP stats ─────────────────────────────────────────────────────────
        $otpToday   = WhatsappLog::where('category', 'otp')->whereDate('created_at', today())->count();

        // ── Blast stats ───────────────────────────────────────────────────────
        $activeBlast = WhatsappBlast::whereIn('status', ['running', 'scheduled'])->count();
        $doneBlast   = WhatsappBlast::where('status', 'completed')->count();

        return [
            Stat::make('Terkirim Hari Ini', $successToday . ' / ' . $totalToday)
                ->description("{$successRate}% success rate")
                ->descriptionIcon($successRate >= 80 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->icon('heroicon-o-paper-airplane')
                ->color($successRate >= 80 ? 'success' : ($successRate >= 50 ? 'warning' : 'danger'))
                ->chart($sparkSuccess),

            Stat::make('Gagal Hari Ini', number_format($failedToday))
                ->description($failedToday > 0 ? 'Ada pesan yang gagal — cek log!' : 'Tidak ada kegagalan hari ini')
                ->descriptionIcon($failedToday > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-shield-check')
                ->icon('heroicon-o-x-circle')
                ->color($failedToday > 0 ? 'danger' : 'success')
                ->chart($sparkFailed)
                ->extraAttributes([
                    'class' => $failedToday > 0 ? 'shadow-[0_0_15px_rgba(239,68,68,0.5)]' : '',
                ]),

            Stat::make('OTP Terkirim Hari Ini', number_format($otpToday))
                ->description('Pesan verifikasi / login')
                ->descriptionIcon('heroicon-m-key')
                ->icon('heroicon-o-shield-check')
                ->color('info'),

            Stat::make('Campaign Blast Aktif', number_format($activeBlast))
                ->description("{$doneBlast} campaign selesai")
                ->descriptionIcon('heroicon-m-megaphone')
                ->icon('heroicon-o-megaphone')
                ->color($activeBlast > 0 ? 'warning' : 'gray'),
        ];
    }
}
