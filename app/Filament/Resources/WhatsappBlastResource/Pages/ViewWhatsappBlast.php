<?php

namespace App\Filament\Resources\WhatsappBlastResource\Pages;

use App\Filament\Resources\WhatsappBlastResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewWhatsappBlast extends ViewRecord
{
    protected static string $resource = WhatsappBlastResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->label('Edit')
                ->visible(fn () => in_array($this->record->status, ['draft', 'scheduled'])),
            Actions\Action::make('cancel')
                ->label('Batalkan Campaign')
                ->icon('heroicon-m-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->action(fn () => $this->record->update(['status' => 'cancelled']))
                ->visible(fn () => in_array($this->record->status, ['scheduled', 'running'])),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Campaign Detail')
                ->icon('heroicon-m-megaphone')
                ->columns(2)
                ->schema([
                    Infolists\Components\TextEntry::make('name')
                        ->label('Nama Campaign')
                        ->weight('bold'),
                    Infolists\Components\BadgeEntry::make('status')
                        ->label('Status')
                        ->formatStateUsing(fn ($state) => match ($state) {
                            'draft'     => '📝 Draft',
                            'scheduled' => '📅 Terjadwal',
                            'running'   => '🚀 Berjalan',
                            'completed' => '✅ Selesai',
                            'failed'    => '❌ Gagal',
                            'cancelled' => '🚫 Dibatalkan',
                            default     => $state,
                        })
                        ->color(fn ($state) => match ($state) {
                            'completed' => 'success',
                            'running'   => 'info',
                            'scheduled' => 'warning',
                            'failed'    => 'danger',
                            default     => 'gray',
                        }),
                    Infolists\Components\TextEntry::make('target_segment')
                        ->label('Target Segmen')
                        ->formatStateUsing(fn ($state) => match ($state) {
                            'all'       => '👥 Semua User',
                            'onboarded' => '✅ Sudah Onboarding',
                            'new_users' => '🆕 User Baru 7 Hari',
                            'custom'    => '✏️ Nomor Custom',
                            default     => $state,
                        }),
                    Infolists\Components\TextEntry::make('scheduled_at')
                        ->label('Jadwal Kirim')
                        ->dateTime('d M Y, H:i')
                        ->timezone('Asia/Jakarta')
                        ->placeholder('—'),
                    Infolists\Components\TextEntry::make('message')
                        ->label('Isi Pesan')
                        ->columnSpanFull()
                        ->prose(),
                    Infolists\Components\TextEntry::make('notes')
                        ->label('Catatan')
                        ->columnSpanFull()
                        ->placeholder('—'),
                ]),

            Infolists\Components\Section::make('📊 Statistik Pengiriman')
                ->icon('heroicon-m-chart-bar')
                ->columns(4)
                ->schema([
                    Infolists\Components\TextEntry::make('total_recipients')
                        ->label('Total Penerima')
                        ->numeric()
                        ->icon('heroicon-m-users'),
                    Infolists\Components\TextEntry::make('sent_count')
                        ->label('Terkirim')
                        ->numeric()
                        ->color('info')
                        ->icon('heroicon-m-paper-airplane'),
                    Infolists\Components\TextEntry::make('success_count')
                        ->label('✅ Sukses')
                        ->numeric()
                        ->color('success'),
                    Infolists\Components\TextEntry::make('failed_count')
                        ->label('❌ Gagal')
                        ->numeric()
                        ->color('danger'),
                    Infolists\Components\TextEntry::make('success_rate')
                        ->label('Success Rate')
                        ->state(fn ($record) => $record->success_rate . '%')
                        ->badge()
                        ->color(fn ($record) => $record->success_rate >= 80 ? 'success' : ($record->success_rate >= 50 ? 'warning' : 'danger')),
                    Infolists\Components\TextEntry::make('started_at')
                        ->label('Mulai')
                        ->dateTime('d M Y, H:i')
                        ->timezone('Asia/Jakarta')
                        ->placeholder('—'),
                    Infolists\Components\TextEntry::make('completed_at')
                        ->label('Selesai')
                        ->dateTime('d M Y, H:i')
                        ->timezone('Asia/Jakarta')
                        ->placeholder('—'),
                ]),
        ]);
    }
}
