{{-- Global confirmation dialog singleton (driven by $store.confirm). Include once per layout. --}}
<div x-data x-show="$store.confirm.show" x-transition.opacity style="display:none"
     @keydown.escape.window="$store.confirm.cancel()"
     class="fixed inset-0 z-[110] flex items-center justify-center bg-black/40 p-4">
    <div x-show="$store.confirm.show" x-trap.noscroll="$store.confirm.show"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
         @click.outside="$store.confirm.cancel()"
         role="dialog" aria-modal="true"
         class="w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-xl">
        <span class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full"
              :class="$store.confirm.tone === 'danger' ? 'bg-accent-500/10 text-accent-600' : 'bg-primary-100 text-primary-600'">
            <i :class="$store.confirm.icon" class="text-2xl"></i>
        </span>
        <h3 class="text-lg font-semibold text-gray-800" x-text="$store.confirm.title"></h3>
        <p class="mt-1 text-sm text-gray-500" x-text="$store.confirm.message"></p>
        <div class="mt-6 flex gap-3">
            <button @click="$store.confirm.cancel()"
                    class="flex-1 rounded-lg border border-gray-200 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-50"
                    x-text="$store.confirm.cancelText"></button>
            <button @click="$store.confirm.confirm()" x-ref="confirmBtn"
                    class="flex-1 rounded-lg py-2.5 text-sm font-medium text-white transition"
                    :class="$store.confirm.tone === 'danger' ? 'bg-accent-500 hover:bg-accent-600' : 'bg-primary-500 hover:bg-primary-600'"
                    x-text="$store.confirm.confirmText"></button>
        </div>
    </div>
</div>
