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

    /**
     * Mapping nama event teknis → label ramah pengguna
     */
    private static function eventLabels(): array
    {
        return [
            'account_created'       => 'Pendaftaran Akun Baru',
            'password_reset'        => 'Permintaan Reset Password',
            'otp_login'             => 'Kode OTP / Verifikasi',
            'new_match'             => 'Koneksi Match Baru',
            'new_message'           => 'Pesan Masuk',
            'linkedin_sync_complete'=> 'Sinkronisasi LinkedIn Selesai',
        ];
    }

    /**
     * Deskripsi ramah pengguna per event
     */
    private static function eventDescriptions(): array
    {
        return [
            'account_created'       => 'Terkirim secara otomatis saat user baru saja berhasil menyelesaikan proses onboarding untuk pertama kalinya.',
            'password_reset'        => 'Dikirim saat user menekan tombol "Lupa Password" untuk memberikan tautan pemulihan akses.',
            'otp_login'             => 'Sistem mengirimkan 6 digit kode OTP rahasia untuk proses verifikasi login via WhatsApp.',
            'new_match'             => 'Ditampilkan ketika algoritma ConnectX berhasil menemukan kecocokan profil / Co-Founder potensial.',
            'new_message'           => 'Memberitahu user bahwa ada pesan masuk baru dari koneksi mereka yang belum terbaca.',
            'linkedin_sync_complete'=> 'Dikirim ketika profil LinkedIn user berhasil ditarik dan disinkronisasi ke ConnectX secara otomatis.',
        ];
    }

    /**
     * Warna border kartu per event
     */
    private static function eventColors(): array
    {
        return [
            'account_created'       => 'border-t-green-500 hover:shadow-[0_15px_30px_-5px_rgba(34,197,94,0.15)]',
            'password_reset'        => 'border-t-red-500 hover:shadow-[0_15px_30px_-5px_rgba(239,68,68,0.15)]',
            'otp_login'             => 'border-t-blue-500 hover:shadow-[0_15px_30px_-5px_rgba(59,130,246,0.15)]',
            'new_match'             => 'border-t-purple-500 hover:shadow-[0_15px_30px_-5px_rgba(168,85,247,0.15)]',
            'new_message'           => 'border-t-orange-500 hover:shadow-[0_15px_30px_-5px_rgba(249,115,22,0.15)]',
            'linkedin_sync_complete'=> 'border-t-sky-500 hover:shadow-[0_15px_30px_-5px_rgba(14,165,233,0.15)]',
        ];
    }

    /**
     * Placeholder yang tersedia per event (token yang bisa dipakai di pesan)
     */
    private static function eventPlaceholders(): array
    {
        return [
            'account_created'       => ['[name]' => 'Nama User', '[nama]' => 'Nama User (ID)'],
            'password_reset'        => ['[name]' => 'Nama User', '[nama]' => 'Nama User (ID)', '[link]' => 'Link Reset'],
            'otp_login'             => ['[otp]' => 'Kode OTP', '[name]' => 'Nama User'],
            'new_match'             => ['[name]' => 'Nama Match', '[nama]' => 'Nama Match (ID)'],
            'new_message'           => ['[sender_name]' => 'Nama Pengirim', '[message]' => 'Isi Pesan'],
            'linkedin_sync_complete'=> ['[name]' => 'Nama User', '[nama]' => 'Nama User (ID)'],
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(1)->schema([

                    // ── Info Event (Read-only, non-developer friendly) ──
                    Forms\Components\Section::make('Informasi Template')
                        ->icon('heroicon-m-information-circle')
                        ->description('Informasi teknis template ini bersifat permanen.')
                        ->schema([
                            Forms\Components\Placeholder::make('event_info')
                                ->label('Nama Event (Trigger)')
                                ->content(fn ($record) => new \Illuminate\Support\HtmlString(
                                    '<span class="px-3 py-1 rounded-lg bg-primary-500/10 text-primary-400 font-bold border border-primary-500/20 shadow-[0_0_15px_-3px_rgba(var(--primary-500),0.3)]">' .
                                    (self::eventLabels()[$record?->name] ?? $record?->name ?? '-') .
                                    '</span>'
                                )),
                            Forms\Components\Placeholder::make('type_info')
                                ->label('Tipe Notifikasi')
                                ->content(fn ($record) => new \Illuminate\Support\HtmlString(
                                    match ($record?->type) {
                                        'push'     => '<span class="px-3 py-1 rounded-lg bg-sky-500/10 text-sky-400 font-bold border border-sky-500/20 shadow-[0_0_15px_-3px_rgba(14,165,233,0.3)]">📱 Push Notification</span>',
                                        'whatsapp' => '<span class="px-3 py-1 rounded-lg bg-green-500/10 text-green-400 font-bold border border-green-500/20 shadow-[0_0_15px_-3px_rgba(34,197,94,0.3)]">💬 WhatsApp</span>',
                                        'email'    => '<span class="px-3 py-1 rounded-lg bg-amber-500/10 text-amber-400 font-bold border border-amber-500/20 shadow-[0_0_15px_-3px_rgba(245,158,11,0.3)]">✉️ Email</span>',
                                        default    => '<span class="px-3 py-1 rounded-lg bg-gray-500/10 text-gray-400 font-bold border border-gray-500/20">'.($record?->type ?? '-').'</span>',
                                    }
                                )),
                            Forms\Components\Placeholder::make('event_desc')
                                ->label('Apa kegunaan template ini?')
                                ->content(fn ($record) => new \Illuminate\Support\HtmlString(
                                    '<div class="text-gray-400 text-sm leading-relaxed italic border-l-2 border-gray-700 pl-4 mt-1">' .
                                    (self::eventDescriptions()[$record?->name] ?? 'Template notifikasi standar sistem.') .
                                    '</div>'
                                ))
                                ->columnSpanFull(),
                        ])->columns(2),

                    // ── Panduan Variabel / Token ──
                    Forms\Components\Section::make('Variabel yang Tersedia')
                        ->description('Klik tombol variabel di bawah untuk menyalin kode-nya, lalu tempel di kolom pesan. Jangan mengubah format [..] karena sistem membutuhkannya persis seperti itu.')
                        ->schema([
                            Forms\Components\ViewField::make('placeholders_guide')
                                ->label('')
                                ->view('filament.forms.components.placeholder-tokens')
                                ->viewData([
                                    'tokens' => [],
                                ])
                                ->afterStateHydrated(function ($component, $record) {
                                    if ($record) {
                                        $tokens = self::eventPlaceholders()[$record->name] ?? [];
                                        $component->viewData(['tokens' => $tokens]);
                                    }
                                }),
                        ]),

                    // ── Form Pesan ──
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\Section::make('🇮🇩 Bahasa Indonesia')->schema([
                            Forms\Components\TextInput::make('title.id')
                                ->label('Judul Notifikasi (ID)')
                                ->live(debounce: 500)
                                ->required(),
                            Forms\Components\Textarea::make('body.id')
                                ->label('Isi Pesan (ID)')
                                ->helperText('Gunakan variabel dari panduan di atas. Contoh: Halo [nama], profil kamu sudah siap!')
                                ->live(debounce: 500)
                                ->rows(5)
                                ->required(),
                        ]),

                        Forms\Components\Section::make('🇬🇧 English')->schema([
                            Forms\Components\TextInput::make('title.en')
                                ->label('Notification Title (EN)')
                                ->live(debounce: 500)
                                ->required(),
                            Forms\Components\Textarea::make('body.en')
                                ->label('Message Body (EN)')
                                ->helperText('Use variables from the guide above. Example: Hi [name], your profile is ready!')
                                ->live(debounce: 500)
                                ->rows(5)
                                ->required(),
                        ]),
                    ]),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        $labels = self::eventLabels();
        $descriptions = self::eventDescriptions();

        return $table
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\TextColumn::make('name')
                        ->weight('bold')
                        ->size(Tables\Columns\TextColumn\TextColumnSize::Large)
                        ->formatStateUsing(fn ($state) => $labels[$state] ?? $state),

                    Tables\Columns\TextColumn::make('description')
                        ->state(fn ($record) => $descriptions[$record->name] ?? 'Template notifikasi standar sistem.')
                        ->color('gray')
                        ->size(Tables\Columns\TextColumn\TextColumnSize::Small)
                        ->wrap()
                        ->extraAttributes(['class' => 'mt-1 mb-4 leading-relaxed']),

                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('type')
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'push'     => 'info',
                                'whatsapp' => 'success',
                                'email'    => 'warning',
                                default    => 'primary',
                            })
                            ->formatStateUsing(fn ($state) => match ($state) {
                                'push'     => '📱 Push Notif',
                                'whatsapp' => '💬 WhatsApp',
                                'email'    => '✉️ Email',
                                default    => $state,
                            })
                            ->grow(false),

                        Tables\Columns\TextColumn::make('title.id')
                            ->icon('heroicon-m-chat-bubble-left-ellipsis')
                            ->color('gray')
                            ->size(Tables\Columns\TextColumn\TextColumnSize::ExtraSmall)
                            ->limit(25)
                            ->alignRight(),
                    ]),
                ])->space(3)
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make()->label('Edit'),
            ])
            ->recordClasses(function ($record) {
                $colors = self::eventColors();
                $color = $colors[$record->name] ?? 'border-t-gray-500 hover:shadow-[0_15px_30px_-5px_rgba(107,114,128,0.15)]';
                return "border-t-4 {$color} transition-all duration-300 hover:-translate-y-1.5 cursor-pointer";
            })
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotificationTemplates::route('/'),
            'edit'  => Pages\EditNotificationTemplate::route('/{record}/edit'),
        ];
    }
}
