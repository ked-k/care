@php
    // Full predefined palettes — modern admin/CRM looks. Each sets sidebar + topbar + primary together.
    $presets = [
        ['name' => 'Default', 'sidebar' => '#1e1e2d', 'header' => '#272d36', 'sbText' => '#ccd3e4', 'sbIcon' => '#525c71', 'topbar' => '#ffffff', 'tbText' => '#4a5361', 'p5' => '#007bff', 'p6' => '#006fe6'],
        ['name' => 'Indigo', 'sidebar' => '#1e1b4b', 'header' => '#312e81', 'sbText' => '#c7d2fe', 'sbIcon' => '#818cf8', 'topbar' => '#ffffff', 'tbText' => '#334155', 'p5' => '#6366f1', 'p6' => '#4f46e5'],
        ['name' => 'Violet', 'sidebar' => '#2e1065', 'header' => '#4c1d95', 'sbText' => '#ddd6fe', 'sbIcon' => '#a78bfa', 'topbar' => '#ffffff', 'tbText' => '#334155', 'p5' => '#8b5cf6', 'p6' => '#7c3aed'],
        ['name' => 'Emerald', 'sidebar' => '#022c22', 'header' => '#064e3b', 'sbText' => '#a7f3d0', 'sbIcon' => '#34d399', 'topbar' => '#ffffff', 'tbText' => '#334155', 'p5' => '#10b981', 'p6' => '#059669'],
        ['name' => 'Sky', 'sidebar' => '#082f49', 'header' => '#0c4a6e', 'sbText' => '#bae6fd', 'sbIcon' => '#38bdf8', 'topbar' => '#ffffff', 'tbText' => '#334155', 'p5' => '#0ea5e9', 'p6' => '#0284c7'],
        ['name' => 'Rose', 'sidebar' => '#4c0519', 'header' => '#881337', 'sbText' => '#fecdd3', 'sbIcon' => '#fb7185', 'topbar' => '#ffffff', 'tbText' => '#334155', 'p5' => '#f43f5e', 'p6' => '#e11d48'],
        ['name' => 'Sunset', 'sidebar' => '#ffffff', 'header' => '#f8fafc', 'sbText' => '#475569', 'sbIcon' => '#f59e0b', 'topbar' => '#ffffff', 'tbText' => '#475569', 'p5' => '#f59e0b', 'p6' => '#d97706'],
        ['name' => 'Slate', 'sidebar' => '#0f172a', 'header' => '#1e293b', 'sbText' => '#cbd5e1', 'sbIcon' => '#94a3b8', 'topbar' => '#ffffff', 'tbText' => '#334155', 'p5' => '#14b8a6', 'p6' => '#0d9488'],
    ];

    $sidebars = [
        ['label' => 'Dark', 'bg' => '#1e1e2d', 'header' => '#272d36', 'text' => '#ccd3e4', 'icon' => '#525c71'],
        ['label' => 'Navy', 'bg' => '#0f172a', 'header' => '#1e293b', 'text' => '#cbd5e1', 'icon' => '#64748b'],
        ['label' => 'Carbon', 'bg' => '#18181b', 'header' => '#27272a', 'text' => '#d4d4d8', 'icon' => '#71717a'],
        ['label' => 'Indigo', 'bg' => '#312e81', 'header' => '#3730a3', 'text' => '#e0e7ff', 'icon' => '#a5b4fc'],
        ['label' => 'Light', 'bg' => '#ffffff', 'header' => '#eef2f7', 'text' => '#475569', 'icon' => '#94a3b8'],
    ];

    $topbars = [
        ['label' => 'White', 'bg' => '#ffffff', 'text' => '#4a5361'],
        ['label' => 'Light', 'bg' => '#f8fafc', 'text' => '#475569'],
        ['label' => 'Dark', 'bg' => '#1e293b', 'text' => '#e2e8f0'],
    ];

    // Each primary color is [base, hover]. Modern, vivid hues used across contemporary admin panels.
    $primaries = [
        ['#3b82f6', '#2563eb'], ['#6366f1', '#4f46e5'], ['#8b5cf6', '#7c3aed'], ['#d946ef', '#c026d3'],
        ['#f43f5e', '#e11d48'], ['#f97316', '#ea580c'], ['#f59e0b', '#d97706'], ['#10b981', '#059669'],
        ['#14b8a6', '#0d9488'], ['#0ea5e9', '#0284c7'], ['#06b6d4', '#0891b2'],
    ];

    $textPrimaries = ['#1e293b', '#334155', '#111827', '#3f3f46', '#1f2937'];
    $textSecondaries = ['#94a3b8', '#64748b', '#9ca3af', '#a1a1aa', '#6b7280'];
