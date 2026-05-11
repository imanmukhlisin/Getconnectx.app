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
                            
                            Infolists\Components\TextEntry::make('profile_header_html')
                                ->hiddenLabel()
                                ->html()
                                ->getStateUsing(function ($record) {
                                    $linkedinCred = $record->credentials()->where('provider', 'linkedin')->first();
                                    
                                    // Parse name
                                    $name = $record->name;
                                    if ($linkedinCred && isset($linkedinCred->raw_data['firstName']) && isset($linkedinCred->raw_data['lastName'])) {
                                        $name = $linkedinCred->raw_data['firstName'] . ' ' . $linkedinCred->raw_data['lastName'];
                                    }
                                    
                                    // Parse headline
                                    $headline = $record->position ?? 'Belum ada headline profesional';
                                    if ($linkedinCred && isset($linkedinCred->raw_data['headline'])) {
                                        $headline = $linkedinCred->raw_data['headline'];
                                    }
                                    
                                    // Location
                                    $location = trim(($record->city ?? '') . ', ' . ($record->country ?? ''), ', ') ?: 'Lokasi Tidak Diketahui';
                                    if ($linkedinCred && isset($linkedinCred->raw_data['location']['linkedinText'])) {
                                        $location = $linkedinCred->raw_data['location']['linkedinText'];
                                    }
                                    
                                    // Followers
                                    $followers = '';
                                    if ($linkedinCred && isset($linkedinCred->raw_data['followerCount'])) {
                                        $followers = number_format($linkedinCred->raw_data['followerCount']) . ' Followers';
                                    }
                                    
                                    // Role category
                                    $role = strtoupper($record->role_category ?? 'BELUM ONBOARDING');
                                    
                                    return "
                                        <div class='flex flex-col justify-center h-full gap-1.5'>
                                            <h1 class='text-4xl font-extrabold text-primary-500 tracking-tight'>{$name}</h1>
                                            <p class='text-lg text-gray-300 border-l-[3px] border-primary-500 pl-3 leading-snug max-w-3xl'>{$headline}</p>
                                            <div class='flex flex-wrap items-center gap-4 mt-3'>
                                                <div class='flex items-center gap-1.5 px-3 py-1 bg-primary-500/10 text-primary-400 rounded-lg text-sm font-bold border border-primary-500/20 shadow-sm'>
                                                    <svg class='w-4 h-4' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'></path></svg>
                                                    {$role}
                                                </div>
                                                <div class='flex items-center gap-1.5 text-gray-400 text-sm font-medium'>
                                                    <svg class='w-4 h-4 text-gray-500' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z'></path><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M15 11a3 3 0 11-6 0 3 3 0 016 0z'></path></svg>
                                                    {$location}
                                                </div>
                                                ".($followers ? "
                                                <div class='flex items-center gap-1.5 text-warning-500 text-sm font-medium'>
                                                    <svg class='w-4 h-4' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'></path></svg>
                                                    {$followers}
                                                </div>" : "")."
                                            </div>
                                        </div>
                                    ";
                                }),
                        ])->from('md'),
                    ]),

                // SECTION 1: PROFIL & KONTAK
                Infolists\Components\Section::make('Profil & Kontak')
                    ->icon('heroicon-o-user')
                    ->collapsible()
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('email')
                                    ->label('Email')
                                    ->icon('heroicon-m-envelope')
                                    ->copyable()
                                    ->hidden(fn($state) => empty($state)),
                                Infolists\Components\TextEntry::make('whatsapp_number')
                                    ->label('WhatsApp')
                                    ->icon('heroicon-m-device-phone-mobile')
                                    ->copyable()
                                    ->hidden(fn($state) => empty($state)),
                                Infolists\Components\TextEntry::make('linkedin_url')
                                    ->label('LinkedIn Profil')
                                    ->icon('heroicon-m-globe-alt')
                                    ->badge()
                                    ->color('info')
                                    ->formatStateUsing(fn ($state) => $state ? 'Kunjungi Profil LinkedIn' : '-')
                                    ->url(fn ($state) => $state)
                                    ->openUrlInNewTab()
                                    ->copyable()
                                    ->hidden(fn($state) => empty($state)),
                            ]),

                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('gender')
                                    ->label('Jenis Kelamin')
                                    ->icon('heroicon-m-users')
                                    ->formatStateUsing(fn ($state) => ucfirst($state ?? '-'))
                                    ->hidden(fn($state) => empty($state)),
                                Infolists\Components\TextEntry::make('date_of_birth')
                                    ->label('Tanggal Lahir')
                                    ->icon('heroicon-m-calendar')
                                    ->date('d F Y')
                                    ->hidden(fn($state) => empty($state)),
                                Infolists\Components\TextEntry::make('location')
                                    ->label('Domisili / Lokasi')
                                    ->icon('heroicon-m-map-pin')
                                    ->getStateUsing(fn ($record) => trim(($record->city ?? '') . ', ' . ($record->country ?? ''), ', '))
                                    ->hidden(fn($state) => empty($state)),
                            ]),

                        Infolists\Components\TextEntry::make('bio')
                            ->label('Bio Singkat')
                            ->prose()
                            ->hidden(fn($state) => empty($state)),
                    ]),

                // SECTION 2: KARIR & SKILL
                Infolists\Components\Section::make('Karir & Skill (Onboarding)')
                    ->icon('heroicon-o-briefcase')
                    ->collapsible()
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('primary_role')
                                    ->label('Peran Utama (Skill)')
                                    ->badge()
                                    ->color('primary')
                                    ->hidden(fn($state) => empty($state)),
                                Infolists\Components\TextEntry::make('position')
                                    ->label('Jabatan Terakhir / Saat Ini')
                                    ->hidden(fn($state) => empty($state)),
                                Infolists\Components\TextEntry::make('years_experience')
                                    ->label('Pengalaman Kerja')
                                    ->badge()
                                    ->color('warning')
                                    ->hidden(fn($state) => empty($state)),
                                Infolists\Components\TextEntry::make('startup_experience')
                                    ->label('Pengalaman Startup')
                                    ->badge()
                                    ->color('success')
                                    ->hidden(fn($state) => empty($state)),
                                Infolists\Components\TextEntry::make('leadership_style')
                                    ->label('Gaya Kepemimpinan')
                                    ->hidden(fn($state) => empty($state)),
                                Infolists\Components\TextEntry::make('commitment_level')
                                    ->label('Tingkat Komitmen')
                                    ->badge()
                                    ->hidden(fn($state) => empty($state)),
                            ]),
                            
                        Infolists\Components\TextEntry::make('tags.name')
                            ->label('Master Tags (Skill & Industri)')
                            ->badge()
                            ->color('info')
                            ->icon('heroicon-m-tag')
                            ->hidden(fn($record) => $record->tags->isEmpty()),

                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('work_arrangement')
                                    ->label('Pengaturan Kerja')
                                    ->badge()
                                    ->hidden(fn($state) => empty($state)),
                                Infolists\Components\IconEntry::make('open_to_remote')
                                    ->label('Terbuka untuk Remote')
                                    ->boolean()
                                    ->hidden(fn($state) => $state === null),
                                Infolists\Components\IconEntry::make('willing_to_relocate')
                                    ->label('Bersedia Relokasi')
                                    ->boolean()
                                    ->hidden(fn($state) => $state === null),
                            ]),
                    ]),

                // SECTION 3: STARTUP & TARGET
                Infolists\Components\Section::make('Startup & Target')
                    ->icon('heroicon-o-rocket-launch')
                    ->collapsible()
                    ->schema([
                        Infolists\Components\TextEntry::make('cofounder_type')
                            ->label('Tipe Co-Founder yang dicari')
                            ->badge()
                            ->color('primary')
                            ->hidden(fn($state) => empty($state)),

                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('startup_name')
                                    ->label('Nama Startup')
                                    ->weight('bold')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->hidden(fn($state) => empty($state)),
                                Infolists\Components\TextEntry::make('startup_stage')
                                    ->label('Tahap Startup (Stage)')
                                    ->badge()
                                    ->color('success')
                                    ->hidden(fn($state) => empty($state)),
                                Infolists\Components\TextEntry::make('startup_tagline')
                                    ->label('Tagline')
                                    ->columnSpanFull()
                                    ->color('gray')
                                    ->hidden(fn($state) => empty($state)),
                                Infolists\Components\TextEntry::make('startup_idea')
                                    ->label('Ide / Pitch Startup')
                                    ->columnSpanFull()
                                    ->prose()
                                    ->hidden(fn($state) => empty($state)),
                            ]),
                    ])
                    ->hidden(fn($record) => empty($record->cofounder_type) && empty($record->startup_name)),

                // SECTION 4: LINKEDIN EXPERIENCE & EDUCATION
                Infolists\Components\Section::make('Data Profesional LinkedIn Lengkap')
                    ->icon('heroicon-o-academic-cap')
                    ->collapsible()
                    ->hidden(function ($record) {
                        $linkedinCred = $record->credentials()->where('provider', 'linkedin')->first();
                        return !$linkedinCred || empty($linkedinCred->raw_data);
                    })
                    ->schema([
                        Infolists\Components\TextEntry::make('linkedin_summary_long')
                            ->label('Tentang')
                            ->prose()
                            ->hidden(function ($record) {
                                $linkedinCred = $record->credentials()->where('provider', 'linkedin')->first();
                                return empty($linkedinCred->raw_data['about']);
                            })
                            ->getStateUsing(function ($record) {
                                return $record->credentials()->where('provider', 'linkedin')->first()->raw_data['about'];
                            }),

                        Infolists\Components\Grid::make(1)
                            ->schema([
                                Infolists\Components\TextEntry::make('linkedin_experience_grouped')
                                    ->label('Pengalaman Kerja')
                                    ->html()
                                    ->getStateUsing(function ($record) {
                                        $linkedinCred = $record->credentials()->where('provider', 'linkedin')->first();
                                        $exp = $linkedinCred->raw_data['experience'] ?? [];
                                        if (empty($exp)) return '';
                                        
                                        // Group by companyName
                                        $grouped = [];
                                        foreach ($exp as $e) {
                                            $cName = $e['companyName'] ?? 'Unknown Company';
                                            $grouped[$cName][] = $e;
                                        }

                                        $html = '<div class="space-y-6 mt-2">';
                                        foreach ($grouped as $companyName => $roles) {
                                            $firstRole = $roles[0];
                                            $logoUrl = $firstRole['companyLogo']['url'] ?? null;
                                            if (!$logoUrl && isset($firstRole['companyLogo']['sizes'][0]['url'])) {
                                                $logoUrl = $firstRole['companyLogo']['sizes'][0]['url'];
                                            }
                                            $logoTag = $logoUrl ? "<img src='{$logoUrl}' class='w-12 h-12 rounded object-contain bg-white shrink-0 shadow-sm border border-gray-700' alt='logo'>" : "<div class='w-12 h-12 rounded bg-gray-800 border border-gray-700 flex items-center justify-center text-xl shrink-0 shadow-sm'>🏢</div>";
                                            
                                            $html .= "<div class='flex gap-4'>";
                                            // Left col: Logo and vertical line if multiple roles
                                            $html .= "<div class='flex flex-col items-center'>";
                                            $html .= $logoTag;
                                            if (count($roles) > 1) {
                                                $html .= "<div class='w-0.5 bg-gray-700 h-full mt-2 rounded-full'></div>";
                                            }
                                            $html .= "</div>";
                                            
                                            // Right col: Company Name and Roles
                                            $html .= "<div class='flex-1 pb-2'>";
                                            if (count($roles) > 1) {
                                                // It's a group, show company name at top
                                                $html .= "<h3 class='text-lg font-bold text-white leading-tight mb-3'>{$companyName}</h3>";
                                                $html .= "<div class='space-y-5'>";
                                                foreach ($roles as $idx => $role) {
                                                    $title = $role['position'] ?? 'Posisi Tidak Diketahui';
                                                    $duration = $role['duration'] ?? '';
                                                    $dates = ($role['startDate']['text'] ?? '') . ' - ' . ($role['endDate']['text'] ?? 'Present');
                                                    $html .= "
                                                        <div class='relative pl-4'>
                                                            <span class='absolute -left-[29px] top-1.5 w-2 h-2 rounded-full bg-primary-500 ring-4 ring-gray-900'></span>
                                                            <h4 class='font-bold text-primary-400 text-base leading-tight mb-1'>{$title}</h4>
                                                            <p class='text-sm text-gray-400 font-medium'>{$dates} · {$duration}</p>
                                                        </div>
                                                    ";
                                                }
                                                $html .= "</div>";
                                            } else {
                                                // Single role for company
                                                $role = $roles[0];
                                                $title = $role['position'] ?? 'Posisi Tidak Diketahui';
                                                $duration = $role['duration'] ?? '';
                                                $dates = ($role['startDate']['text'] ?? '') . ' - ' . ($role['endDate']['text'] ?? 'Present');
                                                $html .= "
                                                    <h3 class='text-lg font-bold text-white leading-tight mb-1'>{$title}</h3>
                                                    <h4 class='text-primary-400 font-semibold mb-1'>{$companyName}</h4>
                                                    <p class='text-sm text-gray-400 font-medium'>{$dates} · {$duration}</p>
                                                ";
                                            }
                                            $html .= "</div>";
                                            $html .= "</div>";
                                        }
                                        $html .= '</div>';
                                        return $html;
                                    })
                                    ->hidden(function ($record) {
                                        $linkedinCred = $record->credentials()->where('provider', 'linkedin')->first();
                                        return empty($linkedinCred->raw_data['experience']);
                                    }),

                                Infolists\Components\TextEntry::make('linkedin_education_rich')
                                    ->label('Pendidikan')
                                    ->html()
                                    ->getStateUsing(function ($record) {
                                        $linkedinCred = $record->credentials()->where('provider', 'linkedin')->first();
                                        $edu = $linkedinCred->raw_data['education'] ?? [];
                                        if (empty($edu)) return '';
                                        
                                        $html = '<div class="space-y-5 mt-2">';
                                        foreach ($edu as $e) {
                                            $school = $e['schoolName'] ?? 'Sekolah Tidak Diketahui';
                                            $degree = $e['degree'] ?? '';
                                            $field = $e['fieldOfStudy'] ?? '';
                                            $period = $e['period'] ?? '';
                                            
                                            $logoUrl = $e['schoolLogo']['url'] ?? null;
                                            if (!$logoUrl && isset($e['schoolLogo']['sizes'][0]['url'])) {
                                                $logoUrl = $e['schoolLogo']['sizes'][0]['url'];
                                            }
                                            $logoTag = $logoUrl ? "<img src='{$logoUrl}' class='w-12 h-12 rounded object-contain bg-white shrink-0 shadow-sm border border-gray-700' alt='logo'>" : "<div class='w-12 h-12 rounded bg-gray-800 border border-gray-700 flex items-center justify-center text-xl shrink-0 shadow-sm'>🎓</div>";
                                            
                                            $html .= "<div class='flex gap-4 items-start'>";
                                            $html .= $logoTag;
                                            $html .= "<div>
                                                        <h3 class='text-lg font-bold text-white leading-tight mb-1'>{$school}</h3>
                                                        <p class='text-primary-400 font-semibold text-sm mb-1'>{$degree}" . ($field ? " - {$field}" : "") . "</p>
                                                        <p class='text-sm text-gray-400 font-medium'>{$period}</p>
                                                      </div>";
                                            $html .= "</div>";
                                        }
                                        $html .= '</div>';
                                        return $html;
                                    })
                                    ->hidden(function ($record) {
                                        $linkedinCred = $record->credentials()->where('provider', 'linkedin')->first();
                                        return empty($linkedinCred->raw_data['education']);
                                    }),

                                Infolists\Components\TextEntry::make('linkedin_certifications_rich')
                                    ->label('Lisensi & Sertifikasi')
                                    ->html()
                                    ->getStateUsing(function ($record) {
                                        $linkedinCred = $record->credentials()->where('provider', 'linkedin')->first();
                                        $certs = $linkedinCred->raw_data['certifications'] ?? [];
                                        if (empty($certs)) return '';
                                        
                                        $html = '<div class="space-y-5 mt-2">';
                                        foreach ($certs as $c) {
                                            $title = $c['title'] ?? 'Sertifikasi';
                                            $issuer = $c['issuedBy'] ?? '';
                                            $issuedAt = $c['issuedAt'] ?? '';
                                            
                                            $logoUrl = $c['issuedByLogo']['url'] ?? null;
                                            if (!$logoUrl && isset($c['issuedByLogo']['sizes'][0]['url'])) {
                                                $logoUrl = $c['issuedByLogo']['sizes'][0]['url'];
                                            }
                                            $logoTag = $logoUrl ? "<img src='{$logoUrl}' class='w-12 h-12 rounded object-contain bg-white shrink-0 shadow-sm border border-gray-700' alt='logo'>" : "<div class='w-12 h-12 rounded bg-gray-800 border border-gray-700 flex items-center justify-center text-xl shrink-0 shadow-sm'>🏆</div>";
                                            
                                            $html .= "<div class='flex gap-4 items-start'>";
                                            $html .= $logoTag;
                                            $html .= "<div>
                                                        <h3 class='text-base font-bold text-white leading-tight mb-1'>{$title}</h3>
                                                        <p class='text-primary-400 font-semibold text-sm mb-1'>{$issuer}</p>
                                                        <p class='text-sm text-gray-400 font-medium'>{$issuedAt}</p>
                                                      </div>";
                                            $html .= "</div>";
                                        }
                                        $html .= '</div>';
                                        return $html;
                                    })
                                    ->hidden(function ($record) {
                                        $linkedinCred = $record->credentials()->where('provider', 'linkedin')->first();
                                        return empty($linkedinCred->raw_data['certifications']);
                                    }),

                                Infolists\Components\TextEntry::make('linkedin_skills_rich')
                                    ->label('Keahlian (Skills)')
                                    ->html()
                                    ->getStateUsing(function ($record) {
                                        $linkedinCred = $record->credentials()->where('provider', 'linkedin')->first();
                                        $skills = $linkedinCred->raw_data['skills'] ?? [];
                                        if (empty($skills)) return '';
                                        
                                        $html = '<div class="flex flex-wrap gap-2 mt-2">';
                                        foreach ($skills as $s) {
                                            $name = $s['name'] ?? '';
                                            if ($name) {
                                                $html .= "<span class='px-3 py-1.5 bg-gray-800 border border-gray-700 text-primary-400 rounded-full text-xs font-semibold shadow-sm'>{$name}</span>";
                                            }
                                        }
                                        $html .= '</div>';
                                        return $html;
                                    })
                                    ->hidden(function ($record) {
                                        $linkedinCred = $record->credentials()->where('provider', 'linkedin')->first();
                                        return empty($linkedinCred->raw_data['skills']);
                                    }),
                            ]),
                    ]),

                // SECTION 5: SISTEM & KEAMANAN
                Infolists\Components\Section::make('Sistem & Keamanan')
                    ->icon('heroicon-o-shield-check')
                    ->collapsible()
                    ->schema([
                        Infolists\Components\Grid::make(3)
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

                        Infolists\Components\Grid::make(2)
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
                                    ->hidden(fn($state) => empty($state)),
                                Infolists\Components\TextEntry::make('blocked_at')
                                    ->label('Waktu Diblokir')
                                    ->dateTime()
                                    ->hidden(fn($state) => empty($state)),
                            ]),

                        Infolists\Components\Grid::make(2)
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
                                    ->hidden(fn($state) => empty($state)),
                                Infolists\Components\TextEntry::make('whatsapp_verified_at')
                                    ->label('Waktu Verifikasi WA')
                                    ->dateTime()
                                    ->hidden(fn($state) => empty($state)),
                            ]),
                    ]),
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
