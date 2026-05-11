<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WhatsappBlastResource\Pages;
use App\Models\User;
use App\Models\WhatsappBlast;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WhatsappBlastResource extends Resource
{
    protected static ?string $model = WhatsappBlast::class;

    protected static ?string $navigationIcon   = 'heroicon-o-megaphone';
    protected static ?string $navigationGroup  = '📱 WhatsApp';
    protected static ?string $navigationLabel  = 'Blasting WA';
    protected static ?string $modelLabel       = 'Blast Campaign';
    protected static ?string $pluralModelLabel = 'Blasting WhatsApp';
    protected static ?int    $navigationSort   = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('📢 Informasi Campaign')
                ->icon('heroicon-m-megaphone')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nama Campaign')
                        ->placeholder('Contoh: Blast Promo Mei 2026')
                        ->required()
                        ->maxLength(200),

                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'draft'     => '📝 Draft',
                            'scheduled' => '📅 Terjadwal',
                        ])
                        ->default('draft')
                        ->required(),

                    Forms\Components\Textarea::make('message')
                        ->label('Isi Pesan Broadcast')
                        ->placeholder("Halo [nama], kami punya kabar baik untuk kamu! 🎉\n\nConnectX membuka akses Co-Founder matching premium mulai hari ini...")
                        ->helperText('Gunakan [nama] untuk nama penerima, [nomor] untuk nomor mereka.')
                        ->rows(6)
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('notes')
                        ->label('Catatan Internal')
                        ->placeholder('Catatan untuk tim...')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('🎯 Target Penerima')
                ->icon('heroicon-m-users')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('target_segment')
                        ->label('Segmen Target')
                        ->options([
                            'all'        => '👥 Semua User',
                            'onboarded'  => '✅ Sudah Onboarding',
                            'new_users'  => '🆕 User Baru (7 hari)',
                            'custom'     => '✏️ Nomor Custom',
                        ])
                        ->default('all')
                        ->live()
                        ->required(),

                    Forms\Components\DateTimePicker::make('scheduled_at')
                        ->label('Jadwalkan Kirim')
                        ->placeholder('Kosongkan untuk kirim sekarang')
                        ->timezone('Asia/Jakarta')
                        ->helperText('Opsional: isi jika ingin dijadwalkan'),

                    Forms\Components\Placeholder::make('recipient_count_preview')
                        ->label('Estimasi Penerima')
                        ->content(function (Forms\Get $get) {
                            $segment = $get('target_segment');
                            return match ($segment) {
                                'all'       => number_format(User::whereNotNull('whatsapp_number')->count()) . ' user',
                                'onboarded' => number_format(User::where('is_onboarded', true)->whereNotNull('whatsapp_number')->count()) . ' user',
                                'new_users' => number_format(User::where('created_at', '>=', now()->subDays(7))->whereNotNull('whatsapp_number')->count()) . ' user',
                                'custom'    => 'Sesuai daftar nomor di bawah',
                                default     => '—',
                            };
                        }),

                    Forms\Components\Textarea::make('recipient_phones')
                        ->label('Daftar Nomor (Format JSON atau satu per baris)')
                        ->placeholder("+628123456789\n+628987654321")
                        ->helperText('Satu nomor per baris. Format internasional (+62xxx)')
                        ->rows(5)
                        ->visible(fn (Forms\Get $get) => $get('target_segment') === 'custom')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Campaign')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->notes ? \Str::limit($record->notes, 50) : null),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
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

                Tables\Columns\TextColumn::make('target_segment')
                    ->label('Target')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'all'       => '👥 Semua',
                        'onboarded' => '✅ Onboarded',
                        'new_users' => '🆕 User Baru',
                        'custom'    => '✏️ Custom',
                        default     => $state,
                    })
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('total_recipients')
                    ->label('Total')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('success_count')
                    ->label('✅ Sukses')
                    ->numeric()
                    ->color('success'),

                Tables\Columns\TextColumn::make('failed_count')
                    ->label('❌ Gagal')
                    ->numeric()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Terjadwal')
                    ->dateTime('d M Y, H:i')
                    ->timezone('Asia/Jakarta')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Selesai')
                    ->dateTime('d M Y, H:i')
                    ->timezone('Asia/Jakarta')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft'     => '📝 Draft',
                        'scheduled' => '📅 Terjadwal',
                        'running'   => '🚀 Berjalan',
                        'completed' => '✅ Selesai',
                        'failed'    => '❌ Gagal',
                        'cancelled' => '🚫 Dibatalkan',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()->label('Lihat Detail'),
                    Tables\Actions\EditAction::make()->label('Edit')
                        ->visible(fn ($record) => in_array($record->status, ['draft', 'scheduled'])),
                    Tables\Actions\Action::make('cancel')
                        ->label('Batalkan')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->update(['status' => 'cancelled']))
                        ->visible(fn ($record) => in_array($record->status, ['scheduled', 'running'])),
                    Tables\Actions\DeleteAction::make()->label('Hapus')
                        ->visible(fn ($record) => $record->status === 'draft'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Hapus Terpilih'),
                ]),
            ])
            ->emptyStateIcon('heroicon-o-megaphone')
            ->emptyStateHeading('Belum ada campaign blast')
            ->emptyStateDescription('Buat campaign blasting WhatsApp pertama Anda.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()->label('Buat Campaign Baru'),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListWhatsappBlasts::route('/'),
            'create' => Pages\CreateWhatsappBlast::route('/create'),
            'view'   => Pages\ViewWhatsappBlast::route('/{record}'),
            'edit'   => Pages\EditWhatsappBlast::route('/{record}/edit'),
        ];
    }
}
