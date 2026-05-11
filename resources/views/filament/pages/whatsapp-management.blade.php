<x-filament-panels::page>

    {{-- ═══════════════════════════════════════════════════════════════════════
         HERO STATS — Live Overview
    ════════════════════════════════════════════════════════════════════════ --}}
    @php
        $stats = $this->getStats();
    @endphp

    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6 mb-6">

        {{-- Total Hari Ini --}}
        <div class="col-span-1 rounded-2xl border border-primary-500/20 bg-primary-500/5 p-4 text-center
                    shadow-[0_0_25px_-5px_rgba(var(--primary-500),0.25)] transition-all hover:-translate-y-1 hover:shadow-[0_0_35px_-5px_rgba(var(--primary-500),0.4)]">
            <div class="text-3xl font-black text-primary-400">{{ number_format($stats['total_today']) }}</div>
            <div class="mt-1 text-xs text-gray-400">📤 Total Hari Ini</div>
        </div>

        {{-- Sukses --}}
        <div class="col-span-1 rounded-2xl border border-green-500/20 bg-green-500/5 p-4 text-center
                    shadow-[0_0_25px_-5px_rgba(34,197,94,0.2)] transition-all hover:-translate-y-1">
            <div class="text-3xl font-black text-green-400">{{ number_format($stats['success_today']) }}</div>
            <div class="mt-1 text-xs text-gray-400">✅ Sukses</div>
        </div>

        {{-- Gagal --}}
        <div class="col-span-1 rounded-2xl border border-red-500/20 bg-red-500/5 p-4 text-center
                    shadow-[0_0_25px_-5px_rgba(239,68,68,0.2)] transition-all hover:-translate-y-1
                    {{ $stats['failed_today'] > 0 ? 'animate-pulse' : '' }}">
            <div class="text-3xl font-black text-red-400">{{ number_format($stats['failed_today']) }}</div>
            <div class="mt-1 text-xs text-gray-400">❌ Gagal</div>
        </div>

        {{-- Pending --}}
        <div class="col-span-1 rounded-2xl border border-amber-500/20 bg-amber-500/5 p-4 text-center
                    shadow-[0_0_25px_-5px_rgba(245,158,11,0.2)] transition-all hover:-translate-y-1">
            <div class="text-3xl font-black text-amber-400">{{ number_format($stats['pending']) }}</div>
            <div class="mt-1 text-xs text-gray-400">⏳ Pending</div>
        </div>

        {{-- Total Blast --}}
        <div class="col-span-1 rounded-2xl border border-purple-500/20 bg-purple-500/5 p-4 text-center
                    shadow-[0_0_25px_-5px_rgba(168,85,247,0.2)] transition-all hover:-translate-y-1">
            <div class="text-3xl font-black text-purple-400">{{ number_format($stats['total_blasts']) }}</div>
            <div class="mt-1 text-xs text-gray-400">📢 Total Blast</div>
        </div>

        {{-- Blast Aktif --}}
        <div class="col-span-1 rounded-2xl border border-sky-500/20 bg-sky-500/5 p-4 text-center
                    shadow-[0_0_25px_-5px_rgba(14,165,233,0.2)] transition-all hover:-translate-y-1">
            <div class="text-3xl font-black text-sky-400">{{ number_format($stats['active_blasts']) }}</div>
            <div class="mt-1 text-xs text-gray-400">🚀 Blast Aktif</div>
        </div>

    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════
         QUICK LINKS
    ════════════════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 gap-3 md:grid-cols-3 mb-6">

        <a href="{{ route('filament.admin.resources.whatsapp-logs.index') }}"
           class="flex items-center gap-3 rounded-xl border border-gray-700/50 bg-gray-800/40 px-4 py-3
                  hover:border-primary-500/40 hover:bg-primary-500/5 transition-all group">
            <x-heroicon-o-chat-bubble-left-right class="w-6 h-6 text-gray-400 group-hover:text-primary-400 transition-colors"/>
            <div>
                <div class="text-sm font-semibold text-gray-200 group-hover:text-white">Log Pesan WA</div>
                <div class="text-xs text-gray-500">Pantau semua pesan keluar/masuk</div>
            </div>
            <x-heroicon-m-arrow-right class="w-4 h-4 text-gray-600 group-hover:text-primary-400 ml-auto transition-colors"/>
        </a>

        <a href="{{ route('filament.admin.resources.whatsapp-blasts.index') }}"
           class="flex items-center gap-3 rounded-xl border border-gray-700/50 bg-gray-800/40 px-4 py-3
                  hover:border-purple-500/40 hover:bg-purple-500/5 transition-all group">
            <x-heroicon-o-megaphone class="w-6 h-6 text-gray-400 group-hover:text-purple-400 transition-colors"/>
            <div>
                <div class="text-sm font-semibold text-gray-200 group-hover:text-white">Blasting WA</div>
                <div class="text-xs text-gray-500">Kirim pesan ke banyak user</div>
            </div>
            <x-heroicon-m-arrow-right class="w-4 h-4 text-gray-600 group-hover:text-purple-400 ml-auto transition-colors"/>
        </a>

        <a href="https://business.facebook.com/wa/manage/phone-numbers/" target="_blank"
           class="flex items-center gap-3 rounded-xl border border-gray-700/50 bg-gray-800/40 px-4 py-3
                  hover:border-blue-500/40 hover:bg-blue-500/5 transition-all group">
            <x-heroicon-o-arrow-top-right-on-square class="w-6 h-6 text-gray-400 group-hover:text-blue-400 transition-colors"/>
            <div>
                <div class="text-sm font-semibold text-gray-200 group-hover:text-white">Meta Business Suite</div>
                <div class="text-xs text-gray-500">Buka dashboard Meta WABA</div>
            </div>
            <x-heroicon-m-arrow-right class="w-4 h-4 text-gray-600 group-hover:text-blue-400 ml-auto transition-colors"/>
        </a>

    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════
         PANDUAN SETUP META WABA — Info Card
    ════════════════════════════════════════════════════════════════════════ --}}
    <div class="rounded-2xl border border-blue-500/20 bg-gradient-to-br from-blue-950/30 to-indigo-950/20 p-5 mb-6">
        <div class="flex items-start gap-3 mb-4">
            <div class="flex-shrink-0 w-9 h-9 rounded-xl bg-blue-500/20 flex items-center justify-center">
                <x-heroicon-m-information-circle class="w-5 h-5 text-blue-400"/>
            </div>
            <div>
                <h3 class="font-bold text-blue-300 text-sm">📖 Panduan Setup Meta WhatsApp Business API</h3>
                <p class="text-xs text-blue-400/70 mt-0.5">Ikuti langkah berikut untuk mendaftarkan nomor WhatsApp Business resmi via Meta.</p>
            </div>
        </div>
        <div class="grid grid-cols-1 gap-2 md:grid-cols-2 lg:grid-cols-4">
            @foreach([
                ['1', 'Buat Meta App', 'Buka developers.facebook.com → buat app bertipe Business, aktifkan produk WhatsApp.'],
                ['2', 'Dapatkan Token', 'Buat System User di Business Settings → generate token permanen dengan scope whatsapp_business_messaging.'],
                ['3', 'Daftarkan Webhook', 'Masukkan Webhook URL & Verify Token di bawah ke kolom Webhook App Meta. Pastikan endpoint bisa diakses publik.'],
                ['4', 'Isi Form Ini', 'Tempel Phone Number ID, WABA ID, Access Token, App Secret, dan Verify Token ke form konfigurasi di bawah, lalu simpan.'],
            ] as [$step, $title, $desc])
            <div class="flex gap-3 rounded-xl bg-blue-900/20 border border-blue-700/20 p-3">
                <div class="flex-shrink-0 w-6 h-6 rounded-full bg-blue-500/30 text-blue-300 text-xs font-black flex items-center justify-center">{{ $step }}</div>
                <div>
                    <div class="text-xs font-bold text-blue-200">{{ $title }}</div>
                    <div class="text-xs text-blue-400/70 mt-0.5 leading-relaxed">{{ $desc }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════
         MAIN FORM
    ════════════════════════════════════════════════════════════════════════ --}}
    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}

        <div class="flex justify-end mt-4 gap-3">
            <x-filament::button
                type="submit"
                size="lg"
                color="success"
                icon="heroicon-m-check-circle"
            >
                Simpan Konfigurasi
            </x-filament::button>
        </div>
    </x-filament-panels::form>

</x-filament-panels::page>
