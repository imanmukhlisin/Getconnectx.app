<?php

namespace App\Filament\Resources\Onboarding;

use App\Filament\Resources\Onboarding\OnboardingStepResource\Pages;
use App\Models\Onboarding\OnboardingFlow;
use App\Models\Onboarding\OnboardingStep;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OnboardingStepResource extends Resource
{
    protected static ?string $model = OnboardingStep::class;

    protected static ?string $navigationIcon  = 'heroicon-o-list-bullet';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $navigationGroup = 'Onboarding Engine';
    public static function getNavigationLabel(): string
    {
        return __('admin.nav.steps');
    }
    protected static ?int    $navigationSort  = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('admin.step.info_section'))
                    ->description('Setiap "Step" adalah satu halaman yang dilihat user saat proses onboarding.')
                    ->schema([
                        Forms\Components\Select::make('flow_id')
                            ->label(__('admin.flow.name'))
                            ->helperText('Pilih alur onboarding tempat halaman ini berada.')
                            ->options(fn () => OnboardingFlow::pluck('name', 'id'))
                            ->searchable()
                            ->required(),

                        Forms\Components\TextInput::make('order_index')
                            ->label(__('admin.step.order_index'))
                            ->helperText('Angka urutan halaman ini dalam alur (1 = pertama, 2 = kedua, dst).')
                            ->placeholder('Contoh: 1')
                            ->numeric()
                            ->minValue(1)
                            ->required(),

                        Forms\Components\TextInput::make('section')
                            ->label(__('admin.step.section'))
                            ->helperText('Pengelompokan internal. Contoh: personal_info, skills, preferences.')
                            ->placeholder('Contoh: personal_info'),
                    ])->columns(3),

                Forms\Components\Section::make('Konten Teks')
                    ->description('Teks yang ditampilkan kepada user di halaman ini. Isi dalam dua bahasa (Indonesia & Inggris).')
                    ->schema([
                        Forms\Components\Fieldset::make(__('admin.step.title'))
                            ->schema([
                                Forms\Components\TextInput::make('title.id')
                                    ->label('🇮🇩 Judul (Indonesia)')
                                    ->placeholder('Contoh: Halo! Ceritakan tentang dirimu')
                                    ->required(),
                                Forms\Components\TextInput::make('title.en')
                                    ->label('🇬🇧 Judul (English)')
                                    ->placeholder('Example: Hello! Tell us about yourself')
                                    ->required(),
                            ])->columns(2),

                        Forms\Components\Fieldset::make(__('admin.step.subtitle'))
                            ->schema([
                                Forms\Components\TextInput::make('subtitle.id')
                                    ->label('🇮🇩 Subjudul (Indonesia)')
                                    ->placeholder('Contoh: Informasi ini membantu kami mencarikan koneksi terbaik'),
                                Forms\Components\TextInput::make('subtitle.en')
                                    ->label('🇬🇧 Subjudul (English)')
                                    ->placeholder('Example: This helps us find the best connections for you'),
                            ])->columns(2),

                        Forms\Components\Fieldset::make(__('admin.step.cta_label'))
                            ->schema([
                                Forms\Components\TextInput::make('cta_label.id')
                                    ->label('🇮🇩 Teks Tombol (Indonesia)')
                                    ->placeholder('Contoh: Lanjutkan'),
                                Forms\Components\TextInput::make('cta_label.en')
                                    ->label('🇬🇧 Teks Tombol (English)')
                                    ->placeholder('Example: Continue'),
                            ])->columns(2),
                    ]),

                Forms\Components\Section::make('Pengaturan Navigasi')
                    ->schema([
                        Forms\Components\Toggle::make('auto_advance')
                            ->label('⚡ ' . __('admin.step.auto_advance'))
                            ->helperText('Jika aktif, user otomatis berpindah ke halaman berikutnya setelah mengisi. Cocok untuk halaman dengan satu pertanyaan sederhana.'),

                        Forms\Components\Toggle::make('can_go_back')
                            ->label('↩️ ' . __('admin.step.can_go_back'))
                            ->helperText('Jika aktif, user bisa menekan tombol kembali untuk mengubah jawaban di halaman sebelumnya.'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('flow.name')
                    ->label(__('admin.flow.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('order_index')
                    ->label(__('admin.step.order_index'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label(__('admin.step.title'))
                    ->formatStateUsing(fn ($record) => $record->getTranslated('title'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('section')
                    ->label(__('admin.step.section'))
                    ->searchable(),
                Tables\Columns\IconColumn::make('auto_advance')
                    ->label(__('admin.step.auto_advance'))
                    ->boolean(),
                Tables\Columns\IconColumn::make('can_go_back')
                    ->label(__('admin.step.can_go_back'))
                    ->boolean(),
            ])
            ->defaultSort('order_index')
            ->filters([
                Tables\Filters\SelectFilter::make('flow_id')
                    ->label('Filter Alur')
                    ->relationship('flow', 'name'),
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
            'index'  => Pages\ListOnboardingSteps::route('/'),
            'create' => Pages\CreateOnboardingStep::route('/create'),
            'edit'   => Pages\EditOnboardingStep::route('/{record}/edit'),
        ];
    }
}
