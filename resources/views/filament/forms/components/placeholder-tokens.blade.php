<div class="flex flex-wrap gap-2 py-1">
    @forelse($tokens as $token => $label)
        <button
            type="button"
            title="Klik untuk menyalin variabel ini ke clipboard"
            onclick="
                navigator.clipboard.writeText('{{ $token }}').then(() => {
                    const el = this;
                    const orig = el.innerHTML;
                    el.innerHTML = '<span class=\'opacity-80\'>✅ Tersalin!</span>';
                    el.classList.add('ring-2', 'ring-green-400');
                    setTimeout(() => { el.innerHTML = orig; el.classList.remove('ring-2', 'ring-green-400'); }, 1500);
                });
            "
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-mono font-semibold
                   bg-gray-700 text-amber-300 border border-amber-400/40
                   hover:bg-amber-400/10 hover:border-amber-400 hover:text-amber-200
                   active:scale-95 transition-all duration-150 cursor-pointer select-none"
        >
            <span class="text-amber-400">{{ $token }}</span>
            <span class="text-gray-400 font-sans font-normal">→ {{ $label }}</span>
        </button>
    @empty
        <p class="text-sm text-gray-500 italic">Tidak ada variabel tersedia untuk template ini.</p>
    @endforelse
</div>
<p class="mt-2 text-xs text-gray-500">
    💡 Klik tombol di atas untuk menyalin variabel, lalu tempel di kolom pesan. Format <code class="bg-gray-700 px-1 rounded text-amber-300">[..]</code> harus tetap persis seperti ini agar sistem bisa membaca nilainya.
</p>
