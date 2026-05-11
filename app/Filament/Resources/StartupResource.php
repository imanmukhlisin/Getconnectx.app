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
        // Filter user yang punya startup_name (dulu disimpan di tabel User)
        return parent::getEloquentQuery()
            ->whereNotNull('startup_name')
            ->whereIn('role_category', ['founder', 'startup']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Startup')
                    ->icon('heroicon-o-rocket-launch')
                    ->schema([
                        Forms\Components\TextInput::make('startup_name')
                            ->label('Nama Startup')
                            ->required(),
                        Forms\Components\TextInput::make('startup_tagline')
                            ->label('Tagline')
                            ->required(),
                        Forms\Components\Select::make('startup_stage')
                            ->label('Stage / Tahap')
                            ->options([
                                'idea' => 'Idea Stage',
                                'mvp' => 'MVP / Prototype',
                                'early_traction' => 'Early Traction',
                                'scaling' => 'Scaling',
                            ]),
                        Forms\Components\Textarea::make('startup_idea')
                            ->label('Ide / Deskripsi Singkat')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Informasi Founder & Lokasi')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Founder'),
                        Forms\Components\TextInput::make('email')
                            ->label('Email'),
                        Forms\Components\TextInput::make('city')
                            ->label('Kota'),
                        Forms\Components\TextInput::make('country')
                            ->label('Negara'),
                        Forms\Components\TextInput::make('linkedin_url')
                            ->label('LinkedIn URL')
                            ->url()
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\ImageColumn::make('avatar_url')
                            ->label('Logo')
                            ->circular()
                            ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name='.urlencode($record->startup_name ?? $record->name).'&color=FFFFFF&background=09090b')
                            ->size(60)
                            ->extraImgAttributes(['class' => 'shadow-lg border-2 border-primary-500/30']),
                        
                        Tables\Columns\Layout\Stack::make([
                            Tables\Columns\TextColumn::make('startup_name')
                                ->weight('bold')
                                ->size(Tables\Columns\TextColumn\TextColumnSize::Large)
                                ->searchable(),
                            Tables\Columns\TextColumn::make('startup_tagline')
                                ->color('gray')
                                ->size(Tables\Columns\TextColumn\TextColumnSize::Small)
                                ->limit(50),
                        ])->space(1),
                    ])->from('md'),
                    
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('startup_stage')
                            ->badge()
                            ->icon('heroicon-m-chart-bar')
                            ->formatStateUsing(fn ($state) => match ($state) {
                                'idea' => 'Idea Stage',
                                'mvp' => 'MVP',
                                'early_traction' => 'Early Traction',
                                'scaling' => 'Scaling',
                                default => 'Unknown',
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'idea' => 'info',
                                'mvp' => 'warning',
                                'early_traction' => 'success',
                                'scaling' => 'primary',
                                default => 'gray',
                            }),
                            
                        Tables\Columns\TextColumn::make('tags.name')
                            ->badge()
                            ->limitList(2)
                            ->color('gray')
                            ->icon('heroicon-m-tag'),
                    ])->from('md')->extraAttributes(['class' => 'mt-4']),

                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('name')
                            ->icon('heroicon-m-user-circle')
                            ->description(fn($record) => $record->email, position: 'below')
                            ->size(Tables\Columns\TextColumn\TextColumnSize::Small)
                            ->searchable(),
                        
                        Tables\Columns\TextColumn::make('city')
                            ->icon('heroicon-m-map-pin')
                            ->formatStateUsing(fn($record) => ($record->city ?? 'Remote') . ($record->country ? ", {$record->country}" : ''))
                            ->size(Tables\Columns\TextColumn\TextColumnSize::Small)
                            ->color('gray'),
                    ])->from('md')->extraAttributes(['class' => 'mt-4 border-t border-gray-800 pt-3']),
                ])->space(2)
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('startup_stage')
                    ->label('Stage Startup')
                    ->options([
                        'idea' => 'Idea Stage',
                        'mvp' => 'MVP / Prototype',
                        'early_traction' => 'Early Traction',
                        'scaling' => 'Scaling',
                    ]),
                Tables\Filters\SelectFilter::make('industry')
                    ->label('Industri')
                    ->relationship('tags', 'name', fn (Builder $query) => $query->where('type', 'industry')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->button()->color('primary'),
                Tables\Actions\EditAction::make()->iconButton()->color('gray'),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ]);
    }

    public static function infolist(\Filament\Infolists\Infolist $infolist): \Filament\Infolists\Infolist
    {
        return $infolist
            ->schema([
                \Filament\Infolists\Components\Section::make()
                    ->schema([
                        \Filament\Infolists\Components\Split::make([
                            \Filament\Infolists\Components\ImageEntry::make('avatar_url')
                                ->hiddenLabel()
                                ->circular()
                                ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name='.urlencode($record->startup_name ?? $record->name).'&color=FFFFFF&background=09090b')
                                ->size(100)
                                ->extraImgAttributes(['class' => 'shadow-xl ring-2 ring-primary-500/50']),
                            
                            \Filament\Infolists\Components\Grid::make(1)
                                ->schema([
                                    \Filament\Infolists\Components\TextEntry::make('startup_name')
                                        ->hiddenLabel()
                                        ->size(\Filament\Infolists\Components\TextEntry\TextEntrySize::Large)
                                        ->weight('bold')
                                        ->color('primary'),
                                    \Filament\Infolists\Components\TextEntry::make('startup_tagline')
                                        ->hiddenLabel()
                                        ->color('gray'),
                                    \Filament\Infolists\Components\TextEntry::make('startup_stage')
                                        ->hiddenLabel()
                                        ->badge()
                                        ->formatStateUsing(fn ($state) => match ($state) {
                                            'idea' => '💡 Idea Stage',
                                            'mvp' => '🚀 MVP / Prototype',
                                            'early_traction' => '📈 Early Traction',
                                            'scaling' => '🔥 Scaling',
                                            default => $state,
                                        })
                                        ->color('primary'),
                                ]),
                        ])->from('md'),
                    ]),

                \Filament\Infolists\Components\Grid::make(3)
                    ->schema([
                        \Filament\Infolists\Components\Section::make('Statistik & Informasi')
                            ->icon('heroicon-o-chart-pie')
                            ->columnSpan(2)
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('startup_idea')
                                    ->label('Deskripsi / Ide Startup')
                                    ->prose()
                                    ->columnSpanFull(),
                                
                                \Filament\Infolists\Components\TextEntry::make('tags.name')
                                    ->label('Industri & Spesialisasi')
                                    ->badge()
                                    ->icon('heroicon-m-tag')
                                    ->color('gray')
                                    ->columnSpanFull(),
                            ]),

                        \Filament\Infolists\Components\Section::make('Profil Founder')
                            ->icon('heroicon-o-user')
                            ->columnSpan(1)
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('name')
                                    ->label('Nama Founder')
                                    ->icon('heroicon-m-user'),
                                \Filament\Infolists\Components\TextEntry::make('email')
                                    ->label('Email')
                                    ->icon('heroicon-m-envelope')
                                    ->copyable(),
                                \Filament\Infolists\Components\TextEntry::make('whatsapp_number')
                                    ->label('WhatsApp')
                                    ->icon('heroicon-m-device-phone-mobile')
                                    ->copyable()
                                    ->placeholder('Tidak tersedia'),
                                \Filament\Infolists\Components\TextEntry::make('linkedin_url')
                                    ->label('LinkedIn')
                                    ->icon('heroicon-m-link')
                                    ->url(fn ($record) => $record->linkedin_url)
                                    ->openUrlInNewTab()
                                    ->placeholder('Belum ada LinkedIn'),
                                \Filament\Infolists\Components\TextEntry::make('location')
                                    ->label('Lokasi')
                                    ->icon('heroicon-m-map-pin')
                                    ->getStateUsing(fn ($record) => ($record->city ?? 'Unknown') . ', ' . ($record->country ?? 'Unknown')),
                            ]),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStartups::route('/'),
            'view' => Pages\ViewStartup::route('/{record}'),
            'edit' => Pages\EditStartup::route('/{record}/edit'),
        ];
    }
}
