<?php

namespace App\Filament\Resources\WhatsappLogResource\Pages;

use App\Filament\Resources\WhatsappLogResource;
use App\Filament\Widgets\WhatsappStatsWidget;
use App\Models\WhatsappLog;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListWhatsappLogs extends ListRecords
{
    protected static string $resource = WhatsappLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            WhatsappStatsWidget::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua')
                ->icon('heroicon-m-queue-list')
                ->badge(WhatsappLog::count()),

            'failed' => Tab::make('❌ Gagal')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'failed'))
                ->badge(WhatsappLog::where('status', 'failed')->count())
                ->badgeColor('danger'),

            'sent' => Tab::make('✅ Terkirim')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['sent', 'delivered', 'read']))
                ->badge(WhatsappLog::whereIn('status', ['sent', 'delivered', 'read'])->count())
                ->badgeColor('success'),

            'otp' => Tab::make('🔑 OTP')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('category', 'otp'))
                ->badge(WhatsappLog::where('category', 'otp')->count())
                ->badgeColor('info'),

            'blast' => Tab::make('📢 Blast')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('category', 'blast'))
                ->badge(WhatsappLog::where('category', 'blast')->count())
                ->badgeColor('warning'),

            'today' => Tab::make('Hari Ini')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('created_at', today()))
                ->icon('heroicon-m-calendar'),
        ];
    }
}
