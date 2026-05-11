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
                    ->extraAttributes(['style' => 'padding: 20px 24px;'])
                    ->schema([
                        Infolists\Components\TextEntry::make('profile_header_full')
                            ->hiddenLabel()
                            ->html()
                            ->columnSpanFull()
                            ->getStateUsing(function ($record) {
                                $linkedinCred = $record->credentials()->where('provider', 'linkedin')->first();

                                $name = $record->name;
                                if ($linkedinCred && isset($linkedinCred->raw_data['firstName'])) {
                                    $name = trim(($linkedinCred->raw_data['firstName'] ?? '') . ' ' . ($linkedinCred->raw_data['lastName'] ?? ''));
                                }
                                $headline = $record->position ?? '';
                                if ($linkedinCred && !empty($linkedinCred->raw_data['headline'])) {
                                    $headline = $linkedinCred->raw_data['headline'];
                                }
                                $location = trim(($record->city ?? '') . ', ' . ($record->country ?? ''), ', ');
                                if ($linkedinCred && !empty($linkedinCred->raw_data['location']['linkedinText'])) {
                                    $location = $linkedinCred->raw_data['location']['linkedinText'];
                                }
                                $followers = '';
                                if ($linkedinCred && !empty($linkedinCred->raw_data['followerCount'])) {
                                    $followers = number_format($linkedinCred->raw_data['followerCount']);
                                }
                                $role = strtoupper($record->role_category ?? 'BELUM ONBOARDING');
                                $avatarUrl = $record->avatar_url ?: 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=FFFFFF&background=09090b&size=160';

                                $n = htmlspecialchars($name);
                                $h = htmlspecialchars($headline);
                                $l = htmlspecialchars($location);
                                $r = htmlspecialchars($role);

                                $followersBadge = $followers ? "
                                    <span style='display:inline-flex;align-items:center;gap:4px;font-size:0.8rem;color:#f59e0b;font-weight:600;'>
                                        <svg style='width:12px;height:12px;flex-shrink:0;' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'></path></svg>
                                        {$followers} Followers
                                    </span>" : '';

                                $locationBadge = $location ? "
                                    <span style='display:inline-flex;align-items:center;gap:4px;font-size:0.8rem;color:#9ca3af;'>
                                        <svg style='width:12px;height:12px;flex-shrink:0;' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z'></path><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M15 11a3 3 0 11-6 0 3 3 0 016 0z'></path></svg>
                                        {$l}
                                    </span>" : '';

                                return "
                                <div style='display:flex;align-items:center;gap:16px;'>
                                    <img src='{$avatarUrl}' alt='avatar'
                                         style='width:120px;height:120px;border-radius:50%;object-fit:cover;flex-shrink:0;
                                                box-shadow:0 4px 24px rgba(0,0,0,0.18);
                                                border:3px solid rgba(249,115,22,0.35);'>
                                    <div style='flex:1;min-width:0;'>
                                        <div style='font-size:1.75rem;font-weight:800;line-height:1.15;color:#f97316;letter-spacing:-0.02em;margin-bottom:4px;'>{$n}</div>
                                        " . ($h ? "<div style='font-size:0.82rem;color:#6b7280;line-height:1.55;max-width:560px;margin-bottom:10px;'>{$h}</div>" : "") . "
                                        <div style='display:flex;flex-wrap:wrap;align-items:center;gap:8px;'>
                                            <span style='display:inline-flex;align-items:center;gap:5px;padding:3px 10px;background:rgba(249,115,22,0.1);color:#f97316;border:1px solid rgba(249,115,22,0.25);border-radius:6px;font-size:0.7rem;font-weight:700;letter-spacing:0.06em;'>
                                                <svg style='width:11px;height:11px;flex-shrink:0;' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'></path></svg>
                                                {$r}
                                            </span>
                                            {$locationBadge}
                                            {$followersBadge}
                                        </div>
                                    </div>
                                </div>";
                            }),
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

                                        $html = '<div class="space-y-8 mt-2">';
                                        foreach ($grouped as $companyName => $roles) {
                                            $firstRole = $roles[0];
                                            $logoUrl = $firstRole['companyLogo']['sizes'][2]['url'] ?? $firstRole['companyLogo']['sizes'][0]['url'] ?? $firstRole['companyLogo']['url'] ?? null;
                                            $logoTag = $logoUrl
                                                ? "<img src='{$logoUrl}' class='w-11 h-11 rounded-lg object-contain bg-white p-1 shrink-0 border border-gray-200' style='min-width:44px;max-width:44px;min-height:44px;max-height:44px;' alt=''>"
                                                : "<div class='shrink-0 w-11 h-11 rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center text-gray-400' style='min-width:44px;'><svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'></path></svg></div>";
                                            
                                            $isGroup = count($roles) > 1;
                                            $lineHeight = $isGroup ? 'calc(100% - 44px)' : '0';
                                            
                                            $html .= "<div class='flex gap-3 items-start'>";
                                            $html .= "<div class='flex flex-col items-center shrink-0'>";
                                            $html .= $logoTag;
                                            if ($isGroup) $html .= "<div class='w-px bg-gray-300 flex-1 mt-2 mb-1'></div>";
                                            $html .= "</div>";
                                            
                                            $html .= "<div class='flex-1 min-w-0 pb-2'>";
                                            if ($isGroup) {
                                                $html .= "<p class='text-xs font-semibold text-gray-500 uppercase tracking-wider mb-0.5'>" . htmlspecialchars($companyName) . "</p>";
                                                $html .= "<div class='space-y-4 mt-1'>";
                                                foreach ($roles as $role) {
                                                    $title = htmlspecialchars($role['position'] ?? 'Posisi Tidak Diketahui');
                                                    $duration = $role['duration'] ?? '';
                                                    $empType = $role['employmentType'] ?? '';
                                                    $dates = ($role['startDate']['text'] ?? '') . ' – ' . ($role['endDate']['text'] ?? 'Present');
                                                    $meta = array_filter([$empType, $dates, $duration]);
                                                    $html .= "<div><p class='font-semibold text-gray-900 dark:text-white text-sm leading-tight'>{$title}</p><p class='text-xs text-gray-500 mt-0.5'>" . implode(' · ', $meta) . "</p></div>";
                                                }
                                                $html .= "</div>";
                                            } else {
                                                $role = $roles[0];
                                                $title = htmlspecialchars($role['position'] ?? 'Posisi Tidak Diketahui');
                                                $empType = $role['employmentType'] ?? '';
                                                $workplace = $role['workplaceType'] ?? '';
                                                $dates = ($role['startDate']['text'] ?? '') . ' – ' . ($role['endDate']['text'] ?? 'Present');
                                                $duration = $role['duration'] ?? '';
                                                $loc = $role['location'] ?? '';
                                                $html .= "<p class='font-semibold text-gray-900 dark:text-white text-sm leading-tight'>{$title}</p>";
                                                $html .= "<p class='text-xs text-gray-600 dark:text-gray-400 mt-0.5'>" . htmlspecialchars($companyName);
                                                if ($empType) $html .= " · {$empType}";
                                                $html .= "</p>";
                                                $html .= "<p class='text-xs text-gray-500 mt-0.5'>{$dates} · {$duration}";
                                                if ($loc) $html .= " · {$loc}";
                                                if ($workplace) $html .= " · {$workplace}";
                                                $html .= "</p>";
                                            }
                                            $html .= "</div></div>";
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
                                            $school = htmlspecialchars($e['schoolName'] ?? 'Sekolah Tidak Diketahui');
                                            $degree = htmlspecialchars($e['degree'] ?? '');
                                            $field = htmlspecialchars($e['fieldOfStudy'] ?? '');
                                            $period = $e['period'] ?? '';
                                            $insights = $e['insights'] ?? '';
                                            $logoUrl = $e['schoolLogo']['sizes'][2]['url'] ?? $e['schoolLogo']['sizes'][0]['url'] ?? $e['schoolLogo']['url'] ?? null;
                                            $logoTag = $logoUrl
                                                ? "<img src='{$logoUrl}' class='w-11 h-11 rounded-lg object-contain bg-white p-1 shrink-0 border border-gray-200' style='min-width:44px;max-width:44px;min-height:44px;max-height:44px;' alt=''>"
                                                : "<div class='shrink-0 w-11 h-11 rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center text-gray-400' style='min-width:44px;'><svg class='w-5 h-5' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path d='M12 14l9-5-9-5-9 5 9 5z'/><path d='M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z'/></svg></div>";
                                            $html .= "<div class='flex gap-3 items-start'>" . $logoTag . "<div class='flex-1 min-w-0'><p class='font-semibold text-gray-900 dark:text-white text-sm leading-tight'>{$school}</p><p class='text-xs text-gray-600 dark:text-gray-400 mt-0.5'>{$degree}" . ($field ? " · {$field}" : "") . "</p><p class='text-xs text-gray-500 mt-0.5'>{$period}</p>" . ($insights ? "<p class='text-xs text-gray-500 mt-0.5 italic'>{$insights}</p>" : "") . "</div></div>";
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
                                            $title = htmlspecialchars($c['title'] ?? 'Sertifikasi');
                                            $issuer = htmlspecialchars($c['issuedBy'] ?? '');
                                            $issuedAt = $c['issuedAt'] ?? '';
                                            $link = $c['link'] ?? null;
                                            $logoUrl = $c['issuedByLogo']['sizes'][2]['url'] ?? $c['issuedByLogo']['sizes'][0]['url'] ?? $c['issuedByLogo']['url'] ?? null;
                                            $logoTag = $logoUrl
                                                ? "<img src='{$logoUrl}' class='w-11 h-11 rounded-lg object-contain bg-white p-1 shrink-0 border border-gray-200' style='min-width:44px;max-width:44px;min-height:44px;max-height:44px;' alt=''>"
                                                : "<div class='shrink-0 w-11 h-11 rounded-lg bg-amber-50 border border-amber-200 flex items-center justify-center' style='min-width:44px;'><svg class='w-5 h-5 text-amber-500' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z'></path></svg></div>";
                                            $credBtn = $link ? "<a href='{$link}' target='_blank' class='inline-flex items-center gap-1 mt-1.5 text-xs font-medium text-blue-500 hover:text-blue-400'><svg class='w-3 h-3' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14'></path></svg>Lihat Kredensial</a>" : '';
                                            $html .= "<div class='flex gap-3 items-start'>" . $logoTag . "<div class='flex-1 min-w-0'><p class='font-semibold text-gray-900 dark:text-white text-sm leading-tight'>{$title}</p><p class='text-xs text-gray-600 dark:text-gray-400 mt-0.5'>{$issuer}</p><p class='text-xs text-gray-500 mt-0.5'>{$issuedAt}</p>{$credBtn}</div></div>";
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
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\IconEntry::make('is_active')
                                    ->label('Akun Aktif')
                                    ->boolean(),
                                Infolists\Components\IconEntry::make('is_onboarded')
                                    ->label('Selesai Onboarding')
                                    ->boolean(),
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
