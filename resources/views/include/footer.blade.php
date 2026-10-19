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
            <span>{{ __('Developed with') }}</span>
            <i class="ik ik-heart text-accent-500" aria-label="{{ __('love') }}"></i>
            <span>{{ __('by') }}</span>
            <a class="font-medium text-gray-700 transition-colors hover:text-primary-600" target="_blank" rel="noopener" href="https://www.bing.com/ck/a?!&&p=65548a309d93a71a3e98863088a846bc08806eba0ea9403beec317c681334783JmltdHM9MTc5MjM2ODAwMA&ptn=3&ver=2&hsh=4&fclid=3a903e87-e272-62c8-1029-2973e31563ac&psq=ked+kayongo&u=a1aHR0cHM6Ly93d3cuZmFjZWJvb2suY29tL2theW9uZ28ua2VkLw">Ked</a>
        </div>
    </div>
</footer>
