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

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'User Management';
    protected static ?string $modelLabel = 'Pengguna';
    protected static ?string $pluralModelLabel = 'Data Pengguna';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pribadi')->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nama Lengkap')
                        ->disabled(),
                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->disabled(),
                    Forms\Components\TextInput::make('whatsapp_number')
                        ->label('Nomor WhatsApp')
                        ->disabled(),
                    Forms\Components\TextInput::make('role_category')
                        ->label('Kategori Role')
                        ->disabled(),
                    Forms\Components\TextInput::make('city')
                        ->label('Kota')
                        ->disabled(),
                    Forms\Components\TextInput::make('linkedin_url')
                        ->label('LinkedIn URL')
                        ->url()
                        ->disabled(),
                ])->columns(2),

                Forms\Components\Section::make('Status Akun')->schema([
                    Forms\Components\Toggle::make('is_onboarded')
                        ->label('Sudah Onboarding?')
                        ->disabled(),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Akun Aktif')
                        ->disabled(),
                    Forms\Components\Toggle::make('is_blocked')
                        ->label('Status Blokir')
                        ->disabled(),
                    Forms\Components\Textarea::make('blocked_reason')
                        ->label('Alasan Diblokir')
                        ->disabled(),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
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
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(function (User $record) {
                        if ($record->is_blocked) return 'Diblokir';
                        return $record->is_active ? 'Aktif' : 'Tidak Aktif';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Aktif' => 'success',
                        'Tidak Aktif' => 'warning',
                        'Diblokir' => 'danger',
                    }),

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
