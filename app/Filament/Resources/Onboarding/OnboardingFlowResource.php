<?php

namespace App\Filament\Resources\Onboarding;

use App\Filament\Resources\Onboarding\OnboardingFlowResource\Pages;
use App\Models\Onboarding\OnboardingFlow;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OnboardingFlowResource extends Resource
{
    protected static ?string $model = OnboardingFlow::class;

    protected static ?string $navigationIcon  = 'heroicon-o-arrow-path-rounded-square';
    protected static ?string $navigationGroup = 'Onboarding Engine';
    protected static ?string $navigationLabel = 'Alur Onboarding';
    protected static ?int    $navigationSort  = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Alur')
                    ->description('Alur (flow) adalah kumpulan step/halaman yang dilalui user saat onboarding.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Alur')
                            ->placeholder('Contoh: Onboarding Utama, Onboarding Freelancer')
                            ->helperText('Nama internal alur ini. Tidak terlihat oleh user.')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->placeholder('Contoh: Alur standar untuk semua user baru ConnectX')
                            ->helperText('Catatan singkat tentang tujuan alur ini.')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_entry')
                            ->label('Alur Utama (Entry Point)?')
                            ->helperText('Aktifkan jika ini adalah alur pertama yang dijalani user baru. Hanya boleh ada 1 alur utama.')
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Alur')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->description),
                Tables\Columns\IconColumn::make('is_entry')
                    ->label('Alur Utama?')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle'),
                Tables\Columns\TextColumn::make('steps_count')
                    ->label('Jumlah Step')
                    ->counts('steps')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Terakhir Diubah')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make()->label('Edit'),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Alur Onboarding?')
                    ->modalDescription('Tindakan ini akan menghapus alur beserta seluruh step dan pertanyaan di dalamnya. Tidak dapat dibatalkan!')
                    ->modalSubmitActionLabel('Ya, Hapus'),
            ])
            ->bulkActions([]);  // Bulk delete dihapus untuk keamanan
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListOnboardingFlows::route('/'),
            'create' => Pages\CreateOnboardingFlow::route('/create'),
            'edit'   => Pages\EditOnboardingFlow::route('/{record}/edit'),
        ];
    }
}
