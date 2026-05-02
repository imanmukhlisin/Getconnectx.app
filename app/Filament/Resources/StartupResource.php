<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StartupResource\Pages;
use App\Models\User;
use App\Models\Tag;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StartupResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rocket-launch';
    protected static ?string $navigationLabel = 'Direktori Startup';
    protected static ?string $modelLabel = 'Startup';
    protected static ?string $pluralModelLabel = 'Direktori Startup';
    protected static ?string $navigationGroup = 'Ekosistem';
    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNotNull('startup_name')
            ->whereIn('role_category', ['founder', 'startup']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Startup')->schema([
                    Forms\Components\TextInput::make('startup_name')
                        ->label('Nama Startup')
                        ->required(),
                    Forms\Components\TextInput::make('startup_tagline')
                        ->label('Tagline')
                        ->required(),
                    Forms\Components\Textarea::make('startup_idea')
                        ->label('Ide / Deskripsi Singkat')
                        ->rows(3),
                    Forms\Components\Select::make('startup_stage')
                        ->label('Stage / Tahap')
                        ->options([
                            'idea' => 'Idea Stage',
                            'mvp' => 'MVP / Prototype',
                            'early_traction' => 'Early Traction',
                            'scaling' => 'Scaling',
                        ]),
                ])->columns(2),
                Forms\Components\Section::make('Detail Profil')->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nama Founder'),
                    Forms\Components\TextInput::make('email')
                        ->label('Email'),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('startup_name')
                    ->label('Startup')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (User $record): string => $record->startup_tagline ?? '-'),
                
                Tables\Columns\TextColumn::make('startup_stage')
                    ->label('Stage')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'idea' => 'Idea Stage',
                        'mvp' => 'MVP',
                        'early_traction' => 'Early Traction',
                        'scaling' => 'Scaling',
                        default => 'Belum Diatur',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'idea' => 'info',
                        'mvp' => 'warning',
                        'early_traction' => 'success',
                        'scaling' => 'primary',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('tags.name')
                    ->label('Industri / Minat')
                    ->badge()
                    ->limitList(3)
                    ->color('gray'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Founder')
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tgl Bergabung')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('startup_stage')
                    ->label('Filter Stage')
                    ->options([
                        'idea' => 'Idea Stage',
                        'mvp' => 'MVP / Prototype',
                        'early_traction' => 'Early Traction',
                        'scaling' => 'Scaling',
                    ]),
                Tables\Filters\SelectFilter::make('industry')
                    ->label('Industri')
                    ->relationship('tags', 'name', fn (Builder $query) => $query->where('category', 'industry')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStartups::route('/'),
            'edit' => Pages\EditStartup::route('/{record}/edit'),
        ];
    }
}
