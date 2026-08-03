@php
    // Flatten the configured menus into searchable destinations.
    $flatten = function ($items, $section) {
        $out = [];
        foreach ($items as $i) {
            if (! empty($i['heading'])) continue;
            $icon = $i['icon'] ?? 'ik ik-corner-down-right';
            if (! empty($i['children'])) {
                foreach ($i['children'] as $c) {
                    $out[] = [
                        'label' => $i['label'] . ' · ' . $c['label'],
                        'icon' => $c['icon'] ?? $icon,
                        'url' => ! empty($c['route']) ? route($c['route']) : url($c['url'] ?? '#'),
                        'section' => $section,
                    ];
                }
            } else {
                $out[] = [
                    'label' => $i['label'],
                    'icon' => $icon,
                    'url' => ! empty($i['route']) ? route($i['route']) : url($i['url'] ?? '#'),
                    'section' => $section,
                ];
            }
        }
        return $out;
    };

    $quickActions = [
        ['label' => __('New User'), 'icon' => 'ik ik-user-plus', 'url' => url('user/create'), 'section' => __('Quick action')],
        ['label' => __('New Product'), 'icon' => 'ik ik-plus-square', 'url' => url('products/create'), 'section' => __('Quick action')],
        ['label' => __('New Sale'), 'icon' => 'ik ik-shopping-cart', 'url' => url('sales/create'), 'section' => __('Quick action')],
        ['label' => __('New Invoice'), 'icon' => 'ik ik-file-text', 'url' => url('income/invoice/create'), 'section' => __('Quick action')],
        ['label' => __('Open POS'), 'icon' => 'ik ik-credit-card', 'url' => url('pos'), 'section' => __('Quick action')],
    ];

    $items = array_merge(
        $quickActions,
        $flatten(config('menu.main', []), __('Main')),
        $flatten(config('menu.inventory', []), __('Inventory')),
        $flatten(config('menu.accounting', []), __('Accounting')),
    );
@endphp

<div x-data="commandPalette(@js($items))"
     @open-command.window="openPalette()"
     @keydown.window.k="if ($event.metaKey || $event.ctrlKey) { $event.preventDefault(); openPalette(); }"
     x-show="open" x-transition.opacity style="display:none"
     @keydown.escape.window="open = false"
     class="fixed inset-0 z-[120] flex items-start justify-center bg-black/40 p-4 pt-[12vh]">

    <div x-show="open" x-trap.noscroll="open" @click.outside="open = false"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
         role="dialog" aria-modal="true" aria-label="{{ __('Command palette') }}"
         class="w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5">

        <div class="flex items-center gap-3 border-b border-gray-100 px-4">
            <i class="ik ik-search text-gray-400"></i>
            <input x-ref="input" x-model="query" @input="active = 0" type="text"
                   @keydown.arrow-down.prevent="move(1)"
                   @keydown.arrow-up.prevent="move(-1)"
                   @keydown.enter.prevent="go()"
                   placeholder="{{ __('Search pages and actions...') }}"
                   class="w-full bg-transparent py-4 text-sm text-gray-700 outline-none placeholder:text-gray-400">
            <kbd class="rounded border border-gray-200 px-1.5 py-0.5 text-[10px] font-medium text-gray-400">ESC</kbd>
        </div>

        <ul class="max-h-80 overflow-y-auto p-2" @mouseleave="active = -1">
            <template x-for="(item, i) in results" :key="item.url + i">
                <li>
                    <a :href="item.url" @mouseenter="active = i"
                       :class="active === i ? 'bg-primary-50 text-primary-700' : 'text-gray-600'"
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition-colors">
                        <i :class="item.icon" class="w-5 text-center text-base opacity-70"></i>
                        <span class="flex-1" x-text="item.label"></span>
                        <span class="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium text-gray-400" x-text="item.section"></span>
                    </a>
                </li>
            </template>
            <li x-show="results.length === 0" class="px-3 py-10 text-center text-sm text-gray-400">
                {{ __('No results for') }} "<span x-text="query"></span>"
            </li>
        </ul>

        <div class="flex items-center gap-4 border-t border-gray-100 px-4 py-2 text-[11px] text-gray-400">
            <span><kbd class="font-sans">↑</kbd> <kbd class="font-sans">↓</kbd> {{ __('navigate') }}</span>
            <span><kbd class="font-sans">↵</kbd> {{ __('open') }}</span>
            <span class="ml-auto"><kbd class="font-sans">⌘</kbd><kbd class="font-sans">K</kbd> {{ __('toggle') }}</span>
        </div>
    </div>
</div>

@once
    @push('script')
        <script>
            function commandPalette(items) {
                return {
                    open: false,
                    query: '',
                    active: 0,
                    items: items,
                    get results() {
                        const q = this.query.trim().toLowerCase();
                        const list = q
                            ? this.items.filter((i) => i.label.toLowerCase().includes(q))
                            : this.items;
                        return list.slice(0, 12);
                    },
                    openPalette() {
                        this.open = true;
                        this.query = '';
                        this.active = 0;
                        this.$nextTick(() => this.$refs.input.focus());
                    },
                    move(dir) {
                        const n = this.results.length;
                        if (!n) return;
                        this.active = (this.active + dir + n) % n;
                    },
                    go() {
                        const item = this.results[this.active];
                        if (item) window.location = item.url;
                    },
                    init() {
                        this.$watch('open', (v) => { if (!v) this.query = ''; });
                    },
                    bindKeys() {},
                };
            }
        </script>
    @endpush
@endonce
