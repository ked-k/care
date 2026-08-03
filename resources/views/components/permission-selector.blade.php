@props([
    'permissions' => [],        // id => name  (e.g. Permission::pluck('name', 'id'))
    'selected' => [],           // array of selected ids (edit mode)
    'name' => 'permissions',    // form field name → submits as name[]
    'channel' => 'permissions', // event channel: dispatch `permission-set` { channel, ids } to set selection
])

@php
    $selectedIds = array_map('strval', (array) $selected);

    $humanize = fn ($v) => ucwords(str_replace(['_', '-', '.'], ' ', $v));

    // Each group gets a colour so the assignment journey reads at a glance.
    $palette = [
        ['chip' => 'bg-primary-100 text-primary-700', 'sel' => 'border-primary-300 bg-primary-50',  'accent' => 'text-primary-600',  'ring' => 'bg-primary-500'],
        ['chip' => 'bg-violet-100 text-violet-700',   'sel' => 'border-violet-300 bg-violet-50',     'accent' => 'text-violet-600',   'ring' => 'bg-violet-500'],
        ['chip' => 'bg-emerald-100 text-emerald-700', 'sel' => 'border-emerald-300 bg-emerald-50',   'accent' => 'text-emerald-600',  'ring' => 'bg-emerald-500'],
        ['chip' => 'bg-amber-100 text-amber-700',     'sel' => 'border-amber-300 bg-amber-50',       'accent' => 'text-amber-600',    'ring' => 'bg-amber-500'],
        ['chip' => 'bg-rose-100 text-rose-700',       'sel' => 'border-rose-300 bg-rose-50',         'accent' => 'text-rose-600',     'ring' => 'bg-rose-500'],
        ['chip' => 'bg-sky-100 text-sky-700',         'sel' => 'border-sky-300 bg-sky-50',           'accent' => 'text-sky-600',      'ring' => 'bg-sky-500'],
        ['chip' => 'bg-indigo-100 text-indigo-700',   'sel' => 'border-indigo-300 bg-indigo-50',     'accent' => 'text-indigo-600',   'ring' => 'bg-indigo-500'],
    ];

    // Group permissions by the resource part of "{action}_{resource}" (e.g. manage_user → "user").
    $grouped = [];
    foreach ($permissions as $id => $permName) {
        $parts = preg_split('/[_\-.]/', (string) $permName, 2);
        $key = count($parts) === 2 ? $parts[1] : 'general';
        $action = count($parts) === 2 ? $parts[0] : (string) $permName;

        $grouped[$key][] = [
            'id' => (string) $id,
            'name' => (string) $permName,
            'actionLabel' => $humanize($action),
        ];
    }
    ksort($grouped);

    $groupsForJs = [];
    $i = 0;
    foreach ($grouped as $key => $items) {
        $groupsForJs[] = [
            'key' => $key,
            'label' => $humanize($key),
            'initial' => strtoupper(mb_substr($key, 0, 1)),
            'colors' => $palette[$i % count($palette)],
            'ids' => array_map(fn ($it) => $it['id'], $items),
            'items' => $items,
        ];
        $i++;
    }
@endphp

<div
    x-data="{
        nm: @js($name),
        channel: @js($channel),
        q: '',
        selected: @js($selectedIds),
        groups: @js($groupsForJs),
        get total() { return this.groups.reduce((n, g) => n + g.ids.length, 0) },
        groupSel(g) { return g.ids.filter(id => this.selected.includes(id)).length },
        allOn(g) { return g.ids.length > 0 && g.ids.every(id => this.selected.includes(id)) },
        someOn(g) { const n = this.groupSel(g); return n > 0 && n < g.ids.length },
        toggleGroup(g) {
            if (this.allOn(g)) { this.selected = this.selected.filter(id => !g.ids.includes(id)) }
            else { const s = new Set(this.selected); g.ids.forEach(id => s.add(id)); this.selected = [...s] }
        },
        selectAll() { const s = new Set(); this.groups.forEach(g => g.ids.forEach(id => s.add(id))); this.selected = [...s] },
        clearAll() { this.selected = [] },
        norm(v) { return (v || '').toString().toLowerCase() },
        itemMatch(it) { const q = this.norm(this.q); return !q || this.norm(it.actionLabel).includes(q) || this.norm(it.name).includes(q) },
        groupMatch(g) { const q = this.norm(this.q); return !q || this.norm(g.label).includes(q) || g.items.some(it => this.itemMatch(it)) },
        get empty() { return this.q !== '' && this.groups.every(g => !this.groupMatch(g)) },
        get pct() { return this.total ? Math.round((this.selected.length / this.total) * 100) : 0 },
    }"
    x-on:permission-set.window="if ($event.detail?.channel === channel) { selected = ($event.detail.ids || []).map(String); q = '' }"
    class="overflow-hidden rounded-2xl border border-gray-200 bg-white"
