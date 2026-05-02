<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationTemplateResource\Pages;
use App\Models\NotificationTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class NotificationTemplateResource extends Resource
{
    protected static ?string $model = NotificationTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';
    protected static ?string $navigationGroup = 'Sistem';
    protected static ?string $modelLabel = 'Template Notifikasi';
    protected static ?string $pluralModelLabel = 'Template Notifikasi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Template')->schema([
                    Forms\Components\TextInput::make('type')
                        ->label('Tipe Notifikasi')
                        ->disabled()
                        ->required(),
                    Forms\Components\TextInput::make('name')
                        ->label('Nama Template')
                        ->required()
                        ->maxLength(255),
                ])->columns(2),

                Forms\Components\Section::make('Bahasa Inggris (EN)')->schema([
                    Forms\Components\TextInput::make('subject_en')
                        ->label('Subjek (EN)')
                        ->required(),
                    Forms\Components\Textarea::make('body_en')
                        ->label('Isi Pesan (EN)')
                        ->rows(4)
                        ->required(),
                ]),

                Forms\Components\Section::make('Bahasa Indonesia (ID)')->schema([
                    Forms\Components\TextInput::make('subject_id')
                        ->label('Subjek (ID)')
                        ->required(),
                    Forms\Components\Textarea::make('body_id')
                        ->label('Isi Pesan (ID)')
                        ->rows(4)
                        ->required(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color('primary')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject_id')
                    ->label('Subjek (ID)')
                    ->limit(40),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
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
            'index' => Pages\ListNotificationTemplates::route('/'),
            'edit' => Pages\EditNotificationTemplate::route('/{record}/edit'),
        ];
    }
}
