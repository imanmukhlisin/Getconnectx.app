<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\StatsOverviewWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\AccountWidget;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon  = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $title           = 'Dashboard Admin';
    protected static ?int    $navigationSort  = -2;

    public function getColumns(): int | string | array
    {
        return [
            'default' => 1,
            'sm'      => 2,
            'lg'      => 3,
            'xl'      => 5,
        ];
    }

    public function getWidgets(): array
    {
        return [
            StatsOverviewWidget::class,
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    public function getHeaderActions(): array
    {
        return [];
    }
}
