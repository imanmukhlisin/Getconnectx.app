<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WhatsappLogResource\Pages;
use App\Models\WhatsappLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WhatsappLogResource extends Resource
{
    protected static ?string $model = WhatsappLog::class;

    protected static ?string $navigationIcon        = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationGroup       = '📱 WhatsApp';
    protected static ?string $navigationLabel       = 'Log Pesan WA';
    protected static ?string $modelLabel            = 'Log Pesan';
    protected static ?string $pluralModelLabel      = 'Log Pesan WhatsApp';
    protected static ?int    $navigationSort        = 2;

    public static function getNavigationBadge(): ?string
    {
        $failed = static::getModel()::where('status', 'failed')
            ->whereDate('created_at', today())
            ->count();

        return $failed > 0 ? (string) $failed : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Detail Pesan')
                ->icon('heroicon-m-chat-bubble-left-ellipsis')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('recipient_phone')
                        ->label('Nomor Tujuan')
                        ->disabled(),
                    Forms\Components\TextInput::make('recipient_name')
                        ->label('Nama Penerima')
                        ->disabled(),
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'pending'   => '⏳ Menunggu',
                            'sent'      => '✅ Terkirim',
                            'delivered' => '📬 Diterima',
                            'read'      => '👀 Dibaca',
                            'failed'    => '❌ Gagal',
                        ])
                        ->disabled(),
                    Forms\Components\Select::make('category')
                        ->label('Kategori')
                        ->options([
                            'otp'          => '🔑 OTP / Verifikasi',
                            'blast'        => '📢 Blasting',
                            'notification' => '🔔 Notifikasi',
                            'manual'       => '✍️ Manual',
                        ])
                        ->disabled(),
                    Forms\Components\TextInput::make('provider')
                        ->label('Provider')
                        ->disabled(),
                    Forms\Components\TextInput::make('message_id')
                        ->label('Message ID (Provider)')
                        ->disabled()
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('message')
                        ->label('Isi Pesan')
                        ->rows(4)
                        ->disabled()
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('error_message')
                        ->label('Pesan Error')
                        ->rows(3)
                        ->disabled()
                        ->columnSpanFull()
                        ->visible(fn ($record) => !empty($record?->error_message)),
                ]),

            Forms\Components\Section::make('Waktu Pengiriman')
                ->icon('heroicon-m-clock')
                ->columns(3)
                ->schema([
                    Forms\Components\Placeholder::make('sent_at')
                        ->label('Terkirim')
                        ->content(fn ($record) => $record?->sent_at?->diffForHumans() ?? '—'),
                    Forms\Components\Placeholder::make('delivered_at')
                        ->label('Diterima')
                        ->content(fn ($record) => $record?->delivered_at?->diffForHumans() ?? '—'),
                    Forms\Components\Placeholder::make('read_at')
                        ->label('Dibaca')
                        ->content(fn ($record) => $record?->read_at?->diffForHumans() ?? '—'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->timezone('Asia/Jakarta')
                    ->size(Tables\Columns\TextColumn\TextColumnSize::ExtraSmall)
                    ->color('gray'),

                Tables\Columns\TextColumn::make('recipient_phone')
                    ->label('Nomor / Penerima')
                    ->description(fn ($record) => $record->recipient_name)
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-phone'),

                Tables\Columns\TextColumn::make('message')
                    ->label('Pesan')
                    ->limit(55)
                    ->wrap()
                    ->tooltip(fn ($record) => $record->message)
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small),

                Tables\Columns\TextColumn::make('category')
                    ->label('Kategori')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'otp'          => '🔑 OTP',
                        'blast'        => '📢 Blast',
                        'notification' => '🔔 Notif',
                        'manual'       => '✍️ Manual',
                        default        => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'otp'          => 'info',
                        'blast'        => 'warning',
                        'notification' => 'primary',
                        'manual'       => 'gray',
                        default        => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
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

                Tables\Columns\TextColumn::make('provider')
                    ->label('Provider')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('error_message')
                    ->label('Error')
                    ->limit(40)
                    ->color('danger')
                    ->icon('heroicon-m-exclamation-circle')
                    ->toggleable()
                    ->visible(fn ($record) => !empty($record?->error_message))
                    ->tooltip(fn ($record) => $record->error_message),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending'   => '⏳ Pending',
                        'sent'      => '✅ Terkirim',
                        'delivered' => '📬 Diterima',
                        'read'      => '👀 Dibaca',
                        'failed'    => '❌ Gagal',
                    ]),

                Tables\Filters\SelectFilter::make('category')
                    ->label('Kategori')
                    ->options([
                        'otp'          => '🔑 OTP',
                        'blast'        => '📢 Blasting',
                        'notification' => '🔔 Notifikasi',
                        'manual'       => '✍️ Manual',
                    ]),

                Tables\Filters\SelectFilter::make('provider')
                    ->label('Provider')
                    ->options([
                        'saungwa' => 'SaungWA',
                        'meta'    => 'Meta WABA',
                        'fonnte'  => 'Fonnte',
                        'twilio'  => 'Twilio',
                    ]),

                Tables\Filters\Filter::make('today')
                    ->label('Hari Ini')
                    ->query(fn (Builder $query) => $query->whereDate('created_at', today())),

                Tables\Filters\Filter::make('failed_today')
                    ->label('⚠️ Gagal Hari Ini')
                    ->query(fn (Builder $query) => $query
                        ->where('status', 'failed')
                        ->whereDate('created_at', today())),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\ViewAction::make()->label('Lihat'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Hapus Terpilih'),
                ]),
            ])
            ->headerActions([
                // Stats header
            ])
            ->emptyStateIcon('heroicon-o-chat-bubble-left-right')
            ->emptyStateHeading('Belum ada log pesan')
            ->emptyStateDescription('Semua pesan WhatsApp yang dikirim sistem akan tercatat di sini.');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWhatsappLogs::route('/'),
            'view'  => Pages\ViewWhatsappLog::route('/{record}'),
        ];
    }
}
