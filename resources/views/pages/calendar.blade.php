@extends('layouts.main')
@section('title', 'Calendar')
@section('content')
    <x-page-header
        title="{{ __('Calendar') }}"
        subtitle="{{ __('Click any day to add an event, or click an event to edit it.') }}"
        icon="ik ik-calendar"
        :breadcrumbs="['Home' => route('dashboard'), 'Calendar' => null]"
    />

    <div
        x-data="calendar()"
        x-init="init()"
        @keydown.escape.window="closeModal()"
        class="grid grid-cols-1 gap-5 xl:grid-cols-4"
    >
        <!-- Sidebar: upcoming events -->
        <x-card class="xl:col-span-1" no-padding>
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h5 class="font-semibold text-gray-700">{{ __('Upcoming Events') }}</h5>
                <x-button variant="primary" size="sm" x-on:click="openCreate(todayStr)">
                    <i class="ik ik-plus"></i>{{ __('New') }}
                </x-button>
            </div>
            <div class="p-3">
                <template x-if="upcoming.length === 0">
                    <p class="px-2 py-6 text-center text-sm text-gray-400">{{ __('No upcoming events.') }}</p>
                </template>
                <ul class="space-y-1.5">
                    <template x-for="ev in upcoming" :key="ev.id">
                        <li>
                            <button
                                type="button"
                                x-on:click="openEdit(ev)"
                                class="flex w-full items-start gap-3 rounded-lg border border-gray-100 px-3 py-2 text-left transition hover:bg-gray-50"
                            >
                                <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full" :class="dotClass(ev.color)"></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-medium text-gray-700" x-text="ev.title"></span>
                                    <span class="mt-0.5 block text-xs text-gray-400">
                                        <span x-text="prettyDate(ev.date)"></span>
                                        <template x-if="ev.time">
                                            <span><i class="ik ik-clock mx-1 text-[10px]"></i><span x-text="ev.time"></span></span>
                                        </template>
                                    </span>
                                </span>
                            </button>
                        </li>
                    </template>
                </ul>
            </div>
        </x-card>

        <!-- Calendar -->
        <x-card class="xl:col-span-3" no-padding>
            <!-- Calendar toolbar -->
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                <h5 class="font-semibold text-gray-700" x-text="monthLabel"></h5>
                <div class="flex items-center gap-2">
                    <x-button variant="outline" size="sm" x-on:click="goToday()">{{ __('Today') }}</x-button>
                    <div class="flex items-center gap-1">
                        <x-button variant="ghost" size="sm" x-on:click="prevMonth()" aria-label="{{ __('Previous month') }}">
                            <i class="ik ik-chevron-left"></i>
                        </x-button>
                        <x-button variant="ghost" size="sm" x-on:click="nextMonth()" aria-label="{{ __('Next month') }}">
                            <i class="ik ik-chevron-right"></i>
                        </x-button>
                    </div>
                </div>
            </div>

            <div class="p-5">
                <!-- Weekday header -->
                <div class="grid grid-cols-7">
                    @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
                        <div class="px-2 py-2 text-center text-xs uppercase tracking-wide text-gray-400">{{ __($day) }}</div>
                    @endforeach
                </div>

                <!-- Month grid -->
                <div class="grid grid-cols-7 overflow-hidden rounded-lg border border-gray-100">
                    <template x-for="(cell, idx) in days" :key="idx">
                        <div
                            class="group relative min-h-24 border-b border-r border-gray-100 p-1.5 text-sm transition"
                            :class="[
                                (idx % 7 === 6) ? 'border-r-0' : '',
                                cell ? 'cursor-pointer hover:bg-gray-50' : 'bg-gray-50/40',
                            ]"
                            x-on:click="cell && openCreate(cell.date)"
                        >
                            <template x-if="cell">
                                <div>
                                    <div class="flex items-center justify-between">
                                        <span
                                            class="inline-flex h-6 w-6 items-center justify-center rounded-full text-xs"
                                            :class="cell.date === todayStr
                                                ? 'bg-primary-500 font-semibold text-white'
                                                : 'text-gray-600'"
                                            x-text="cell.day"
                                        ></span>
                                        <i class="ik ik-plus text-[10px] text-gray-300 opacity-0 transition group-hover:opacity-100"></i>
                                    </div>

                                    <!-- Event chips -->
                                    <div class="mt-1 space-y-1">
                                        <template x-for="ev in eventsFor(cell.date).slice(0, 3)" :key="ev.id">
                                            <button
                                                type="button"
                                                x-on:click.stop="openEdit(ev)"
                                                class="flex w-full items-center gap-1 truncate rounded px-1.5 py-0.5 text-left text-xs transition hover:opacity-80"
                                                :class="chipClass(ev.color)"
                                                :title="ev.title + (ev.time ? ' · ' + ev.time : '')"
                                            >
                                                <template x-if="ev.time">
                                                    <span class="shrink-0 font-medium" x-text="ev.time"></span>
                                                </template>
                                                <span class="truncate" x-text="ev.title"></span>
                                            </button>
                                        </template>
                                        <template x-if="eventsFor(cell.date).length > 3">
                                            <button
                                                type="button"
                                                x-on:click.stop="openCreate(cell.date)"
                                                class="block w-full px-1.5 text-left text-[11px] font-medium text-gray-400 hover:text-gray-600"
                                                x-text="'+' + (eventsFor(cell.date).length - 3) + ' {{ __('more') }}'"
                                            ></button>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </x-card>

        <!-- Event modal (create / edit) -->
        <div
            x-show="modalOpen"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            style="display:none;"
        >
            <div
                x-show="modalOpen"
                x-transition.opacity
                class="absolute inset-0 bg-black/40"
                x-on:click="closeModal()"
            ></div>
            <div
                x-show="modalOpen"
                x-transition
                x-trap.noscroll="modalOpen"
                class="relative w-full max-w-md rounded-xl border border-gray-100 bg-white shadow-lg"
            >
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <h5 class="font-semibold text-gray-700" x-text="editing ? '{{ __('Edit Event') }}' : '{{ __('Add New Event') }}'"></h5>
                    <button type="button" class="text-gray-400 hover:text-gray-600" x-on:click="closeModal()" aria-label="{{ __('Close') }}">
                        <i class="ik ik-x"></i>
                    </button>
                </div>

                <form x-on:submit.prevent="save()">
                    <div class="space-y-4 px-5 py-4">
                        <div>
                            <label for="evTitle" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Title') }}</label>
                            <input
                                type="text" id="evTitle" x-model="form.title" required
                                x-ref="titleInput"
                                placeholder="{{ __('Enter event title') }}"
                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200"
                            >
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="evDate" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Date') }}</label>
                                <input
                                    type="date" id="evDate" x-model="form.date" required
                                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200"
                                >
                            </div>
                            <div>
                                <label for="evTime" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Time') }} <span class="text-gray-400">({{ __('optional') }})</span></label>
                                <input
                                    type="time" id="evTime" x-model="form.time"
                                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200"
                                >
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Color') }}</label>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="c in palette" :key="c.key">
                                    <button
                                        type="button"
                                        x-on:click="form.color = c.key"
                                        class="h-7 w-7 rounded-full ring-offset-2 transition hover:ring-2 hover:ring-gray-300"
                                        :class="[c.swatch, form.color === c.key ? 'ring-2 ring-gray-600' : '']"
                                        :aria-label="c.key"
                                    ></button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-2 border-t border-gray-100 px-5 py-4">
                        <div>
                            <template x-if="editing">
                                <x-button variant="danger" type="button" x-on:click="remove()">
                                    <i class="ik ik-trash-2"></i>{{ __('Delete') }}
                                </x-button>
                            </template>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-button variant="secondary" type="button" x-on:click="closeModal()">{{ __('Cancel') }}</x-button>
                            <x-button variant="primary" type="submit">{{ __('Save') }}</x-button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('script')
        <script>
            function calendar() {
                const today = new Date();
                const pad = (n) => String(n).padStart(2, '0');
                const fmt = (y, m, d) => `${y}-${pad(m + 1)}-${pad(d)}`;
                const todayStr = fmt(today.getFullYear(), today.getMonth(), today.getDate());

                return {
                    today,
                    todayStr,
                    year: today.getFullYear(),
                    month: today.getMonth(), // 0-based
                    modalOpen: false,
                    editing: false,
                    palette: [
                        { key: 'primary', swatch: 'bg-primary-500' },
                        { key: 'green',   swatch: 'bg-green-500' },
                        { key: 'amber',   swatch: 'bg-amber-500' },
                        { key: 'accent',  swatch: 'bg-accent-500' },
                        { key: 'cyan',    swatch: 'bg-cyan-500' },
                    ],
                    form: { id: null, date: todayStr, title: '', time: '', color: 'primary' },
                    events: [],

                    init() {
                        const y = this.year, m = this.month;
                        // Seed a few sample events in the current month.
                        this.events = [
                            { id: this.uid(), date: fmt(y, m, 5),  title: @json(__('Team Meeting')), time: '10:00', color: 'primary' },
                            { id: this.uid(), date: fmt(y, m, 12), title: @json(__('Birthday Party')), time: '', color: 'green' },
                            { id: this.uid(), date: fmt(y, m, 20), title: @json(__('Project Deadline')), time: '17:00', color: 'accent' },
                            { id: this.uid(), date: fmt(y, m, 20), title: @json(__('Client Call')), time: '14:30', color: 'cyan' },
                            { id: this.uid(), date: fmt(y, m, 25), title: @json(__('Conference')), time: '09:00', color: 'amber' },
                        ];
                    },

                    uid() {
                        return Date.now().toString(36) + Math.random().toString(36).slice(2, 7);
                    },

                    get monthLabel() {
                        return new Date(this.year, this.month, 1)
                            .toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
                    },

                    // Array of cells; null for leading/trailing blanks, otherwise { day, date }.
                    get days() {
                        const lead = new Date(this.year, this.month, 1).getDay();
                        const count = new Date(this.year, this.month + 1, 0).getDate();
                        const cells = [];
                        for (let i = 0; i < lead; i++) cells.push(null);
                        for (let d = 1; d <= count; d++) {
                            cells.push({ day: d, date: fmt(this.year, this.month, d) });
                        }
                        while (cells.length % 7 !== 0) cells.push(null);
                        return cells;
                    },

                    get upcoming() {
                        return [...this.events]
                            .filter((e) => e.date >= this.todayStr)
                            .sort((a, b) => (a.date + (a.time || '99:99')).localeCompare(b.date + (b.time || '99:99')))
                            .slice(0, 6);
                    },

                    eventsFor(date) {
                        return this.events
                            .filter((e) => e.date === date)
                            .sort((a, b) => (a.time || '99:99').localeCompare(b.time || '99:99'));
                    },

                    chipClass(color) {
                        return ({
                            primary: 'bg-primary-100 text-primary-700',
                            green:   'bg-green-100 text-green-700',
                            amber:   'bg-amber-100 text-amber-700',
                            accent:  'bg-accent-500/15 text-accent-600',
                            cyan:    'bg-cyan-100 text-cyan-700',
                        })[color] || 'bg-gray-100 text-gray-700';
                    },

                    dotClass(color) {
                        return ({
                            primary: 'bg-primary-500',
                            green:   'bg-green-500',
                            amber:   'bg-amber-500',
                            accent:  'bg-accent-500',
                            cyan:    'bg-cyan-500',
                        })[color] || 'bg-gray-400';
                    },

                    prettyDate(date) {
                        const [y, m, d] = date.split('-').map(Number);
                        return new Date(y, m - 1, d)
                            .toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' });
                    },

                    prevMonth() {
                        if (this.month === 0) { this.month = 11; this.year--; }
                        else { this.month--; }
                    },

                    nextMonth() {
                        if (this.month === 11) { this.month = 0; this.year++; }
                        else { this.month++; }
                    },

                    goToday() {
                        this.year = this.today.getFullYear();
                        this.month = this.today.getMonth();
                    },

                    openCreate(date) {
                        this.editing = false;
                        this.form = { id: null, date: date || this.todayStr, title: '', time: '', color: 'primary' };
                        this.modalOpen = true;
                        this.$nextTick(() => this.$refs.titleInput?.focus());
                    },

                    openEdit(ev) {
                        this.editing = true;
                        this.form = { ...ev };
                        this.modalOpen = true;
                        this.$nextTick(() => this.$refs.titleInput?.focus());
                    },

                    closeModal() {
                        this.modalOpen = false;
                    },

                    save() {
                        if (!this.form.title.trim() || !this.form.date) return;
                        if (this.editing && this.form.id) {
                            const i = this.events.findIndex((e) => e.id === this.form.id);
                            if (i !== -1) this.events.splice(i, 1, { ...this.form });
                        } else {
                            this.events.push({ ...this.form, id: this.uid() });
                        }
                        this.closeModal();
                    },

                    remove() {
                        this.events = this.events.filter((e) => e.id !== this.form.id);
                        this.closeModal();
                    },
                };
            }
        </script>
    @endpush
@endsection