>
    {{-- Toolbar: search · live counter · progress · bulk actions --}}
    <div class="border-b border-gray-100 bg-gradient-to-r from-primary-50/70 via-white to-white p-3">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative w-full sm:max-w-xs">
                <i class="ik ik-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400"></i>
                <input type="text" x-model="q" placeholder="{{ __('Search permissions...') }}"
                       class="w-full rounded-lg border border-gray-200 bg-white py-2 pl-9 pr-3 text-sm outline-none transition focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
            </div>
            <div class="flex items-center justify-between gap-3 sm:justify-end">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-primary-100 px-3 py-1 text-xs font-medium text-primary-700">
                    <i class="ik ik-check-square text-sm"></i>
                    <span x-text="selected.length"></span> / <span x-text="total"></span>
                </span>
                <div class="flex items-center gap-1.5">
                    <button type="button" @click="selectAll()"
                            class="rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-600 transition hover:border-primary-200 hover:bg-primary-50 hover:text-primary-700">
                        {{ __('Select all') }}
                    </button>
                    <button type="button" @click="clearAll()"
                            class="rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-50"
                            :class="selected.length === 0 && 'pointer-events-none opacity-50'">
                        {{ __('Clear') }}
                    </button>
                </div>
            </div>
        </div>
        {{-- Progress bar --}}
        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-gray-100">
            <div class="h-full rounded-full bg-gradient-to-r from-primary-400 to-primary-600 transition-all duration-300"
                 :style="`width: ${pct}%`"></div>
        </div>
    </div>

    {{-- Grouped, collapsible permission list --}}
    <div class="max-h-[26rem] divide-y divide-gray-100 overflow-y-auto">
        <template x-for="g in groups" :key="g.key">
            <div x-show="groupMatch(g)" x-data="{ open: true }">
                {{-- Group header --}}
                <div class="flex cursor-pointer items-center gap-3 px-4 py-3 transition hover:bg-gray-50"
                     role="button" tabindex="0" @click="open = !open" @keydown.enter="open = !open">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-sm font-bold"
                          :class="g.colors.chip" x-text="g.initial"></span>
                    <div class="min-w-0">
                        <span class="block font-medium capitalize leading-tight text-gray-700" x-text="g.label"></span>
                        <span class="block text-xs text-gray-400">
                            <span class="font-semibold" :class="groupSel(g) > 0 ? g.colors.accent : ''" x-text="groupSel(g)"></span>
                            {{ __('of') }} <span x-text="g.ids.length"></span> {{ __('selected') }}
                        </span>
                    </div>
                    <button type="button" @click.stop="toggleGroup(g)"
                            class="ml-auto rounded-md px-2 py-1 text-xs font-medium transition hover:bg-white"
                            :class="g.colors.accent"
                            x-text="allOn(g) ? '{{ __('Clear') }}' : '{{ __('Select all') }}'"></button>
                    <i class="ik ik-chevron-down text-sm text-gray-400 transition-transform"
                       :class="(open || q !== '') && 'rotate-180'"></i>
                </div>

                {{-- Group body --}}
                <div x-show="open || q !== ''" x-collapse class="px-4 pb-4">
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <template x-for="it in g.items" :key="it.id">
                            <label x-show="itemMatch(it)"
                                   class="flex cursor-pointer items-start gap-2.5 rounded-xl border px-3 py-2.5 text-sm transition"
                                   :class="selected.includes(it.id)
                                       ? g.colors.sel + ' shadow-sm'
                                       : 'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50'">
                                <input type="checkbox" :name="nm + '[]'" :value="it.id" x-model="selected"
                                       class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-primary-600 focus:ring-2 focus:ring-primary-200">
                                <span class="min-w-0">
                                    <span class="block font-medium leading-tight text-gray-700" x-text="it.actionLabel"></span>
                                    <span class="block truncate text-xs text-gray-400" x-text="it.name"></span>
                                </span>
                            </label>
                        </template>
                    </div>
                </div>
            </div>
        </template>

        {{-- No search results --}}
        <div x-show="empty" class="px-4 py-10 text-center text-sm text-gray-400">
            <i class="ik ik-search mb-2 block text-2xl text-gray-300"></i>
            {{ __('No permissions match') }} "<span x-text="q" class="font-medium text-gray-500"></span>"
        </div>
    </div>
</div>
