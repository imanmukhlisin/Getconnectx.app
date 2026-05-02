<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Sistem';
    protected static ?string $modelLabel = 'Aktivitas Admin (Audit Log)';
    protected static ?string $pluralModelLabel = 'Audit Logs';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('log_name')
                    ->label('Tipe Log'),
                Forms\Components\TextInput::make('description')
                    ->label('Deskripsi'),
                Forms\Components\TextInput::make('subject_type')
                    ->label('Target Model'),
                Forms\Components\TextInput::make('event')
                    ->label('Event'),
                Forms\Components\TextInput::make('causer_type')
                    ->label('Aktor Model'),
                Forms\Components\KeyValue::make('properties')
                    ->label('Data Properties')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i:s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Aktor (Admin)')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Aktivitas')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Target')
                    ->formatStateUsing(fn ($state) => class_basename($state)),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivities::route('/'),
        ];
    }
}
