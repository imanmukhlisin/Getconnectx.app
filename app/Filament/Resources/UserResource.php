<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'User Management';
    protected static ?string $modelLabel = 'Pengguna';
    protected static ?string $pluralModelLabel = 'Data Pengguna';

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Profil Utama')
                    ->schema([
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\ImageEntry::make('avatar_url')
                                ->label('Foto Profil')
                                ->circular()
                                ->defaultImageUrl(fn($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name))
                                ->hidden(fn ($state) => blank($state)),
                            Infolists\Components\TextEntry::make('name')
                                ->label('Nama Lengkap')
                                ->weight('bold')
                                ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                            Infolists\Components\TextEntry::make('email')
                                ->label('Email')
                                ->icon('heroicon-m-envelope'),
                            Infolists\Components\TextEntry::make('whatsapp_number')
                                ->label('Nomor WhatsApp')
                                ->icon('heroicon-m-phone'),
                            Infolists\Components\TextEntry::make('city')
                                ->label('Domisili')
                                ->icon('heroicon-m-map-pin')
                                ->hidden(fn ($state) => blank($state)),
                            Infolists\Components\TextEntry::make('linkedin_url')
                                ->label('LinkedIn')
                                ->icon('heroicon-m-link')
                                ->url(fn ($state) => $state)
                                ->openUrlInNewTab()
                                ->hidden(fn ($state) => blank($state)),
                            Infolists\Components\TextEntry::make('date_of_birth')
                                ->label('Tanggal Lahir')
                                ->date('d M Y')
                                ->hidden(fn ($state) => blank($state)),
                            Infolists\Components\TextEntry::make('gender')
                                ->label('Jenis Kelamin')
                                ->hidden(fn ($state) => blank($state)),
                        ]),
                    ]),

                Infolists\Components\Section::make('Detail Startup / Peran')
                    ->schema([
                        Infolists\Components\Grid::make(2)->schema([
                            Infolists\Components\TextEntry::make('role_category')
                                ->label('Kategori Role')
                                ->badge()
                                ->color('info'),
                            Infolists\Components\TextEntry::make('primary_role')
                                ->label('Peran Utama (Skill)')
                                ->hidden(fn ($state) => blank($state)),
                            Infolists\Components\TextEntry::make('startup_name')
                                ->label('Nama Startup')
                                ->hidden(fn ($state) => blank($state)),
                            Infolists\Components\TextEntry::make('startup_tagline')
                                ->label('Tagline Startup')
                                ->hidden(fn ($state) => blank($state)),
                            Infolists\Components\TextEntry::make('startup_stage')
                                ->label('Tahap Startup')
                                ->badge()
                                ->hidden(fn ($state) => blank($state)),
                            Infolists\Components\TextEntry::make('commitment_level')
                                ->label('Tingkat Komitmen')
                                ->hidden(fn ($state) => blank($state)),
                            Infolists\Components\TextEntry::make('years_experience')
                                ->label('Pengalaman Kerja')
                                ->hidden(fn ($state) => blank($state)),
                            Infolists\Components\TextEntry::make('startup_experience')
                                ->label('Pengalaman Startup')
                                ->hidden(fn ($state) => blank($state)),
                            Infolists\Components\TextEntry::make('cofounder_type')
                                ->label('Tipe Co-Founder Dicari')
                                ->hidden(fn ($state) => blank($state)),
                            Infolists\Components\TextEntry::make('open_to_remote')
                                ->label('Remote Preference')
                                ->hidden(fn ($state) => blank($state)),
                            Infolists\Components\TextEntry::make('willing_to_relocate')
                                ->label('Relokasi')
                                ->hidden(fn ($state) => blank($state)),
                        ]),
                        Infolists\Components\TextEntry::make('bio')
                            ->label('Bio Singkat')
                            ->columnSpanFull()
                            ->hidden(fn ($state) => blank($state)),
                        Infolists\Components\TextEntry::make('startup_idea')
                            ->label('Ide / Pitch Startup')
                            ->columnSpanFull()
                            ->hidden(fn ($state) => blank($state)),
                    ])
                    ->hidden(fn ($record) => blank($record->role_category)),

                Infolists\Components\Section::make('Status Sistem')
                    ->schema([
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\IconEntry::make('is_onboarded')
                                ->label('Selesai Onboarding')
                                ->boolean(),
                            Infolists\Components\IconEntry::make('is_active')
                                ->label('Akun Aktif')
                                ->boolean(),
                            Infolists\Components\IconEntry::make('is_blocked')
                                ->label('Status Blokir')
                                ->boolean()
                                ->trueIcon('heroicon-o-no-symbol')
                                ->falseIcon('heroicon-o-check-circle')
                                ->trueColor('danger')
                                ->falseColor('success'),
                            Infolists\Components\TextEntry::make('blocked_reason')
                                ->label('Alasan Blokir')
                                ->color('danger')
                                ->columnSpanFull()
                                ->hidden(fn ($state) => blank($state)),
                        ])
                    ])->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ViewColumn::make('avatar_url')
                    ->label('Foto')
                    ->view('filament.tables.columns.avatar-with-pro'),
                    
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (User $record): string => $record->email ?? '-'),
                
                Tables\Columns\TextColumn::make('role_category')
                    ->label('Role')
                    ->searchable()
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => $state ?? 'Belum Pilih'),

                Tables\Columns\IconColumn::make('is_onboarded')
                    ->label('Onboarding')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Aktivitas')
                    ->badge()
                    ->getStateUsing(function (User $record) {
                        if ($record->is_blocked) return 'Diblokir';

                        $latestToken = $record->tokens()->orderBy('last_used_at', 'desc')->first();
                        $lastActive = $latestToken ? $latestToken->last_used_at : null;

                        if (!$lastActive) {
                            if (!$record->is_onboarded && $record->created_at && $record->created_at->diffInDays(now()) > 30) {
                                return 'Tidak Aktif';
                            }
                            return 'Belum Login';
                        }

                        $diffDays = $lastActive->startOfDay()->diffInDays(now()->startOfDay());

                        if ($diffDays == 0) {
                            return 'Aktif Sekarang';
                        } elseif ($diffDays == 1) {
                            return 'Kemarin';
                        } elseif ($diffDays < 7) {
                            return "{$diffDays} hari yang lalu";
                        } elseif ($diffDays < 30) {
                            $weeks = floor($diffDays / 7);
                            return "{$weeks} minggu yang lalu";
                        } else {
                            return 'Tidak Aktif (> 1 bln)';
                        }
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Aktif Sekarang' => 'success',
                        'Kemarin' => 'info',
                        'Diblokir', 'Tidak Aktif', 'Tidak Aktif (> 1 bln)', 'Belum Login' => 'danger',
                        default => str_contains($state, 'hari') ? 'info' : 'warning',
                    })
                    ->icon(fn (string $state): ?string => $state === 'Aktif Sekarang' ? 'heroicon-s-sparkles' : null)
                    ->extraAttributes(fn (string $state): array => $state === 'Aktif Sekarang' ? [
                        'class' => 'animate-pulse shadow-[0_0_15px_rgba(34,197,94,0.8)]',
                    ] : []),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tgl Daftar')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role_category')
                    ->label('Filter Role')
                    ->options([
                        'founder' => 'Founder',
                        'startup' => 'Startup',
                        'team_member' => 'Team Member',
                        'cofounder' => 'Co-Founder',
                    ]),
                Filter::make('is_onboarded')
                    ->label('Sudah Onboarding')
                    ->query(fn (Builder $query): Builder => $query->where('is_onboarded', true)),
                Filter::make('is_blocked')
                    ->label('Akun Diblokir')
                    ->query(fn (Builder $query): Builder => $query->where('is_blocked', true)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                
                // Tombol Blokir
                Action::make('block')
                    ->label('Blokir')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Blokir Pengguna')
                    ->modalDescription('Pengguna yang diblokir tidak akan bisa login atau mendaftar lagi menggunakan email ini.')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Alasan Pemblokiran')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (User $record, array $data): void {
                        $record->update([
                            'is_blocked' => true,
                            'blocked_reason' => $data['reason'],
                            'blocked_at' => now(),
                            'is_active' => false,
                        ]);
                        // Hapus token session
                        $record->tokens()->delete();

                        \Filament\Notifications\Notification::make()
                            ->title('Berhasil diblokir')
                            ->body("Pengguna {$record->name} berhasil diblokir.")
                            ->success()
                            ->send();
                    })
                    ->visible(fn (User $record): bool => ! $record->is_blocked),

                // Tombol Buka Blokir
                Action::make('unblock')
                    ->label('Buka Blokir')
                    ->icon('heroicon-o-shield-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Buka Blokir Pengguna')
                    ->action(function (User $record): void {
                        $record->update([
                            'is_blocked' => false,
                            'blocked_reason' => null,
                            'blocked_at' => null,
                            'is_active' => true,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Blokir dibuka')
                            ->body("Akses pengguna {$record->name} berhasil dipulihkan.")
                            ->success()
                            ->send();
                    })
                    ->visible(fn (User $record): bool => $record->is_blocked),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'view' => Pages\ViewUser::route('/{record}'),
        ];
    }
}
