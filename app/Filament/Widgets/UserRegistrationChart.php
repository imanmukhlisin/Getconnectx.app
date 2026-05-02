<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class UserRegistrationChart extends ChartWidget
{
    protected static ?string $heading = 'Grafik Pendaftaran Pengguna (14 Hari Terakhir)';
    protected static ?int $sort = 2; // Tampil di bawah StatsOverview
    protected int | string | array $columnSpan = 'full';

    // Animasi mulus saat data dimuat
    protected static ?array $options = [
        'animation' => [
            'duration' => 1000,
            'easing' => 'easeOutQuart',
        ],
        'scales' => [
            'y' => [
                'beginAtZero' => true,
            ],
        ],
    ];

    protected function getData(): array
    {
        $data = [];
        $labels = [];

        // Ambil data 14 hari terakhir
        for ($i = 13; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('d M');
            $data[] = User::whereDate('created_at', $date)->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'User Baru',
                    'data' => $data,
                    'fill' => 'start',
                    'backgroundColor' => 'rgba(249, 115, 22, 0.15)', // Orange-500 dengan opacity
                    'borderColor' => '#f97316', // Orange-500
                    'pointBackgroundColor' => '#ffffff',
                    'pointBorderColor' => '#f97316',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 4,
                    'pointHoverRadius' => 6,
                    'tension' => 0.4, // Membuat garis sangat mulus (curved)
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
