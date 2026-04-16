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
    protected static ?string $navigationGroup = 'Onboarding Engine';
    protected static ?string $navigationLabel = 'Pilihan Jawaban';
    protected static ?int    $navigationSort  = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Pengaturan Pilihan')
                    ->description('Setiap pilihan adalah satu opsi jawaban yang bisa dipilih user dari pertanyaan bertipe "Select" atau "Multi Select".')
                    ->schema([
                        Forms\Components\Select::make('question_id')
                            ->label('Pertanyaan')
                            ->helperText('Pilih pertanyaan yang memiliki opsi jawaban ini.')
                            ->options(function () {
                                return OnboardingQuestion::all()
                                    ->mapWithKeys(function ($q) {
                                        $label = is_array($q->label)
                                            ? ($q->label['id'] ?? 'Pertanyaan #' . $q->order_index)
                                            : ($q->label ?? 'Pertanyaan #' . $q->order_index);
                                        return [$q->id => $label];
                                    });
                            })
                            ->searchable()
                            ->required(),

                        Forms\Components\TextInput::make('order_index')
                            ->label('Urutan Tampil')
                            ->helperText('Urutan pilihan ini dalam daftar (1 = paling atas).')
                            ->placeholder('Contoh: 1')
                            ->numeric()
                            ->minValue(1)
                            ->required(),

                        Forms\Components\TextInput::make('value')
                            ->label('Nilai (Value)')
                            ->helperText('Nilai teknis yang disimpan ke database ketika user memilih opsi ini. Gunakan huruf kecil tanpa spasi. Contoh: software_engineer')
                            ->placeholder('Contoh: software_engineer')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('icon')
                            ->label('Ikon (Opsional)')
                            ->helperText('Nama emoji atau kode ikon. Contoh: 💻 atau heroicon-o-computer-desktop')
                            ->placeholder('Contoh: 💻')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('group_name')
                            ->label('Nama Grup (Opsional)')
                            ->helperText('Jika pilihan dikelompokkan, isi nama grupnya. Contoh: "Teknologi", "Bisnis".')
                            ->placeholder('Contoh: Teknologi')
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Teks Pilihan')
                    ->description('Teks yang dilihat user saat memilih opsi ini. Isi dalam dua bahasa.')
                    ->schema([
                        Forms\Components\Fieldset::make('Label Pilihan (Wajib)')
                            ->schema([
                                Forms\Components\TextInput::make('label.id')
                                    ->label('🇮🇩 Teks Pilihan (Indonesia)')
                                    ->placeholder('Contoh: Software Engineer')
                                    ->required(),
                                Forms\Components\TextInput::make('label.en')
                                    ->label('🇬🇧 Option Label (English)')
                                    ->placeholder('Example: Software Engineer')
                                    ->required(),
                            ])->columns(2),

                        Forms\Components\Fieldset::make('Sub-label (Opsional)')
                            ->schema([
                                Forms\Components\TextInput::make('sub_label.id')
                                    ->label('🇮🇩 Keterangan Tambahan (Indonesia)')
                                    ->placeholder('Contoh: Membangun dan memelihara sistem perangkat lunak'),
                                Forms\Components\TextInput::make('sub_label.en')
                                    ->label('🇬🇧 Additional Description (English)')
                                    ->placeholder('Example: Build and maintain software systems'),
                            ])->columns(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('question.label')
                    ->label('Pertanyaan')
                    ->formatStateUsing(fn ($state) => is_array($state) ? ($state['id'] ?? '-') : $state)
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('order_index')
                    ->label('Urutan')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('label')
                    ->label('Teks Pilihan (ID)')
                    ->formatStateUsing(fn ($state) => is_array($state) ? ($state['id'] ?? '-') : $state)
                    ->searchable(),
                Tables\Columns\TextColumn::make('value')
                    ->label('Value')
                    ->searchable()
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('icon')
                    ->label('Ikon'),
                Tables\Columns\TextColumn::make('group_name')
                    ->label('Grup')
                    ->searchable(),
            ])
            ->defaultSort('order_index')
            ->filters([
                Tables\Filters\SelectFilter::make('question_id')
                    ->label('Filter Pertanyaan')
                    ->relationship('question', 'id'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Edit'),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Pilihan Jawaban?')
                    ->modalDescription('Tindakan ini akan menghapus pilihan jawaban ini secara permanen.')
                    ->modalSubmitActionLabel('Ya, Hapus'),
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
