<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TagResource\Pages;
use App\Models\Tag;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TagResource extends Resource
{
    protected static ?string $model = Tag::class;

    protected static ?string $navigationIcon  = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Master Data';
    protected static bool $shouldRegisterNavigation = false;
    public static function getNavigationLabel(): string
    {
        return __('admin.nav.tags');
    }
    protected static ?int    $navigationSort  = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Tag')
                    ->description('Tag digunakan untuk mengelompokkan keahlian (skill) atau bidang industri dari setiap user ConnectX.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Tag')
                            ->helperText('Nama yang akan ditampilkan kepada user. Contoh: React.js, Product Management, Fintech')
                            ->placeholder('Contoh: React.js')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('type')
                            ->label('Kategori Tag')
                            ->helperText('Pilih apakah ini tag keahlian teknis (Skill) atau bidang industri (Industri).')
                            ->options([
                                'skill'    => '🔧 Skill',
                                'industry' => '🏭 Industri',
                            ])
                            ->native(false)
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Tag')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'skill'    => 'info',
                        'industry' => 'success',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'skill'    => '🔧 Skill',
                        'industry' => '🏭 Industri',
                        default    => $state,
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin.common.last_updated'))
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Filter Kategori')
                    ->options([
                        'skill'    => '🔧 Skill',
                        'industry' => '🏭 Industri',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label(__('admin.common.edit')),
                Tables\Actions\DeleteAction::make()
                    ->label(__('admin.common.delete'))
                    ->requiresConfirmation()
                    ->modalHeading(__('admin.common.delete_confirm'))
                    ->modalDescription(__('admin.common.delete_confirm'))
                    ->modalSubmitActionLabel(__('admin.common.yes_delete')),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTags::route('/'),
            'create' => Pages\CreateTag::route('/create'),
            'edit'   => Pages\EditTag::route('/{record}/edit'),
        ];
    }
}
