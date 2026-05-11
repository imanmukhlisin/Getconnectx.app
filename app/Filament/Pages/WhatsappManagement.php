<?php

namespace App\Filament\Pages;

use App\Models\WhatsappLog;
use App\Models\WhatsappBlast;
use App\Models\WhatsappWebhookConfig;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;

class WhatsappManagement extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-device-phone-mobile';
    protected static ?string $navigationGroup = 'WhatsApp Webhook';
    protected static ?string $navigationLabel = 'Pengaturan & Webhook';
    protected static ?string $title           = 'WhatsApp API Management';
    protected static ?int    $navigationSort  = 1;

    protected static string $view = 'filament.pages.whatsapp-management';

    // ─── Form state ────────────────────────────────────────────────────────────
    public ?array $data = [];

    public function mount(): void
    {
        $config = WhatsappWebhookConfig::first();
        $this->form->fill($config ? $config->toArray() : [
            'provider'       => 'saungwa',
            'is_active'      => false,
            'api_version'    => 'v19.0',
            'webhook_fields' => ['messages', 'message_deliveries', 'message_reads'],
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // ── Provider Selection ─────────────────────────────────────────
                Forms\Components\Section::make('Provider WhatsApp Aktif')
                    ->icon('heroicon-m-signal')
                    ->description('Pilih provider WhatsApp yang digunakan sistem saat ini.')
                    ->schema([
                        Forms\Components\Select::make('provider')
                            ->label('Provider')
                            ->options([
                                'saungwa' => '🟢 SaungWA (Aktif)',
                                'meta'    => '🔵 Meta WABA (WhatsApp Business API)',
                                'fonnte'  => '🟡 Fonnte',
                                'twilio'  => '🟣 Twilio',
                            ])
                            ->live()
                            ->required(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktifkan Konfigurasi ini')
                            ->helperText('Hanya satu konfigurasi yang aktif dalam satu waktu.')
                            ->onColor('success'),
                    ])->columns(2),

                // ── SaungWA Settings ───────────────────────────────────────────
                Forms\Components\Section::make('🟢 Konfigurasi SaungWA')
                    ->icon('heroicon-m-key')
                    ->description('Konfigurasi SaungWA yang sudah aktif sekarang.')
                    ->visible(fn (Forms\Get $get) => $get('provider') === 'saungwa')
                    ->schema([
                        Forms\Components\Placeholder::make('saungwa_info')
                            ->label('')
                            ->content(new HtmlString('
                                <div class="rounded-xl border border-green-500/30 bg-green-500/5 p-4 text-sm text-green-400 space-y-1">
                                    <div class="font-bold text-green-300">✅ SaungWA sudah aktif via .env</div>
                                    <div>App Key: <span class="font-mono bg-green-950/50 px-2 py-0.5 rounded">782591d4-••••</span></div>
                                    <div>API URL: <span class="font-mono bg-green-950/50 px-2 py-0.5 rounded">https://app.saungwa.com/api/create-message</span></div>
                                    <div class="mt-2 text-xs text-green-600">Untuk mengubah, update <code>SAUNGWA_APP_KEY</code> dan <code>SAUNGWA_AUTH_KEY</code> di file .env</div>
                                </div>
                            ')),
                    ]),

                // ── Meta WABA Settings ─────────────────────────────────────────
                Forms\Components\Section::make('🔵 Meta WhatsApp Business API')
                    ->icon('heroicon-m-key')
                    ->description('Konfigurasi Meta WABA. Access token & Phone Number ID dari Meta Business Dashboard.')
                    ->visible(fn (Forms\Get $get) => $get('provider') === 'meta')
                    ->columns(2)
                    ->schema([
                        // Status Banner
                        Forms\Components\Placeholder::make('meta_status_banner')
                            ->label('')
                            ->content(new HtmlString('
                                <div class="rounded-xl border border-amber-500/30 bg-amber-500/5 p-4 text-sm text-amber-400">
                                    <div class="font-bold text-amber-300">⏳ Menunggu Konfigurasi Meta</div>
                                    <div class="mt-1 text-xs text-amber-600">Isi Phone Number ID, WABA ID, dan Access Token dari Meta Business Suite untuk mengaktifkan WhatsApp Business API.</div>
                                </div>
                            '))
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('phone_number_id')
                            ->label('Phone Number ID')
                            ->placeholder('1234567890123456')
                            ->helperText('Dari Meta Business → WhatsApp → Phone Numbers')
                            ->prefixIcon('heroicon-m-phone'),

                        Forms\Components\TextInput::make('waba_id')
                            ->label('WhatsApp Business Account ID (WABA ID)')
                            ->placeholder('1234567890123456')
                            ->helperText('Dari Meta Business Settings → Business Account ID')
                            ->prefixIcon('heroicon-m-building-office'),

                        Forms\Components\TextInput::make('access_token')
                            ->label('Access Token')
                            ->placeholder('EAAxxxxxxxxxx...')
                            ->helperText('Permanent token dari Meta System User. JANGAN share!')
                            ->password()
                            ->revealable()
                            ->prefixIcon('heroicon-m-lock-closed')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('app_secret')
                            ->label('App Secret')
                            ->placeholder('abc123def456...')
                            ->helperText('Untuk verifikasi signature webhook. Dari Meta App Dashboard.')
                            ->password()
                            ->revealable()
                            ->prefixIcon('heroicon-m-shield-check'),

                        Forms\Components\TextInput::make('api_version')
                            ->label('Graph API Version')
                            ->placeholder('v19.0')
                            ->default('v19.0')
                            ->helperText('Cek versi terbaru di developers.facebook.com'),
                    ]),

                // ── Webhook Config ──────────────────────────────────────────────
                Forms\Components\Section::make('🔗 Konfigurasi Webhook')
                    ->icon('heroicon-m-globe-alt')
                    ->description('Webhook URL ini didaftarkan di Meta App Dashboard untuk menerima event dari WhatsApp.')
                    ->visible(fn (Forms\Get $get) => $get('provider') === 'meta')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Placeholder::make('webhook_url_display')
                            ->label('Webhook URL (daftarkan di Meta)')
                            ->content(fn () => new HtmlString(
                                '<div class="font-mono text-sm bg-gray-900 border border-gray-700 rounded-lg px-4 py-2 text-primary-400 flex items-center gap-2">' .
                                '<span class="text-gray-500">POST</span> ' .
                                config('app.url') . '/api/v1/webhook/whatsapp' .
                                '</div>'
                            ))
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('verify_token')
                            ->label('Verify Token')
                            ->placeholder('connectx_wa_verify_2026')
                            ->helperText('String rahasia untuk verifikasi webhook Meta. Bebas diisi, harus sama dengan yang di Meta Dashboard.')
                            ->prefixIcon('heroicon-m-finger-print'),

                        Forms\Components\CheckboxList::make('webhook_fields')
                            ->label('Subscribe Fields Webhook')
                            ->options([
                                'messages'             => 'messages — Pesan masuk',
                                'message_deliveries'   => 'message_deliveries — Konfirmasi terkirim',
                                'message_reads'        => 'message_reads — Konfirmasi dibaca',
                                'message_echoes'       => 'message_echoes — Echo pesan keluar',
                                'messaging_optins'     => 'messaging_optins — Opt-in user',
                                'messaging_optouts'    => 'messaging_optouts — Opt-out user',
                            ])
                            ->default(['messages', 'message_deliveries', 'message_reads'])
                            ->columns(2),
                    ]),

                // ── Webhook Status ──────────────────────────────────────────────
                Forms\Components\Section::make('📊 Status Webhook')
                    ->icon('heroicon-m-signal')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Placeholder::make('webhook_status')
                            ->label('Status Verifikasi')
                            ->content(fn () => new HtmlString(
                                WhatsappWebhookConfig::first()?->webhook_verified
                                    ? '<span class="px-3 py-1 rounded-full bg-green-500/15 text-green-400 border border-green-500/25 text-sm font-bold">✅ Terverifikasi</span>'
                                    : '<span class="px-3 py-1 rounded-full bg-amber-500/15 text-amber-400 border border-amber-500/25 text-sm font-bold">⏳ Belum Diverifikasi</span>'
                            )),

                        Forms\Components\Placeholder::make('last_webhook')
                            ->label('Webhook Terakhir Diterima')
                            ->content(fn () =>
                                WhatsappWebhookConfig::first()?->last_webhook_at?->timezone('Asia/Jakarta')->diffForHumans() ?? '—'
                            ),

                        Forms\Components\Placeholder::make('token_status')
                            ->label('Status Token')
                            ->content(fn () => new HtmlString(
                                WhatsappWebhookConfig::first()?->is_token_expired
                                    ? '<span class="text-red-400 font-bold">❌ Token Expired</span>'
                                    : '<span class="text-green-400 font-bold">✅ Token Aktif</span>'
                            )),
                    ]),

                // ── Notes ────────────────────────────────────────────────────────
                Forms\Components\Section::make('📝 Catatan')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan Internal')
                            ->placeholder('Catatan untuk tim teknis...')
                            ->rows(3),
                    ])
                    ->collapsible(),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Konfigurasi')
                ->icon('heroicon-m-check')
                ->color('success')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $config = WhatsappWebhookConfig::first();

        if ($config) {
            $config->update($data);
        } else {
            WhatsappWebhookConfig::create($data);
        }

        Notification::make()
            ->title('Konfigurasi berhasil disimpan!')
            ->body('Pengaturan WhatsApp API telah diperbarui.')
            ->success()
            ->send();
    }

    // ─── Live Stats ────────────────────────────────────────────────────────────

    public function getStats(): array
    {
        return [
            'total_today'   => WhatsappLog::whereDate('created_at', today())->count(),
            'success_today' => WhatsappLog::whereDate('created_at', today())->whereIn('status', ['sent', 'delivered', 'read'])->count(),
            'failed_today'  => WhatsappLog::whereDate('created_at', today())->where('status', 'failed')->count(),
            'pending'       => WhatsappLog::where('status', 'pending')->count(),
            'total_blasts'  => WhatsappBlast::count(),
            'active_blasts' => WhatsappBlast::whereIn('status', ['running', 'scheduled'])->count(),
        ];
    }
}
