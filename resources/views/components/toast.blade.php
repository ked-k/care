{{-- Global toast stack. Include once per layout. --}}
<div class="pointer-events-none fixed right-4 top-4 z-[100] flex w-80 max-w-[calc(100vw-2rem)] flex-col gap-2"
     @toast.window="$store.toast.push($event.detail.message, $event.detail.type, $event.detail.timeout)">
    <template x-for="t in $store.toast.items" :key="t.id">
        <div x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="translate-x-8 opacity-0"
             x-transition:enter-end="translate-x-0 opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="pointer-events-auto flex items-start gap-3 rounded-xl border bg-white p-3.5 shadow-lg shadow-black/5"
             :class="{
                'border-green-200': t.type === 'success',
                'border-accent-500/30': t.type === 'error' || t.type === 'danger',
                'border-amber-200': t.type === 'warning',
                'border-primary-200': t.type === 'info',
             }">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-base"
                  :class="{
                    'bg-green-100 text-green-600': t.type === 'success',
                    'bg-accent-500/10 text-accent-600': t.type === 'error' || t.type === 'danger',
                    'bg-amber-100 text-amber-600': t.type === 'warning',
                    'bg-primary-100 text-primary-600': t.type === 'info' || !t.type,
                  }">
                <i :class="{
                    'ik ik-check-circle': t.type === 'success',
                    'ik ik-alert-octagon': t.type === 'error' || t.type === 'danger',
                    'ik ik-alert-triangle': t.type === 'warning',
                    'ik ik-info': t.type === 'info' || !t.type,
                }"></i>
            </span>
            <p class="flex-1 pt-1 text-sm text-gray-700" x-text="t.message"></p>
            <button @click="$store.toast.dismiss(t.id)" class="shrink-0 text-gray-300 hover:text-gray-500"><i class="ik ik-x"></i></button>
        </div>
    </template>
</div>

{{-- Bridge Laravel flash messages → toasts on page load --}}
@php
    $flash = [];
    if (session('success')) $flash[] = ['type' => 'success', 'message' => session('success')];
    if (session('status'))  $flash[] = ['type' => 'success', 'message' => session('status')];
    if (session('error'))   $flash[] = ['type' => 'error', 'message' => session('error')];
    if (session('warning')) $flash[] = ['type' => 'warning', 'message' => session('warning')];
@endphp
@if (! empty($flash))
    @push('script')
        <script>
            document.addEventListener('alpine:initialized', () => {
                @foreach ($flash as $f)
                    window.toast(@js($f['message']), @js($f['type']));
                @endforeach
            });
        </script>
    @endpush
@endif
