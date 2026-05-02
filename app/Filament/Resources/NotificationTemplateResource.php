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
                Forms\Components\Grid::make(3)->schema([
                    
                    // Kolom Kiri: Form Input
                    Forms\Components\Group::make()->schema([
                        Forms\Components\Section::make('Informasi Template')->schema([
                            Forms\Components\Select::make('type')
                                ->label('Tipe Notifikasi')
                                ->options([
                                    'push' => 'Push Notification',
                                    'email' => 'Email',
                                    'whatsapp' => 'WhatsApp',
                                ])
                                ->disabled()
                                ->extraAttributes(['class' => 'cursor-not-allowed opacity-70'])
                                ->required(),
                            Forms\Components\Select::make('name')
                                ->label('Nama Event (Trigger)')
                                ->options([
                                    'account_created' => 'Pendaftaran Akun Baru',
                                    'password_reset' => 'Permintaan Reset Password',
                                    'otp_login' => 'Kode OTP / Verifikasi',
                                    'new_match' => 'Koneksi Match Baru',
                                    'new_message' => 'Pesan Masuk',
                                ])
                                ->disabled()
                                ->extraAttributes(['class' => 'cursor-not-allowed opacity-70'])
                                ->required(),
                        ])->columns(2),

                        Forms\Components\Section::make('Bahasa Indonesia (ID)')->schema([
                            Forms\Components\TextInput::make('subject_id')
                                ->label('Subjek (ID)')
                                ->live(debounce: 500)
                                ->required(),
                            Forms\Components\Textarea::make('body_id')
                                ->label('Isi Pesan (ID)')
                                ->live(debounce: 500)
                                ->rows(4)
                                ->required(),
                        ]),

                        Forms\Components\Section::make('Bahasa Inggris (EN)')->schema([
                            Forms\Components\TextInput::make('subject_en')
                                ->label('Subjek (EN)')
                                ->live(debounce: 500)
                                ->required(),
                            Forms\Components\Textarea::make('body_en')
                                ->label('Isi Pesan (EN)')
                                ->live(debounce: 500)
                                ->rows(4)
                                ->required(),
                        ]),
                    ])->columnSpan(2),

                    // Kolom Kanan: Live Preview (Mobile)
                    Forms\Components\Group::make()->schema([
                        Forms\Components\Section::make('Live Preview (ID)')->schema([
                            Forms\Components\Placeholder::make('live_preview')
                                ->label('')
                                ->content(function (\Filament\Forms\Get $get) {
                                    return view('filament.components.notification-preview', [
                                        'type' => $get('type') ?: 'push',
                                        'subject' => $get('subject_id') ?: 'Judul Notifikasi...',
                                        'body' => $get('body_id') ?: 'Isi pesan akan muncul secara otomatis di sini...',
                                    ]);
                                })
                        ]),
                    ])->columnSpan(1),
                    
                ]),
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
                    Tables\Columns\TextColumn::make('name')
                        ->weight('bold')
                        ->size(Tables\Columns\TextColumn\TextColumnSize::Large)
                        ->formatStateUsing(fn ($state) => match ($state) {
                            'account_created' => 'Pendaftaran Akun Baru',
                            'password_reset'  => 'Permintaan Reset Password',
                            'otp_login'       => 'Kode OTP / Verifikasi',
                            'new_match'       => 'Koneksi Match Baru',
                            'new_message'     => 'Pesan Masuk Baru',
                            default           => $state,
                        }),

                    Tables\Columns\TextColumn::make('description')
                        ->state(fn ($record) => match ($record->name) {
                            'account_created' => 'Terkirim secara otomatis saat user baru saja berhasil menyelesaikan proses onboarding untuk pertama kalinya.',
                            'password_reset'  => 'Dikirim saat user menekan tombol "Lupa Password" untuk memberikan tautan pemulihan akses.',
                            'otp_login'       => 'Sistem mengirimkan 6 digit kode OTP rahasia untuk proses verifikasi login via WhatsApp.',
                            'new_match'       => 'Ditampilkan ketika algoritma ConnectX berhasil menemukan kecocokan profil / Co-Founder potensial.',
                            'new_message'     => 'Memberitahu user bahwa ada pesan masuk baru dari koneksi mereka yang belum terbaca.',
                            default           => 'Template notifikasi standar sistem.',
                        })
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

                        Tables\Columns\TextColumn::make('subject_id')
                            ->icon('heroicon-m-chat-bubble-left-ellipsis')
                            ->color('gray')
                            ->size(Tables\Columns\TextColumn\TextColumnSize::ExtraSmall)
                            ->limit(25)
                            ->alignRight(),
                    ]),
                ])->space(3)
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->recordClasses('transition-all duration-300 hover:-translate-y-1.5 hover:shadow-[0_15px_30px_-5px_rgba(249,115,22,0.15)] hover:border-orange-500/40 cursor-pointer')
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
