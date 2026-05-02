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
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color('primary')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject_id')
                    ->label('Subjek (ID)')
                    ->limit(40),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
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
