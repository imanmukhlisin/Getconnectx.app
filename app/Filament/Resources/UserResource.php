<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'User Management';
    protected static ?string $modelLabel = 'Pengguna';
    protected static ?string $pluralModelLabel = 'Data Pengguna';

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make()
                    ->schema([
                        Infolists\Components\Split::make([
                            Infolists\Components\ImageEntry::make('avatar_url')
                                ->hiddenLabel()
                                ->circular()
                                ->defaultImageUrl(fn($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name).'&color=FFFFFF&background=09090b')
                                ->size(160)
                                ->extraImgAttributes(['class' => 'shadow-2xl ring-4 ring-primary-500/30 object-cover hover:scale-105 transition-transform duration-300']),
                            
                            Infolists\Components\Group::make()
                                ->schema([
                                    Infolists\Components\TextEntry::make('name')
                                        ->hiddenLabel()
                                        ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                        ->weight('bold')
                                        ->color('primary')
                                        ->formatStateUsing(function ($state, $record) {
                                            $linkedinCred = $record->credentials()->where('provider', 'linkedin')->first();
                                            if ($linkedinCred && isset($linkedinCred->raw_data['firstName']) && isset($linkedinCred->raw_data['lastName'])) {
                                                return $linkedinCred->raw_data['firstName'] . ' ' . $linkedinCred->raw_data['lastName'];
                                            }
                                            return $state;
                                        })
                                        ->extraAttributes(['class' => 'text-4xl tracking-tight']),

                                    Infolists\Components\TextEntry::make('headline')
                                        ->hiddenLabel()
                                        ->getStateUsing(function ($record) {
                                            $linkedinCred = $record->credentials()->where('provider', 'linkedin')->first();
                                            if ($linkedinCred && isset($linkedinCred->raw_data['headline'])) {
                                                return $linkedinCred->raw_data['headline'];
                                            }
                                            return $record->position ?? 'Belum ada headline profesional';
                                        })
                                        ->color('gray')
                                        ->extraAttributes(['class' => 'text-lg italic mt-2 border-l-2 border-primary-500 pl-3']),

                                    Infolists\Components\Grid::make(4)
                                        ->schema([
                                            Infolists\Components\TextEntry::make('username')
                                                ->hiddenLabel()
                                                ->icon('heroicon-m-at-symbol')
                                                ->badge()
                                                ->color('gray')
                                                ->formatStateUsing(fn ($state) => $state ?? 'Belum ada username'),
                                                
                                            Infolists\Components\TextEntry::make('role_category')
                                                ->hiddenLabel()
                                                ->badge()
                                                ->icon('heroicon-m-briefcase')
                                                ->color('info')
                                                ->formatStateUsing(fn ($state) => strtoupper($state ?? 'Belum Onboarding')),
                                                
                                            Infolists\Components\TextEntry::make('location_details')
                                                ->hiddenLabel()
                                                ->icon('heroicon-m-map-pin')
                                                ->color('gray')
                                                ->getStateUsing(function ($record) {
                                                    $linkedinCred = $record->credentials()->where('provider', 'linkedin')->first();
                                                    if ($linkedinCred && isset($linkedinCred->raw_data['location']['linkedinText'])) {
                                                        return $linkedinCred->raw_data['location']['linkedinText'];
                                                    }
                                                    return trim(($record->city ?? '') . ', ' . ($record->country ?? ''), ', ') ?: 'Lokasi Tidak Diketahui';
                                                }),
                                                
                                            Infolists\Components\TextEntry::make('followers')
                                                ->hiddenLabel()
                                                ->icon('heroicon-m-users')
                                                ->color('warning')
                                                ->getStateUsing(function ($record) {
                                                    $linkedinCred = $record->credentials()->where('provider', 'linkedin')->first();
                                                    if ($linkedinCred && isset($linkedinCred->raw_data['followerCount'])) {
                                                        return number_format($linkedinCred->raw_data['followerCount']) . ' Followers';
                                                    }
                                                    return '-';
                                                }),
                                        ])->extraAttributes(['class' => 'mt-4']),
                                ])->grow(true),
                        ])->from('md'),
                    ]),

                Infolists\Components\Tabs::make('Tabs')
                    ->tabs([
                        // ── TAB 1: PROFIL & KONTAK ────────────────────────────────────────────────────────────
                        Infolists\Components\Tabs\Tab::make('Profil & Kontak')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Infolists\Components\Section::make('Kontak Pribadi')
                                    ->columns(3)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('email')
                                            ->label('Email')
                                            ->icon('heroicon-m-envelope')
                                            ->copyable(),
                                        Infolists\Components\TextEntry::make('whatsapp_number')
                                            ->label('WhatsApp')
                                            ->icon('heroicon-m-device-phone-mobile')
                                            ->copyable(),
                                        Infolists\Components\TextEntry::make('linkedin_url')
                                            ->label('LinkedIn URL')
                                            ->icon('heroicon-m-globe-alt')
                                            ->badge()
                                            ->color('info')
                                            ->formatStateUsing(fn ($state) => $state ? 'Kunjungi Profil LinkedIn' : '-')
                                            ->url(fn ($state) => $state)
                                            ->openUrlInNewTab()
                                            ->copyable(),
                                    ]),

                                Infolists\Components\Section::make('Demografi & Lokasi')
                                    ->columns(3)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('gender')
                                            ->label('Jenis Kelamin')
                                            ->icon('heroicon-m-users')
                                            ->formatStateUsing(fn ($state) => ucfirst($state ?? '-')),
                                        Infolists\Components\TextEntry::make('date_of_birth')
                                            ->label('Tanggal Lahir')
                                            ->icon('heroicon-m-calendar')
                                            ->date('d F Y'),
                                        Infolists\Components\TextEntry::make('location')
                                            ->label('Domisili / Lokasi')
                                            ->icon('heroicon-m-map-pin')
                                            ->getStateUsing(fn ($record) => trim(($record->city ?? '') . ', ' . ($record->country ?? ''), ', ')),
                                        Infolists\Components\TextEntry::make('latitude')
                                            ->label('Latitude')
                                            ->color('gray')
                                            ->size(Infolists\Components\TextEntry\TextEntrySize::Small),
                                        Infolists\Components\TextEntry::make('longitude')
                                            ->label('Longitude')
                                            ->color('gray')
                                            ->size(Infolists\Components\TextEntry\TextEntrySize::Small),
                                    ]),

                                Infolists\Components\Section::make('Bio Singkat')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('bio')
                                            ->hiddenLabel()
                                            ->prose()
                                            ->placeholder('Belum ada bio.'),
                                    ]),
                            ]),

                        // ── TAB 2: KARIR, SKILL & TAGS ─────────────────────────────────────────────────────────
                        Infolists\Components\Tabs\Tab::make('Karir & Skill')
                            ->icon('heroicon-o-briefcase')
                            ->schema([
                                Infolists\Components\Section::make('Posisi & Pengalaman')
                                    ->columns(3)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('primary_role')
                                            ->label('Peran Utama (Skill)')
                                            ->badge()
                                            ->color('primary'),
                                        Infolists\Components\TextEntry::make('position')
                                            ->label('Jabatan Terakhir / Saat Ini'),
                                        Infolists\Components\TextEntry::make('years_experience')
                                            ->label('Pengalaman Kerja')
                                            ->badge()
                                            ->color('warning'),
                                        Infolists\Components\TextEntry::make('startup_experience')
                                            ->label('Pengalaman Startup')
                                            ->badge()
                                            ->color('success'),
                                        Infolists\Components\TextEntry::make('leadership_style')
                                            ->label('Gaya Kepemimpinan'),
                                        Infolists\Components\TextEntry::make('commitment_level')
                                            ->label('Tingkat Komitmen')
                                            ->badge(),
                                    ]),

                                Infolists\Components\Section::make('Master Tags (Skill & Industri)')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('tags.name')
                                            ->hiddenLabel()
                                            ->badge()
                                            ->color('info')
                                            ->icon('heroicon-m-tag'),
                                    ]),

                                Infolists\Components\Section::make('Preferensi Kerja')
                                    ->columns(3)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('work_arrangement')
                                            ->label('Pengaturan Kerja')
                                            ->badge(),
                                        Infolists\Components\IconEntry::make('open_to_remote')
                                            ->label('Terbuka untuk Remote')
                                            ->boolean(),
                                        Infolists\Components\IconEntry::make('willing_to_relocate')
                                            ->label('Bersedia Relokasi')
                                            ->boolean(),
                                        Infolists\Components\IconEntry::make('remote_ready')
                                            ->label('Remote Ready (Peralatan lengkap)')
                                            ->boolean(),
                                    ]),
                            ]),

                        // ── TAB 3: STARTUP & CO-FOUNDER ───────────────────────────────────────────────────────
                        Infolists\Components\Tabs\Tab::make('Startup & Target')
                            ->icon('heroicon-o-rocket-launch')
                            ->schema([
                                Infolists\Components\Section::make('Target Pencarian')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('cofounder_type')
                                            ->label('Tipe Co-Founder yang dicari')
                                            ->badge()
                                            ->color('primary'),
                                    ]),

                                Infolists\Components\Section::make('Detail Startup (Jika ada)')
                                    ->columns(2)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('startup_name')
                                            ->label('Nama Startup')
                                            ->weight('bold')
                                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                                        Infolists\Components\TextEntry::make('startup_stage')
                                            ->label('Tahap Startup (Stage)')
                                            ->badge()
                                            ->color('success'),
                                        Infolists\Components\TextEntry::make('startup_tagline')
                                            ->label('Tagline')
                                            ->columnSpanFull()
                                            ->color('gray'),
                                        Infolists\Components\TextEntry::make('startup_idea')
                                            ->label('Ide / Pitch Startup')
                                            ->columnSpanFull()
                                            ->prose(),
                                    ]),
                            ]),

                        // ── TAB 4: EDUKASI & BAHASA ───────────────────────────────────────────────────────────
                        Infolists\Components\Tabs\Tab::make('Edukasi & Bahasa')
                            ->icon('heroicon-o-academic-cap')
                            ->schema([
                                Infolists\Components\Section::make('Riwayat Pendidikan (Data JSON/Array)')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('education')
                                            ->hiddenLabel()
                                            ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : 'Belum ada data')
                                            ->extraAttributes(['class' => 'font-mono text-xs bg-gray-900 text-gray-300 p-4 rounded-lg overflow-x-auto'])
                                            ->html(),
                                    ]),
                                Infolists\Components\Section::make('Bahasa yang dikuasai')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('languages')
                                            ->hiddenLabel()
                                            ->badge()
                                            ->color('success'),
                                    ]),
                            ]),

                        // ── TAB 5: DATA LINKEDIN LENGKAP ──────────────────────────────────────────────────────
                        Infolists\Components\Tabs\Tab::make('Data LinkedIn')
                            ->icon('heroicon-o-link')
                            ->schema([
                                Infolists\Components\Section::make('Informasi Profesional LinkedIn')
                                    ->description('Data terverifikasi yang ditarik secara otomatis dari profil LinkedIn pengguna. Ditampilkan dengan tampilan premium.')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('linkedin_summary')
                                            ->hiddenLabel()
                                            ->prose()
                                            ->getStateUsing(function ($record) {
                                                $linkedinCred = $record->credentials()->where('provider', 'linkedin')->first();
                                                return $linkedinCred->raw_data['about'] ?? 'Belum ada ringkasan profesional.';
                                            }),
                                        
                                        Infolists\Components\Grid::make(2)
                                            ->schema([
                                                Infolists\Components\Section::make('Pengalaman Kerja')
                                                    ->schema([
                                                        Infolists\Components\TextEntry::make('linkedin_experience')
                                                            ->hiddenLabel()
                                                            ->html()
                                                            ->getStateUsing(function ($record) {
                                                                $linkedinCred = $record->credentials()->where('provider', 'linkedin')->first();
                                                                $exp = $linkedinCred->raw_data['experience'] ?? [];
                                                                if (empty($exp)) return '<span class="text-gray-500 italic">Belum ada pengalaman kerja.</span>';
                                                                
                                                                $html = '<div class="space-y-5">';
                                                                foreach ($exp as $e) {
                                                                    $title = $e['position'] ?? 'Posisi Tidak Diketahui';
                                                                    $company = $e['companyName'] ?? '';
                                                                    $duration = $e['duration'] ?? '';
                                                                    $html .= "<div class='border-l-4 border-primary-500 pl-4 py-1 hover:bg-gray-800/50 rounded-r-lg transition-colors duration-200'>
                                                                                <h4 class='font-bold text-lg text-white'>{$title}</h4>
                                                                                <p class='text-primary-400 font-medium'>{$company}</p>
                                                                                <p class='text-sm text-gray-400'>{$duration}</p>
                                                                              </div>";
                                                                }
                                                                $html .= '</div>';
                                                                return $html;
                                                            })
                                                    ]),
                                                
                                                Infolists\Components\Section::make('Pendidikan & Sertifikasi')
                                                    ->schema([
                                                        Infolists\Components\TextEntry::make('linkedin_education')
                                                            ->hiddenLabel()
                                                            ->html()
                                                            ->getStateUsing(function ($record) {
                                                                $linkedinCred = $record->credentials()->where('provider', 'linkedin')->first();
                                                                if (!$linkedinCred || empty($linkedinCred->raw_data)) return '<span class="text-gray-500 italic">Belum ada data.</span>';
                                                                
                                                                $edu = $linkedinCred->raw_data['education'] ?? [];
                                                                $certs = $linkedinCred->raw_data['certifications'] ?? [];
                                                                
                                                                $html = '<div class="space-y-4">';
                                                                if (!empty($edu)) {
                                                                    $html .= '<h3 class="font-bold text-white mb-2 border-b border-gray-700 pb-1">Pendidikan</h3>';
                                                                    foreach ($edu as $e) {
                                                                        $school = $e['schoolName'] ?? 'Sekolah Tidak Diketahui';
                                                                        $degree = $e['degree'] ?? '';
                                                                        $field = $e['fieldOfStudy'] ?? '';
                                                                        $period = $e['period'] ?? '';
                                                                        $html .= "<div class='mb-3 hover:bg-gray-800/50 p-2 rounded-lg transition-colors duration-200'>
                                                                                    <h4 class='font-semibold text-primary-300'>{$school}</h4>
                                                                                    <p class='text-sm text-gray-300'>{$degree} - {$field}</p>
                                                                                    <p class='text-xs text-gray-500'>{$period}</p>
                                                                                  </div>";
                                                                    }
                                                                }
                                                                
                                                                if (!empty($certs)) {
                                                                    $html .= '<h3 class="font-bold text-white mb-2 mt-4 border-b border-gray-700 pb-1">Sertifikasi</h3>';
                                                                    foreach ($certs as $c) {
                                                                        $title = $c['title'] ?? 'Sertifikasi';
                                                                        $issuer = $c['issuedBy'] ?? '';
                                                                        $html .= "<div class='mb-2 flex items-start gap-2 hover:bg-gray-800/50 p-2 rounded-lg transition-colors duration-200'>
                                                                                    <span class='text-yellow-500'>🏆</span>
                                                                                    <div>
                                                                                        <p class='text-sm font-medium text-gray-200'>{$title}</p>
                                                                                        <p class='text-xs text-gray-400'>{$issuer}</p>
                                                                                    </div>
                                                                                  </div>";
                                                                    }
                                                                }
                                                                
                                                                if (empty($edu) && empty($certs)) {
                                                                    return '<span class="text-gray-500 italic">Belum ada riwayat pendidikan atau sertifikasi.</span>';
                                                                }
                                                                
                                                                $html .= '</div>';
                                                                return $html;
                                                            })
                                                    ]),
                                            ]),
                                            
                                        Infolists\Components\Section::make('Keahlian (Skills)')
                                            ->schema([
                                                Infolists\Components\TextEntry::make('linkedin_skills')
                                                    ->hiddenLabel()
                                                    ->html()
                                                    ->getStateUsing(function ($record) {
                                                        $linkedinCred = $record->credentials()->where('provider', 'linkedin')->first();
                                                        $skills = $linkedinCred->raw_data['skills'] ?? [];
                                                        if (empty($skills)) return '<span class="text-gray-500 italic">Belum ada keahlian.</span>';
                                                        
                                                        $html = '<div class="flex flex-wrap gap-2">';
                                                        foreach ($skills as $s) {
                                                            $name = $s['name'] ?? '';
                                                            if ($name) {
                                                                $html .= "<span class='px-3 py-1.5 bg-gray-800 border border-gray-700 text-primary-400 rounded-full text-xs font-semibold hover:bg-primary-900/30 hover:border-primary-500 transition-colors duration-200'>{$name}</span>";
                                                            }
                                                        }
                                                        $html .= '</div>';
                                                        return $html;
                                                    })
                                            ]),

                                        Infolists\Components\Section::make('Data Mentah (JSON Payload)')
                                            ->collapsed()
                                            ->description('Tampilan payload JSON dari LinkedIn scraper API untuk proses debugging.')
                                            ->schema([
                                                Infolists\Components\TextEntry::make('linkedin_data')
                                                    ->hiddenLabel()
                                                    ->formatStateUsing(function ($record) {
                                                        $linkedinCred = $record->credentials()->where('provider', 'linkedin')->first();
                                                        return $linkedinCred ? '<pre>'.json_encode($linkedinCred->raw_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).'</pre>' : 'Belum ada data LinkedIn';
                                                    })
                                                    ->extraAttributes(['class' => 'font-mono text-xs bg-gray-900 text-gray-300 p-4 rounded-lg overflow-x-auto max-h-[400px] overflow-y-auto'])
                                                    ->html(),
                                            ]),
                                    ]),
                            ]),

                        // ── TAB 6: STATUS & SISTEM ────────────────────────────────────────────────────────────
                        Infolists\Components\Tabs\Tab::make('Sistem & Keamanan')
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Infolists\Components\Section::make('Status Akun')
                                    ->columns(3)
                                    ->schema([
                                        Infolists\Components\IconEntry::make('is_active')
                                            ->label('Akun Aktif')
                                            ->boolean(),
                                        Infolists\Components\IconEntry::make('is_onboarded')
                                            ->label('Selesai Onboarding')
                                            ->boolean(),
                                        Infolists\Components\TextEntry::make('registration_step')
                                            ->label('Langkah Registrasi Terakhir')
                                            ->badge()
                                            ->color('warning'),
                                    ]),

                                Infolists\Components\Section::make('Blokir & Kemanan')
                                    ->columns(2)
                                    ->schema([
                                        Infolists\Components\IconEntry::make('is_blocked')
                                            ->label('Status Blokir')
                                            ->boolean()
                                            ->trueIcon('heroicon-o-no-symbol')
                                            ->falseIcon('heroicon-o-check-circle')
                                            ->trueColor('danger')
                                            ->falseColor('success'),
                                        Infolists\Components\TextEntry::make('blocked_reason')
                                            ->label('Alasan Diblokir')
                                            ->color('danger')
                                            ->placeholder('-'),
                                        Infolists\Components\TextEntry::make('blocked_at')
                                            ->label('Waktu Diblokir')
                                            ->dateTime()
                                            ->placeholder('-'),
                                    ]),

                                Infolists\Components\Section::make('Log Waktu & Device')
                                    ->columns(2)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('created_at')
                                            ->label('Mendaftar Pada')
                                            ->dateTime('d F Y, H:i:s'),
                                        Infolists\Components\TextEntry::make('updated_at')
                                            ->label('Pembaruan Terakhir')
                                            ->dateTime('d F Y, H:i:s'),
                                        Infolists\Components\TextEntry::make('email_verified_at')
                                            ->label('Waktu Verifikasi Email')
                                            ->dateTime()
                                            ->placeholder('Belum verifikasi'),
                                        Infolists\Components\TextEntry::make('whatsapp_verified_at')
                                            ->label('Waktu Verifikasi WA')
                                            ->dateTime()
                                            ->placeholder('Belum verifikasi'),
                                        Infolists\Components\TextEntry::make('last_device_id')
                                            ->label('Device ID Terakhir')
                                            ->color('gray')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ViewColumn::make('avatar_url')
                    ->label('Foto')
                    ->view('filament.tables.columns.avatar-with-pro'),
                    
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (User $record): string => $record->email ?? '-'),
                
                Tables\Columns\TextColumn::make('role_category')
                    ->label('Role')
                    ->searchable()
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => $state ?? 'Belum Pilih'),

                Tables\Columns\IconColumn::make('is_onboarded')
                    ->label('Onboarding')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Aktivitas')
                    ->badge()
                    ->getStateUsing(function (User $record) {
                        if ($record->is_blocked) return 'Diblokir';

                        $latestToken = $record->tokens()->orderBy('last_used_at', 'desc')->first();
                        $lastActive = $latestToken ? $latestToken->last_used_at : null;

                        if (!$lastActive) {
                            if (!$record->is_onboarded && $record->created_at && $record->created_at->diffInDays(now()) > 30) {
                                return 'Tidak Aktif';
                            }
                            return 'Belum Login';
                        }

                        $diffDays = $lastActive->startOfDay()->diffInDays(now()->startOfDay());

                        if ($diffDays == 0) {
                            return 'Aktif Sekarang';
                        } elseif ($diffDays == 1) {
                            return 'Kemarin';
                        } elseif ($diffDays < 7) {
                            return "{$diffDays} hari yang lalu";
                        } elseif ($diffDays < 30) {
                            $weeks = floor($diffDays / 7);
                            return "{$weeks} minggu yang lalu";
                        } else {
                            return 'Tidak Aktif (> 1 bln)';
                        }
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Aktif Sekarang' => 'success',
                        'Kemarin' => 'info',
                        'Diblokir', 'Tidak Aktif', 'Tidak Aktif (> 1 bln)', 'Belum Login' => 'danger',
                        default => str_contains($state, 'hari') ? 'info' : 'warning',
                    })
                    ->icon(fn (string $state): ?string => $state === 'Aktif Sekarang' ? 'heroicon-s-sparkles' : null)
                    ->extraAttributes(fn (string $state): array => $state === 'Aktif Sekarang' ? [
                        'class' => 'animate-pulse shadow-[0_0_15px_rgba(34,197,94,0.8)]',
                    ] : []),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tgl Daftar')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role_category')
                    ->label('Filter Role')
                    ->options([
                        'founder' => 'Founder',
                        'startup' => 'Startup',
                        'team_member' => 'Team Member',
                        'cofounder' => 'Co-Founder',
                    ]),
                Filter::make('is_onboarded')
                    ->label('Sudah Onboarding')
                    ->query(fn (Builder $query): Builder => $query->where('is_onboarded', true)),
                Filter::make('is_blocked')
                    ->label('Akun Diblokir')
                    ->query(fn (Builder $query): Builder => $query->where('is_blocked', true)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                
                // Tombol Blokir
                Action::make('block')
                    ->label('Blokir')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Blokir Pengguna')
                    ->modalDescription('Pengguna yang diblokir tidak akan bisa login atau mendaftar lagi menggunakan email ini.')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Alasan Pemblokiran')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (User $record, array $data): void {
                        $record->update([
                            'is_blocked' => true,
                            'blocked_reason' => $data['reason'],
                            'blocked_at' => now(),
                            'is_active' => false,
                        ]);
                        // Hapus token session
                        $record->tokens()->delete();

                        \Filament\Notifications\Notification::make()
                            ->title('Berhasil diblokir')
                            ->body("Pengguna {$record->name} berhasil diblokir.")
                            ->success()
                            ->send();
                    })
                    ->visible(fn (User $record): bool => ! $record->is_blocked),

                // Tombol Buka Blokir
                Action::make('unblock')
                    ->label('Buka Blokir')
                    ->icon('heroicon-o-shield-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Buka Blokir Pengguna')
                    ->action(function (User $record): void {
                        $record->update([
                            'is_blocked' => false,
                            'blocked_reason' => null,
                            'blocked_at' => null,
                            'is_active' => true,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Blokir dibuka')
                            ->body("Akses pengguna {$record->name} berhasil dipulihkan.")
                            ->success()
                            ->send();
                    })
                    ->visible(fn (User $record): bool => $record->is_blocked),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListUsers::route('/'),
            'view' => Pages\ViewUser::route('/{record}'),
        ];
    }
}
