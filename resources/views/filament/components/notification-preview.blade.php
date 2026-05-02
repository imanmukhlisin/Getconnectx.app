<div class="flex justify-center items-center py-4 rounded-xl overflow-hidden">
    <div class="relative w-64 h-[450px] bg-black rounded-[2.5rem] border-[6px] border-gray-800 shadow-2xl p-2 flex flex-col items-center">
        
        <!-- Notch / Dynamic Island -->
        <div class="w-24 h-6 bg-gray-800 rounded-full absolute top-2 flex justify-center items-center gap-1 z-20">
            <div class="w-2 h-2 bg-black rounded-full opacity-50"></div>
        </div>
        
        <!-- Screen background (wallpaper) -->
        <div class="w-full h-full bg-gradient-to-br from-indigo-900 via-purple-900 to-black rounded-[2rem] p-3 pt-12 relative overflow-hidden flex flex-col">
            
            <!-- Clock -->
            <div class="text-white/90 text-sm text-center font-semibold mb-6">09:41</div>

            <!-- The Notification Bubble -->
            <div class="w-full bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-3 shadow-[0_8px_30px_rgb(0,0,0,0.12)] transform transition-all duration-300">
                
                <!-- App Header -->
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-5 h-5 rounded bg-[#f97316] flex items-center justify-center text-white text-[10px] font-bold">
                        CX
                    </div>
                    <span class="text-white/70 text-[10px] uppercase font-bold tracking-wider">ConnectX</span>
                    <span class="text-white/40 text-[10px] ml-auto">sekarang</span>
                </div>
                
                <!-- Content -->
                <h4 class="text-white font-semibold text-sm leading-tight mb-1">
                    {{ $subject }}
                </h4>
                
                <p class="text-white/80 text-xs leading-snug break-words">
                    {!! nl2br(e($body)) !!}
                </p>
            </div>

        </div>
    </div>
</div>
