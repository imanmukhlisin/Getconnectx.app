<div>
    <div class="relative inline-block">
        @if ($getState())
            <img src="{{ $getState() }}" alt="Avatar" class="w-10 h-10 rounded-full object-cover shadow-sm">
        @else
            <div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-800 flex items-center justify-center shadow-sm">
                <span class="text-gray-500 dark:text-gray-400 text-xs font-bold">{{ substr($getRecord()->name ?? 'U', 0, 1) }}</span>
            </div>
        @endif
        
        @if ($getRecord()->is_pro)
            <span class="absolute bottom-0 right-0 w-3 h-3 bg-blue-500 border-2 border-white dark:border-gray-900 rounded-full" title="Pro/Premium User"></span>
        @endif
    </div>
</div>
