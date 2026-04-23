<?php

namespace App\Filament\Resources\Onboarding;

use App\Filament\Resources\Onboarding\OnboardingQuestionResource\Pages;
use App\Models\Onboarding\OnboardingFlow;
use App\Models\Onboarding\OnboardingQuestion;
use App\Models\Onboarding\OnboardingStep;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OnboardingQuestionResource extends Resource
{
    protected static ?string $model = OnboardingQuestion::class;

    protected static ?string $navigationIcon  = 'heroicon-o-question-mark-circle';
    protected static ?string $navigationGroup = 'Onboarding Engine';
    public static function getNavigationLabel(): string
    {
        return __('admin.nav.questions');
    }
    protected static ?int    $navigationSort  = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('admin.question.info_section'))
                    ->description('Pertanyaan yang muncul pada salah satu halaman (step) onboarding.')
                    ->schema([
                        Forms\Components\Select::make('step_id')
                            ->label(__('admin.nav.steps'))
                            ->helperText('Pilih halaman tempat pertanyaan ini ditampilkan.')
                            ->options(function () {
                                return OnboardingStep::with('flow')
                                    ->get()
                                    ->mapWithKeys(function ($step) {
                                        $flowName = $step->flow?->name ?? 'Tanpa Alur';
                                        $title    = $step->getTranslated('title') ?? 'Step #' . $step->order_index;
                                        return [$step->id => "[{$flowName}] {$title}"];
                                    });
                            })
                            ->searchable()
                            ->required(),

                        Forms\Components\TextInput::make('order_index')
                            ->label(__('admin.question.order_index'))
                            ->helperText('Urutan tampil pertanyaan dalam halaman (1 = pertama).')
                            ->placeholder('Contoh: 1')
                            ->numeric()
                            ->minValue(1)
                            ->required(),

                        Forms\Components\Select::make('type')
                            ->label(__('admin.question.type'))
                            ->helperText('Jenis tampilan input yang digunakan user untuk menjawab.')
                            ->options([
                                'text'          => '📝 Text (Teks bebas)',
                                'select'        => '🔽 Select (Pilih satu)',
                                'multi_select'  => '☑️ Multi Select (Pilih banyak)',
                                'date'          => '📅 Date (Tanggal)',
                                'tag_selector'  => '🏷️ Tag Selector (Pilih tag)',
                                'photo_upload'  => '📷 Photo Upload (Unggah foto)',
                                'number'        => '🔢 Number (Angka)',
                            ])
                            ->searchable()
                            ->required(),

                        Forms\Components\Toggle::make('required')
                            ->label(__('admin.question.required'))
                            ->helperText('Jika aktif, user tidak bisa melewati pertanyaan ini tanpa menjawab.')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make(__('admin.question.label'))
                    ->description('Teks yang ditampilkan kepada user. Isi dalam dua bahasa.')
                    ->schema([
                        Forms\Components\Fieldset::make(__('admin.question.label'))
                            ->schema([
                                Forms\Components\TextInput::make('label.id')
                                    ->label('🇮🇩 ' . __('admin.question.label') . ' (Indonesia)')
                                    ->placeholder('Contoh: Apa nama lengkap kamu?')
                                    ->required(),
                                Forms\Components\TextInput::make('label.en')
                                    ->label('🇬🇧 ' . __('admin.question.label') . ' (English)')
                                    ->placeholder('Example: What is your full name?')
                                    ->required(),
                            ])->columns(2),

                        Forms\Components\Fieldset::make(__('admin.question.sub_label'))
                            ->schema([
                                Forms\Components\TextInput::make('sub_label.id')
                                    ->label('🇮🇩 ' . __('admin.question.sub_label') . ' (Indonesia)')
                                    ->placeholder('Contoh: Gunakan nama yang tertera di KTP'),
                                Forms\Components\TextInput::make('sub_label.en')
                                    ->label('🇬🇧 ' . __('admin.question.sub_label') . ' (English)')
                                    ->placeholder('Example: Use the name on your ID card'),
                            ])->columns(2),

                        Forms\Components\Fieldset::make(__('admin.question.helper_text'))
                            ->schema([
                                Forms\Components\TextInput::make('helper_text.id')
                                    ->label('🇮🇩 ' . __('admin.question.helper_text') . ' (Indonesia)')
                                    ->placeholder('Contoh: Nama ini akan ditampilkan ke koneksi kamu'),
                                Forms\Components\TextInput::make('helper_text.en')
                                    ->label('🇬🇧 ' . __('admin.question.helper_text') . ' (English)')
                                    ->placeholder('Example: This name will be shown to your connections'),
                            ])->columns(2),

                        Forms\Components\Fieldset::make(__('admin.question.placeholder'))
                            ->schema([
                                Forms\Components\TextInput::make('placeholder.id')
                                    ->label('🇮🇩 ' . __('admin.question.placeholder') . ' (Indonesia)')
                                    ->placeholder('Contoh: Masukkan nama kamu...'),
                                Forms\Components\TextInput::make('placeholder.en')
                                    ->label('🇬🇧 ' . __('admin.question.placeholder') . ' (English)')
                                    ->placeholder('Example: Enter your name...'),
                            ])->columns(2),
                    ]),

                Forms\Components\Section::make('⚙️ Pengaturan Lanjutan')
                    ->description('Pengaturan teknis untuk developer. Tidak perlu diubah untuk operasional normal.')
                    ->schema([
                        Forms\Components\Textarea::make('validation')
                            ->label(__('admin.question.validation'))
                            ->helperText('Contoh: {"min": 3, "max": 100}')
                            ->rows(3),
                        Forms\Components\Textarea::make('depends_on')
                            ->label(__('admin.question.depends_on'))
                            ->helperText('Pertanyaan ini hanya muncul jika kondisi tertentu terpenuhi.')
                            ->rows(3),
                        Forms\Components\Textarea::make('meta')
                            ->label('Data Tambahan (JSON)')
                            ->helperText('Konfigurasi tambahan yang diperlukan oleh engine.')
                            ->rows(3),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('step.flow.name')
                    ->label(__('admin.flow.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('step.order_index')
                    ->label(__('admin.nav.steps'))
                    ->formatStateUsing(fn ($state) => "Step #{$state}")
                    ->sortable(),
                Tables\Columns\TextColumn::make('order_index')
                    ->label(__('admin.step.order_index'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('label')
                    ->label(__('admin.question.label'))
                    ->formatStateUsing(fn ($record) => $record->getTranslated('label'))
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('admin.question.type'))
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'text'         => 'info',
                        'select'       => 'success',
                        'multi_select' => 'warning',
                        'tag_selector' => 'gray',
                        default        => 'gray',
                    }),
                Tables\Columns\IconColumn::make('required')
                    ->label(__('admin.question.required'))
                    ->boolean(),
            ])
            ->defaultSort('order_index')
            ->filters([
                Tables\Filters\SelectFilter::make('step_id')
                    ->label('Filter Halaman')
                    ->relationship('step', 'id'),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Filter Tipe')
                    ->options([
                        'text'         => 'Text',
                        'select'       => 'Select',
                        'multi_select' => 'Multi Select',
                        'date'         => 'Date',
                        'tag_selector' => 'Tag Selector',
                    ]),
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
            'index'  => Pages\ListOnboardingQuestions::route('/'),
            'create' => Pages\CreateOnboardingQuestion::route('/create'),
            'edit'   => Pages\EditOnboardingQuestion::route('/{record}/edit'),
        ];
    }
}
