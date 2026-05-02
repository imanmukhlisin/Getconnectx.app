<?php

namespace App\Filament\Resources\Onboarding;

use App\Filament\Resources\Onboarding\OnboardingOptionResource\Pages;
use App\Models\Onboarding\OnboardingOption;
use App\Models\Onboarding\OnboardingQuestion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OnboardingOptionResource extends Resource
{
    protected static ?string $model = OnboardingOption::class;

    protected static ?string $navigationIcon  = 'heroicon-o-squares-plus';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $navigationGroup = 'Onboarding Engine';
    public static function getNavigationLabel(): string
    {
        return __('admin.nav.options');
    }
    protected static ?int    $navigationSort  = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('admin.option.info_section'))
                    ->description('Setiap pilihan adalah satu opsi jawaban yang bisa dipilih user dari pertanyaan bertipe "Select" atau "Multi Select".')
                    ->schema([
                        Forms\Components\Select::make('question_id')
                            ->label(__('admin.nav.questions'))
                            ->helperText('Pilih pertanyaan yang memiliki opsi jawaban ini.')
                            ->options(function () {
                                return OnboardingQuestion::all()
                                    ->mapWithKeys(function ($q) {
                                        $label = $q->getTranslated('label') ?? 'Pertanyaan #' . $q->order_index;
                                        return [$q->id => $label];
                                    });
                            })
                            ->searchable()
                            ->required(),

                        Forms\Components\TextInput::make('order_index')
                            ->label(__('admin.option.order_index'))
                            ->helperText('Urutan pilihan ini dalam daftar (1 = paling atas).')
                            ->placeholder('Contoh: 1')
                            ->numeric()
                            ->minValue(1)
                            ->required(),

                        Forms\Components\TextInput::make('value')
                            ->label(__('admin.option.value'))
                            ->helperText('Nilai teknis yang disimpan ke database ketika user memilih opsi ini. Gunakan huruf kecil tanpa spasi. Contoh: software_engineer')
                            ->placeholder('Contoh: software_engineer')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('icon')
                            ->label(__('admin.option.icon'))
                            ->helperText('Nama emoji atau kode ikon. Contoh: 💻 atau heroicon-o-computer-desktop')
                            ->placeholder('Contoh: 💻')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('group_name')
                            ->label(__('admin.option.group_name'))
                            ->helperText('Jika pilihan dikelompokkan, isi nama grupnya. Contoh: "Teknologi", "Bisnis".')
                            ->placeholder('Contoh: Teknologi')
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make(__('admin.option.label'))
                    ->description('Teks yang dilihat user saat memilih opsi ini. Isi dalam dua bahasa.')
                    ->schema([
                        Forms\Components\Fieldset::make(__('admin.option.label'))
                            ->schema([
                                Forms\Components\TextInput::make('label.id')
                                    ->label('🇮🇩 ' . __('admin.option.label') . ' (Indonesia)')
                                    ->placeholder('Contoh: Software Engineer')
                                    ->required(),
                                Forms\Components\TextInput::make('label.en')
                                    ->label('🇬🇧 ' . __('admin.option.label') . ' (English)')
                                    ->placeholder('Example: Software Engineer')
                                    ->required(),
                            ])->columns(2),

                        Forms\Components\Fieldset::make(__('admin.option.sub_label'))
                            ->schema([
                                Forms\Components\TextInput::make('sub_label.id')
                                    ->label('🇮🇩 ' . __('admin.option.sub_label') . ' (Indonesia)')
                                    ->placeholder('Contoh: Membangun dan memelihara sistem perangkat lunak'),
                                Forms\Components\TextInput::make('sub_label.en')
                                    ->label('🇬🇧 ' . __('admin.option.sub_label') . ' (English)')
                                    ->placeholder('Example: Build and maintain software systems'),
                            ])->columns(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(20),
                Tables\Columns\TextColumn::make('question_id')
                    ->label(__('admin.nav.questions') . ' (ID)')
                    ->searchable()
                    ->limit(20)
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('order_index')
                    ->label(__('admin.step.order_index'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('label')
                    ->label(__('admin.option.label'))
                    ->formatStateUsing(fn ($record) => $record->getTranslated('label'))
                    ->limit(30),
                Tables\Columns\TextColumn::make('value')
                    ->label(__('admin.option.value'))
                    ->badge()
                    ->color('gray')
                    ->limit(20),
                Tables\Columns\TextColumn::make('group_name')
                    ->label(__('admin.option.group_name'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('order_index')
            ->paginationPageOptions([10, 25])
            ->defaultPaginationPageOption(10)
            ->filters([
                Tables\Filters\SelectFilter::make('question_id')
                    ->label('Filter Pertanyaan')
                    ->options(fn () => \App\Models\Onboarding\OnboardingQuestion::query()
                        ->orderBy('step_id')
                        ->pluck('id', 'id')
                        ->toArray()
                    )
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label(__('admin.common.edit')),
                Tables\Actions\DeleteAction::make()
                    ->label(__('admin.common.delete'))
                    ->requiresConfirmation()
                    ->modalHeading(__('admin.common.delete_confirm'))
                    ->modalDescription(__('admin.common.delete_desc'))
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
            'index'  => Pages\ListOnboardingOptions::route('/'),
            'create' => Pages\CreateOnboardingOption::route('/create'),
            'edit'   => Pages\EditOnboardingOption::route('/{record}/edit'),
        ];
    }
}
