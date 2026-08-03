<footer class="mt-auto border-t border-gray-100 px-4 py-4 sm:px-6">
    <div class="flex flex-col items-center justify-between gap-2 text-sm text-gray-500 sm:flex-row">
        <div class="flex items-center gap-2">
            <span class="font-medium text-gray-700">{{ config('app.name') }}</span>
            @if (config('app.version'))
                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-500">v{{ config('app.version') }}</span>
            @endif
            <span class="text-gray-300">·</span>
            <span>© {{ date('Y') }} {{ __('All rights reserved.') }}</span>
        </div>
        <div class="flex items-center gap-1.5">
            <span>{{ __('Crafted with') }}</span>
            <i class="ik ik-heart text-accent-500" aria-label="{{ __('love') }}"></i>
            <span>{{ __('by') }}</span>
            <a class="font-medium text-gray-700 transition-colors hover:text-primary-600" target="_blank" rel="noopener" href="https://themicly.com">Themicly</a>
        </div>
    </div>
</footer>
