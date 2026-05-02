@php
    $iconPath = base_path('storage/ConnectX Color 1.png');
    $iconBase64 = file_exists($iconPath) 
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($iconPath)) 
        : 'https://ui-avatars.com/api/?name=CX&background=f97316&color=fff';
@endphp

<div class="flex justify-center items-center py-4 rounded-xl overflow-hidden w-full h-full">
    
    <!-- iPhone Device Frame -->
    <div class="relative w-[280px] h-[580px] bg-black rounded-[3rem] border-[8px] border-gray-800 shadow-2xl p-1.5 flex flex-col items-center">
        
        <!-- Hardware Buttons (Volume & Power) -->
        <div class="absolute -left-[11px] top-24 w-1 h-12 bg-gray-700 rounded-l-md"></div>
        <div class="absolute -left-[11px] top-40 w-1 h-12 bg-gray-700 rounded-l-md"></div>
        <div class="absolute -right-[11px] top-32 w-1 h-16 bg-gray-700 rounded-r-md"></div>

        <!-- Dynamic Island / Notch -->
        <div class="w-24 h-7 bg-black rounded-full absolute top-3 flex justify-center items-center gap-1 z-30 border border-gray-900">
            <div class="w-2.5 h-2.5 bg-[#0a0a0f] rounded-full shadow-[inset_0_0_2px_rgba(255,255,255,0.2)]"></div>
        </div>

        @if($type === 'whatsapp')
            <!-- WhatsApp Screen -->
            <div class="w-full h-full bg-[#EFEAE2] rounded-[2.5rem] relative overflow-hidden flex flex-col z-10">
                <!-- Status Bar -->
                <div class="w-full h-12 bg-[#008069] flex justify-between items-center px-6 pt-4 z-20">
                    <span class="text-white text-[10px] font-bold">09:41</span>
                    <div class="flex gap-1 items-center text-white">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M2 11.5a.5.5 0 01.5-.5h14a.5.5 0 010 1h-14a.5.5 0 01-.5-.5z"></path></svg>
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a8 8 0 100 16 8 8 0 000-16z"></path></svg>
                    </div>
                </div>

                <!-- WhatsApp Header -->
                <div class="w-full bg-[#008069] px-3 pb-3 flex items-center gap-3 shadow-md z-20">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    <img src="{{ $iconBase64 }}" class="w-9 h-9 rounded-full bg-white p-0.5 object-cover" />
                    <div class="flex flex-col">
                        <span class="text-white font-semibold text-sm">ConnectX Official</span>
                        <span class="text-green-100 text-[10px]">Pusat Bantuan & OTP</span>
                    </div>
                </div>

                <!-- Chat Background Pattern -->
                <div class="absolute inset-0 opacity-[0.06] z-0 pointer-events-none" style="background-image: url('https://w0.peakpx.com/wallpaper/818/148/HD-wallpaper-whatsapp-background-cool-dark-green-new-theme-whatsapp.jpg'); background-size: cover;"></div>

                <!-- Chat Area -->
                <div class="flex-1 p-3 flex flex-col gap-3 overflow-y-auto z-10">
                    <!-- Date badge -->
                    <div class="flex justify-center mt-2">
                        <span class="bg-[#D9FDD3] px-3 py-1 rounded-lg text-[10px] font-medium text-gray-600 shadow-sm">Hari Ini</span>
                    </div>

                    <!-- Chat Bubble -->
                    <div class="bg-white rounded-lg rounded-tl-none p-3 max-w-[90%] shadow-sm relative self-start">
                        <!-- Tail -->
                        <div class="absolute top-0 -left-2 w-3 h-3">
                            <svg viewBox="0 0 8 13" width="8" height="13"><path opacity=".13" fill="#0000000" d="M1.533 3.568L8 12.193V1H2.812C1.042 1 .474 2.156 1.533 3.568z"></path><path fill="currentColor" class="text-white" d="M1.533 2.568L8 11.193V0H2.812C1.042 0 .474 1.156 1.533 2.568z"></path></svg>
                        </div>
                        
                        <div class="text-sm text-gray-800 leading-relaxed font-sans">
                            <strong class="block mb-2 font-bold">{{ $subject }}</strong>
                            {!! nl2br(e($body)) !!}
                        </div>
                        <div class="text-[10px] text-gray-400 text-right mt-1 w-full flex justify-end items-center gap-1">
                            09:41 
                        </div>
                    </div>
                </div>
            </div>
        @else
            <!-- Push Notification Screen (iOS Style) -->
            <div class="w-full h-full bg-gradient-to-br from-indigo-900 via-purple-900 to-black rounded-[2.5rem] p-3 pt-12 relative overflow-hidden flex flex-col z-10">
                
                <!-- Clock -->
                <div class="text-white/90 text-[3rem] tracking-tight text-center font-semibold mb-1 mt-4">09:41</div>
                <div class="text-white/70 text-sm text-center font-medium mb-8">Minggu, 3 Mei</div>

                <!-- The Notification Bubble -->
                <div class="w-full bg-white/[0.15] backdrop-blur-2xl border border-white/20 rounded-[1.25rem] p-3.5 shadow-[0_8px_30px_rgb(0,0,0,0.12)] transform transition-all duration-300">
                    
                    <!-- App Header -->
                    <div class="flex items-center gap-2 mb-2">
                        <img src="{{ $iconBase64 }}" class="w-5 h-5 rounded-md object-cover shadow-sm bg-white" />
                        <span class="text-white/80 text-[11px] uppercase font-bold tracking-wider">ConnectX</span>
                        <span class="text-white/50 text-[10px] ml-auto font-medium">sekarang</span>
                    </div>
                    
                    <!-- Content -->
                    <h4 class="text-white font-bold text-[13px] leading-tight mb-1">
                        {{ $subject }}
                    </h4>
                    
                    <p class="text-white/90 text-[13px] leading-snug break-words">
                        {!! nl2br(e($body)) !!}
                    </p>
                </div>
            </div>
        @endif
    </div>
</div>
