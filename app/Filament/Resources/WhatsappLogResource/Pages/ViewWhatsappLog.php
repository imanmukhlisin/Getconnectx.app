<?php

namespace App\Filament\Resources\WhatsappLogResource\Pages;

use App\Filament\Resources\WhatsappLogResource;
use App\Filament\Widgets\WhatsappStatsWidget;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewWhatsappLog extends ViewRecord
{
    protected static string $resource = WhatsappLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('Hapus Log'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Detail Pesan')
                ->icon('heroicon-m-chat-bubble-left-ellipsis')
                ->columns(2)
                ->schema([
                    Infolists\Components\TextEntry::make('recipient_phone')
                        ->label('Nomor Tujuan')
                        ->icon('heroicon-m-phone')
                        ->copyable(),
                    Infolists\Components\TextEntry::make('recipient_name')
                        ->label('Nama Penerima')
                        ->placeholder('—'),
                    Infolists\Components\TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->formatStateUsing(fn ($state) => match ($state) {
                            'pending'   => '⏳ Pending',
                            'sent'      => '✅ Terkirim',
                            'delivered' => '📬 Diterima',
                            'read'      => '👀 Dibaca',
                            'failed'    => '❌ Gagal',
                            default     => $state,
                        })
                        ->color(fn ($state) => match ($state) {
                            'sent'      => 'info',
                            'delivered' => 'success',
                            'read'      => 'success',
                            'failed'    => 'danger',
                            'pending'   => 'warning',
                            default     => 'gray',
                        }),
                    Infolists\Components\TextEntry::make('category')
                        ->label('Kategori')
                        ->badge()
                        ->formatStateUsing(fn ($state) => match ($state) {
                            'otp'          => '🔑 OTP',
                            'blast'        => '📢 Blast',
                            'notification' => '🔔 Notif',
                            'manual'       => '✍️ Manual',
                            default        => $state,
                        })
                        ->color('primary'),
                    Infolists\Components\TextEntry::make('provider')
                        ->label('Provider')
                        ->badge()
                        ->color('gray'),
                    Infolists\Components\TextEntry::make('message_id')
                        ->label('Message ID')
                        ->placeholder('—')
                        ->copyable(),
                    Infolists\Components\TextEntry::make('event_trigger')
                        ->label('Event Trigger')
                        ->placeholder('—')
                        ->badge()
                        ->color('gray'),
                    Infolists\Components\TextEntry::make('message_type')
                        ->label('Tipe Pesan')
                        ->badge()
                        ->color('gray'),
                    Infolists\Components\TextEntry::make('message')
                        ->label('Isi Pesan')
                        ->columnSpanFull()
                        ->prose(),
                    Infolists\Components\TextEntry::make('error_message')
                        ->label('Pesan Error')
                        ->columnSpanFull()
                        ->color('danger')
                        ->icon('heroicon-m-exclamation-circle')
                        ->placeholder('—'),
                ]),

            Infolists\Components\Section::make('⏱️ Timeline Pengiriman')
                ->icon('heroicon-m-clock')
                ->columns(4)
                ->schema([
                    Infolists\Components\TextEntry::make('created_at')
                        ->label('🕐 Dibuat')
                        ->dateTime('d M Y, H:i:s')
                        ->timezone('Asia/Jakarta'),
                    Infolists\Components\TextEntry::make('sent_at')
                        ->label('📤 Terkirim')
                        ->dateTime('d M Y, H:i:s')
                        ->timezone('Asia/Jakarta')
                        ->placeholder('—'),
                    Infolists\Components\TextEntry::make('delivered_at')
                        ->label('📬 Diterima')
                        ->dateTime('d M Y, H:i:s')
                        ->timezone('Asia/Jakarta')
                        ->placeholder('—'),
                    Infolists\Components\TextEntry::make('read_at')
                        ->label('👀 Dibaca')
                        ->dateTime('d M Y, H:i:s')
                        ->timezone('Asia/Jakarta')
                        ->placeholder('—'),
                ]),

            Infolists\Components\Section::make('🔗 Relasi')
                ->icon('heroicon-m-link')
                ->columns(2)
                ->schema([
                    Infolists\Components\TextEntry::make('blast_id')
                        ->label('ID Blast Campaign')
                        ->placeholder('Bukan dari blast')
                        ->badge()
                        ->color('warning'),
                    Infolists\Components\TextEntry::make('sent_by_admin_id')
                        ->label('Dikirim oleh Admin ID')
                        ->placeholder('Sistem otomatis')
                        ->badge()
                        ->color('gray'),
                ]),
        ]);
    }
}
