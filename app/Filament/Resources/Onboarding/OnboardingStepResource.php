<?php

namespace App\Filament\Resources\Onboarding;

use App\Filament\Resources\Onboarding\OnboardingStepResource\Pages;
use App\Filament\Resources\Onboarding\OnboardingStepResource\RelationManagers;
use App\Models\Onboarding\OnboardingStep;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OnboardingStepResource extends Resource
{
    protected static ?string $model = OnboardingStep::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';
    protected static ?string $navigationGroup = 'Onboarding Engine';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('flow_id')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('order_index')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('section')
                    ->maxLength(255),
                Forms\Components\Fieldset::make('title')
                    ->schema([
                        Forms\Components\TextInput::make('title.id')->label('Indonesian Title')->required(),
                        Forms\Components\TextInput::make('title.en')->label('English Title')->required(),
                    ]),
                Forms\Components\Fieldset::make('subtitle')
                    ->schema([
                        Forms\Components\TextInput::make('subtitle.id')->label('Indonesian Subtitle'),
                        Forms\Components\TextInput::make('subtitle.en')->label('English Subtitle'),
                    ]),
                Forms\Components\Fieldset::make('cta_label')
                    ->schema([
                        Forms\Components\TextInput::make('cta_label.id')->label('Indonesian CTA Label'),
                        Forms\Components\TextInput::make('cta_label.en')->label('English CTA Label'),
                    ]),
                Forms\Components\Toggle::make('auto_advance')
                    ->required(),
                Forms\Components\Toggle::make('can_go_back')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('flow_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->formatStateUsing(fn ($state) => is_array($state) ? ($state['en'] ?? '') : $state)
                    ->searchable(),
                Tables\Columns\TextColumn::make('order_index')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('section')
                    ->searchable(),
                Tables\Columns\IconColumn::make('auto_advance')
                    ->boolean(),
                Tables\Columns\IconColumn::make('can_go_back')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListOnboardingSteps::route('/'),
            'create' => Pages\CreateOnboardingStep::route('/create'),
            'edit' => Pages\EditOnboardingStep::route('/{record}/edit'),
        ];
    }
}
