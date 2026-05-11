<?php

namespace App\Filament\Resources\WhatsappBlastResource\Pages;

use App\Filament\Resources\WhatsappBlastResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use App\Models\WhatsappBlast;
use Illuminate\Database\Eloquent\Builder;

class ListWhatsappBlasts extends ListRecords
{
    protected static string $resource = WhatsappBlastResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('+ Buat Campaign Baru'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua')
                ->badge(WhatsappBlast::count()),

            'draft' => Tab::make('📝 Draft')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', 'draft'))
                ->badge(WhatsappBlast::where('status', 'draft')->count()),

            'scheduled' => Tab::make('📅 Terjadwal')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', 'scheduled'))
                ->badge(WhatsappBlast::where('status', 'scheduled')->count())
                ->badgeColor('warning'),

            'running' => Tab::make('🚀 Berjalan')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', 'running'))
                ->badge(WhatsappBlast::where('status', 'running')->count())
                ->badgeColor('info'),

            'completed' => Tab::make('✅ Selesai')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', 'completed'))
                ->badge(WhatsappBlast::where('status', 'completed')->count())
                ->badgeColor('success'),

            'failed' => Tab::make('❌ Gagal')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', 'failed'))
                ->badge(WhatsappBlast::where('status', 'failed')->count())
                ->badgeColor('danger'),
        ];
    }
}
