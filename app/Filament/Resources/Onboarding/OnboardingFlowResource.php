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
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $navigationGroup = 'Onboarding Engine';
    public static function getNavigationLabel(): string
    {
        return __('admin.nav.flows');
    }
    protected static ?int    $navigationSort  = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('admin.flow.info_section'))
                    ->description(__('admin.flow.info_desc'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('admin.flow.name'))
                            ->placeholder('Contoh: Onboarding Utama, Onboarding Freelancer')
                            ->helperText('Nama internal alur ini. Tidak terlihat oleh user.')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label(__('admin.flow.description'))
                            ->placeholder('Contoh: Alur standar untuk semua user baru ConnectX')
                            ->helperText('Catatan singkat tentang tujuan alur ini.')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_entry')
                            ->label(__('admin.flow.is_entry'))
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
                    ->label(__('admin.flow.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label(__('admin.flow.description'))
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->description),
                Tables\Columns\IconColumn::make('is_entry')
                    ->label(__('admin.flow.is_entry'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle'),
                Tables\Columns\TextColumn::make('steps_count')
                    ->label(__('admin.flow.steps_count'))
                    ->counts('steps')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('admin.common.last_updated'))
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make()->label(__('admin.common.edit')),
                Tables\Actions\DeleteAction::make()
                    ->label(__('admin.common.delete'))
                    ->requiresConfirmation()
                    ->modalHeading(__('admin.common.delete_confirm'))
                    ->modalDescription(__('admin.common.delete_desc'))
                    ->modalSubmitActionLabel(__('admin.common.yes_delete')),
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
