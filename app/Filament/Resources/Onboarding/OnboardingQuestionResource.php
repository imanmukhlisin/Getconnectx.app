<?php

namespace App\Filament\Resources\Onboarding;

use App\Filament\Resources\Onboarding\OnboardingQuestionResource\Pages;
use App\Filament\Resources\Onboarding\OnboardingQuestionResource\RelationManagers;
use App\Models\Onboarding\OnboardingQuestion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OnboardingQuestionResource extends Resource
{
    protected static ?string $model = OnboardingQuestion::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';
    protected static ?string $navigationGroup = 'Onboarding Engine';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('step_id')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('order_index')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('type')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Fieldset::make('label')
                    ->label('Label')
                    ->schema([
                        Forms\Components\TextInput::make('label.id')->label('🇮🇩 Indonesian')->required(),
                        Forms\Components\TextInput::make('label.en')->label('🇬🇧 English')->required(),
                    ])->columns(2),
                Forms\Components\Fieldset::make('sub_label')
                    ->label('Sub Label')
                    ->schema([
                        Forms\Components\TextInput::make('sub_label.id')->label('🇮🇩 Indonesian'),
                        Forms\Components\TextInput::make('sub_label.en')->label('🇬🇧 English'),
                    ])->columns(2),
                Forms\Components\Fieldset::make('helper_text')
                    ->label('Helper Text')
                    ->schema([
                        Forms\Components\TextInput::make('helper_text.id')->label('🇮🇩 Indonesian'),
                        Forms\Components\TextInput::make('helper_text.en')->label('🇬🇧 English'),
                    ])->columns(2),
                Forms\Components\Fieldset::make('placeholder')
                    ->label('Placeholder')
                    ->schema([
                        Forms\Components\TextInput::make('placeholder.id')->label('🇮🇩 Indonesian'),
                        Forms\Components\TextInput::make('placeholder.en')->label('🇬🇧 English'),
                    ])->columns(2),
                Forms\Components\Toggle::make('required')
                    ->required(),
                Forms\Components\TextInput::make('validation'),
                Forms\Components\TextInput::make('depends_on'),
                Forms\Components\TextInput::make('meta'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('step_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('order_index')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->searchable(),
                Tables\Columns\IconColumn::make('required')
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
            'index' => Pages\ListOnboardingQuestions::route('/'),
            'create' => Pages\CreateOnboardingQuestion::route('/create'),
            'edit' => Pages\EditOnboardingQuestion::route('/{record}/edit'),
        ];
    }
}