@endphp

<div x-data="themeCustomizer()"
     @open-theme.window="open = true"
     @keydown.escape.window="open = false">

    <!-- Backdrop + drawer -->
    <div x-show="open" x-transition.opacity class="fixed inset-0 z-50 bg-black/40" style="display:none">
        <aside class="absolute inset-y-0 right-0 flex w-80 max-w-full flex-col bg-white shadow-2xl"
               @click.outside="open = false"
               x-transition:enter="transition ease-out duration-200"
               x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0">

            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <div>
                    <h6 class="flex items-center gap-2 font-semibold text-gray-800"><i class="ik ik-droplet text-primary-600"></i> {{ __('Theme Customizer') }}</h6>
                    <p class="text-xs text-gray-400">{{ __('Live preview · saved to this browser') }}</p>
                </div>
                <button @click="open = false" class="text-gray-400 hover:text-gray-600"><i class="ik ik-x"></i></button>
            </div>

            <div class="flex-1 space-y-6 overflow-y-auto px-5 py-5">

                <!-- Presets -->
                <section>
                    <h6 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Preset Palettes') }}</h6>
                    <div class="grid grid-cols-2 gap-2.5">
                        @foreach ($presets as $p)
                            <button type="button"
                                    @click='applyPreset(@json($p))'
                                    class="group overflow-hidden rounded-xl border border-gray-200 text-left transition hover:border-primary-400 hover:shadow-sm">
                                <div class="flex h-10">
                                    <span class="w-1/4" style="background:{{ $p['sidebar'] }}"></span>
                                    <span class="flex flex-1 items-center justify-end px-2" style="background:{{ $p['topbar'] }}">
                                        <span class="h-3 w-3 rounded-full" style="background:{{ $p['p5'] }}"></span>
                                    </span>
                                </div>
                                <span class="block px-2 py-1.5 text-xs font-medium text-gray-600">{{ $p['name'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </section>

                <!-- Sidebar -->
                <section>
                    <h6 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Sidebar') }}</h6>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($sidebars as $s)
                            <button type="button" title="{{ $s['label'] }}"
                                    @click="set({'--color-sidebar':'{{ $s['bg'] }}','--color-sidebar-header':'{{ $s['header'] }}','--color-sidebar-text':'{{ $s['text'] }}','--color-sidebar-icon':'{{ $s['icon'] }}'})"
                                    class="h-9 w-9 rounded-lg border-2 ring-offset-2 transition"
                                    :class="cur('--color-sidebar') === '{{ $s['bg'] }}' ? 'border-primary-500 ring-2 ring-primary-300' : 'border-white shadow'"
                                    style="background:{{ $s['bg'] }}"></button>
                        @endforeach
                        <label class="relative flex h-9 w-9 cursor-pointer items-center justify-center rounded-lg border-2 border-dashed border-gray-300 text-gray-400" title="{{ __('Custom') }}">
                            <i class="ik ik-droplet"></i>
                            <input type="color" class="absolute inset-0 cursor-pointer opacity-0"
                                   @input="set({'--color-sidebar':$event.target.value,'--color-sidebar-header':$event.target.value})">
                        </label>
                    </div>
                </section>

                <!-- Topbar -->
                <section>
                    <h6 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Top Bar') }}</h6>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($topbars as $t)
                            <button type="button" title="{{ $t['label'] }}"
                                    @click="set({'--color-topbar':'{{ $t['bg'] }}','--color-topbar-text':'{{ $t['text'] }}'})"
                                    class="h-9 w-9 rounded-lg border-2 transition"
                                    :class="cur('--color-topbar') === '{{ $t['bg'] }}' ? 'border-primary-500 ring-2 ring-primary-300' : 'border-gray-200 shadow'"
                                    style="background:{{ $t['bg'] }}"></button>
                        @endforeach
                        <label class="relative flex h-9 w-9 cursor-pointer items-center justify-center rounded-lg border-2 border-dashed border-gray-300 text-gray-400" title="{{ __('Custom') }}">
                            <i class="ik ik-droplet"></i>
                            <input type="color" class="absolute inset-0 cursor-pointer opacity-0"
                                   @input="set({'--color-topbar':$event.target.value})">
                        </label>
                    </div>
                </section>

                <!-- Primary color -->
                <section>
                    <h6 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Primary Color') }}</h6>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($primaries as [$base, $hover])
                            <button type="button"
                                    @click="set({'--color-primary-500':'{{ $base }}','--color-primary-600':'{{ $hover }}'})"
                                    class="h-9 w-9 rounded-full border-2 transition"
                                    :class="cur('--color-primary-500') === '{{ $base }}' ? 'border-gray-800 ring-2 ring-gray-300' : 'border-white shadow'"
                                    style="background:{{ $base }}"></button>
                        @endforeach
                        <label class="relative flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border-2 border-dashed border-gray-300 text-gray-400" title="{{ __('Custom') }}">
                            <i class="ik ik-droplet"></i>
                            <input type="color" class="absolute inset-0 cursor-pointer rounded-full opacity-0"
                                   @input="set({'--color-primary-500':$event.target.value,'--color-primary-600':$event.target.value})">
                        </label>
                    </div>
                </section>

                <!-- Primary text -->
                <section>
                    <h6 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Primary Text') }}</h6>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($textPrimaries as $c)
                            <button type="button"
                                    @click="set({'--color-gray-900':'{{ $c }}','--color-gray-800':'{{ $c }}','--color-gray-700':'{{ $c }}'})"
                                    class="h-9 w-9 rounded-lg border-2 transition"
                                    :class="cur('--color-gray-700') === '{{ $c }}' ? 'border-primary-500 ring-2 ring-primary-300' : 'border-white shadow'"
                                    style="background:{{ $c }}"></button>
                        @endforeach
                    </div>
                </section>

                <!-- Secondary text -->
                <section>
                    <h6 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Secondary Text') }}</h6>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($textSecondaries as $c)
                            <button type="button"
                                    @click="set({'--color-gray-500':'{{ $c }}','--color-gray-400':'{{ $c }}'})"
                                    class="h-9 w-9 rounded-lg border-2 transition"
                                    :class="cur('--color-gray-400') === '{{ $c }}' ? 'border-primary-500 ring-2 ring-primary-300' : 'border-white shadow'"
                                    style="background:{{ $c }}"></button>
                        @endforeach
                    </div>
                </section>
            </div>

            <div class="border-t border-gray-100 p-4">
                <button type="button" @click="reset()"
                        class="flex w-full items-center justify-center gap-2 rounded-lg border border-gray-200 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-50">
                    <i class="ik ik-refresh-ccw"></i> {{ __('Reset to default') }}
                </button>
            </div>
        </aside>
    </div>
</div>

@once
    @push('script')
        <script>
            function themeCustomizer() {
                return {
                    open: false,
                    overrides: {},
                    init() {
                        try { this.overrides = JSON.parse(localStorage.getItem('radmin-theme') || '{}'); }
                        catch (e) { this.overrides = {}; }
                        this.apply();
                    },
                    apply() {
                        const r = document.documentElement;
                        for (const k in this.overrides) r.style.setProperty(k, this.overrides[k]);
                    },
                    set(vars) {
                        Object.assign(this.overrides, vars);
                        localStorage.setItem('radmin-theme', JSON.stringify(this.overrides));
                        this.apply();
                    },
                    applyPreset(p) {
                        this.set({
                            '--color-sidebar': p.sidebar, '--color-sidebar-header': p.header,
                            '--color-sidebar-text': p.sbText, '--color-sidebar-icon': p.sbIcon,
                            '--color-topbar': p.topbar, '--color-topbar-text': p.tbText,
                            '--color-primary-500': p.p5, '--color-primary-600': p.p6,
                        });
                    },
                    cur(name) {
                        return (this.overrides[name] || '').toLowerCase();
                    },
                    reset() {
                        const r = document.documentElement;
                        for (const k in this.overrides) r.style.removeProperty(k);
                        this.overrides = {};
                        localStorage.removeItem('radmin-theme');
                    },
                };
            }
        </script>
    @endpush
@endonce
