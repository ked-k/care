@extends('layouts.main')
@section('title', 'Form Picker')
@section('content')
    <x-page-header title="{{ __('Form Picker') }}" subtitle="{{ __('Self-contained date, time and color pickers built with Tailwind & Alpine.js') }}" icon="ik ik-terminal"
                   :breadcrumbs="['Home' => route('dashboard'), 'Forms' => null, 'Form Picker' => null]" />

    @php
        // Reusable Alpine calendar data object. Inlined on each instance via {!! $calendar(...) !!}
        // so every component gets its OWN independent state (no shared singleton).
        // $inline = true renders the grid always visible (no dropdown / no input wiring).
        $calendar = function (bool $inline = false) {
            $open = $inline ? 'open: true,' : 'open: false,';
            // Emit a real JS boolean literal — $inline must NOT be interpolated bare
            // inside the heredoc (PHP casts false to '' and breaks the JS).
            $keepOpen = $inline ? 'true' : 'false';
            return <<<JS
{
    $open
    selected: '',
    today: new Date(),
    view: new Date(),
    months: ['January','February','March','April','May','June','July','August','September','October','November','December'],
    weekdays: ['Su','Mo','Tu','We','Th','Fr','Sa'],
    get label() { return this.months[this.view.getMonth()] + ' ' + this.view.getFullYear(); },
    get days() {
        const year = this.view.getFullYear(), month = this.view.getMonth();
        const firstDay = new Date(year, month, 1).getDay();
        const total = new Date(year, month + 1, 0).getDate();
        const cells = [];
        for (let i = 0; i < firstDay; i++) cells.push(null);
        for (let d = 1; d <= total; d++) cells.push(d);
        return cells;
    },
    pad(n) { return String(n).padStart(2, '0'); },
    iso(day) {
        return this.view.getFullYear() + '-' + this.pad(this.view.getMonth() + 1) + '-' + this.pad(day);
    },
    isToday(day) {
        return day === this.today.getDate() &&
            this.view.getMonth() === this.today.getMonth() &&
            this.view.getFullYear() === this.today.getFullYear();
    },
    isSelected(day) { return this.selected === this.iso(day); },
    prevMonth() { this.view = new Date(this.view.getFullYear(), this.view.getMonth() - 1, 1); },
    nextMonth() { this.view = new Date(this.view.getFullYear(), this.view.getMonth() + 1, 1); },
    pick(day) {
        if (!day) return;
        this.selected = this.iso(day);
        this.open = $keepOpen;
    },
}
JS;
        };
    @endphp

    {{-- ===================== ALPINE CALENDAR DATE PICKER ===================== --}}
    <x-card>
        <x-slot:header>{{ __('Alpine.js Date-Picker') }}</x-slot:header>
        <p class="mb-4 text-sm text-gray-500">{{ __('A self-contained calendar dropdown — no external libraries. Click the field to open, navigate months and pick a day.') }}</p>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div x-data="{!! $calendar() !!}" class="relative">
                <label class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Select a date') }}</label>
                <div class="relative">
                    <i class="ik ik-calendar pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" readonly x-model="selected" @click="open = !open"
                        placeholder="YYYY-MM-DD"
                        class="w-full cursor-pointer rounded-lg border border-gray-200 bg-white py-2.5 pl-10 pr-3 text-sm text-gray-700 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-100">
                </div>

                <div x-show="open" x-transition @click.outside="open = false" x-cloak
                    class="absolute z-30 mt-2 w-72 rounded-xl border border-gray-100 bg-white p-3 shadow-lg shadow-gray-200/70">
                    <div class="mb-2 flex items-center justify-between">
                        <button type="button" @click="prevMonth()" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100"><i class="ik ik-chevron-left"></i></button>
                        <span class="text-sm font-semibold text-gray-700" x-text="label"></span>
                        <button type="button" @click="nextMonth()" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100"><i class="ik ik-chevron-right"></i></button>
                    </div>
                    <div class="mb-1 grid grid-cols-7 gap-1 text-center text-xs font-medium text-gray-400">
                        <template x-for="wd in weekdays" :key="wd"><span x-text="wd"></span></template>
                    </div>
                    <div class="grid grid-cols-7 gap-1 text-center text-sm">
                        <template x-for="(day, idx) in days" :key="idx">
                            <button type="button" @click="pick(day)" :disabled="!day"
                                class="flex h-8 items-center justify-center rounded-lg"
                                :class="{
                                    'invisible': !day,
                                    'bg-primary-500 text-white font-semibold': day && isSelected(day),
                                    'ring-1 ring-primary-300 text-primary-600': day && isToday(day) && !isSelected(day),
                                    'text-gray-700 hover:bg-gray-100': day && !isSelected(day),
                                }">
                                <span x-text="day"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <div class="rounded-lg bg-gray-50 p-4 text-sm text-gray-500">
                <p class="mb-1 font-medium text-gray-700">{{ __('How it works') }}</p>
                <p>{{ __('The month math (leading blanks, day count, today/selected highlight, prev/next navigation) is implemented entirely in the Alpine x-data. The chosen day fills the input as') }} <code class="rounded bg-gray-200 px-1">YYYY-MM-DD</code>.</p>
            </div>
        </div>
    </x-card>

    {{-- ===================== NATIVE DATE/TIME TYPES ===================== --}}
    <div class="mt-5">
        <x-card>
            <x-slot:header>{{ __('Native Date & Time Inputs') }}</x-slot:header>
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                @foreach ([
                    [__('Date'), 'date', '&lt;input type="date"&gt;'],
                    [__('Month'), 'month', '&lt;input type="month"&gt;'],
                    [__('Week'), 'week', '&lt;input type="week"&gt;'],
                    [__('Date-time-local'), 'datetime-local', '&lt;input type="datetime-local"&gt;'],
                    [__('Time'), 'time', '&lt;input type="time"&gt;'],
                ] as [$lbl, $type, $code])
                    <div>
                        <h4 class="mb-1 font-semibold text-gray-700">{{ $lbl }}</h4>
                        <p class="mb-2 text-sm text-gray-500">{{ __('Add type') }} <code>{!! $code !!}</code></p>
                        <x-form.input name="picker-{{ $type }}" :type="$type" />
                    </div>
                @endforeach
            </div>
        </x-card>
    </div>

    {{-- ===================== INLINE CALENDAR + TIME PICKER ===================== --}}
    <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-2">
        {{-- Inline (always-visible) calendar --}}
        <x-card>
            <x-slot:header>{{ __('Inline Datepicker') }}</x-slot:header>
            <div x-data="{!! $calendar(true) !!}" class="mx-auto w-full max-w-xs">
                <div class="mb-3 flex items-center justify-between">
                    <button type="button" @click="prevMonth()" class="flex h-9 w-9 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100"><i class="ik ik-chevron-left"></i></button>
                    <span class="text-sm font-semibold text-gray-700" x-text="label"></span>
                    <button type="button" @click="nextMonth()" class="flex h-9 w-9 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100"><i class="ik ik-chevron-right"></i></button>
                </div>
                <div class="mb-1 grid grid-cols-7 gap-1 text-center text-xs font-medium text-gray-400">
                    <template x-for="wd in weekdays" :key="wd"><span x-text="wd"></span></template>
                </div>
                <div class="grid grid-cols-7 gap-1 text-center text-sm">
                    <template x-for="(day, idx) in days" :key="idx">
                        <button type="button" @click="pick(day)" :disabled="!day"
                            class="flex h-9 items-center justify-center rounded-lg"
                            :class="{
                                'invisible': !day,
                                'bg-primary-500 text-white font-semibold': day && isSelected(day),
                                'ring-1 ring-primary-300 text-primary-600': day && isToday(day) && !isSelected(day),
                                'text-gray-700 hover:bg-gray-100': day && !isSelected(day),
                            }">
                            <span x-text="day"></span>
                        </button>
                    </template>
                </div>
                <p class="mt-3 text-center text-sm text-gray-500">
                    {{ __('Selected:') }} <span class="font-medium text-gray-700" x-text="selected || '—'"></span>
                </p>
            </div>
        </x-card>

        <div class="space-y-5">
            {{-- Native time picker --}}
            <x-card>
                <x-slot:header>{{ __('Time Picker') }}</x-slot:header>
                <x-form.input name="timepicker" type="time" label="{{ __('Pick a time') }}" icon="ik ik-clock" />
            </x-card>

            {{-- Alpine time-slot dropdown --}}
            <x-card>
                <x-slot:header>{{ __('Time Slot Picker') }}</x-slot:header>
                <div x-data="{
                        open: false,
                        selected: '',
                        slots: (() => {
                            const out = [];
                            for (let h = 8; h <= 18; h++) {
                                for (const m of ['00', '30']) {
                                    out.push(String(h).padStart(2, '0') + ':' + m);
                                }
                            }
                            return out;
                        })(),
                    }" class="relative">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Choose a slot') }}</label>
                    <div class="relative">
                        <i class="ik ik-clock pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" readonly x-model="selected" @click="open = !open"
                            placeholder="{{ __('Select a time slot') }}"
                            class="w-full cursor-pointer rounded-lg border border-gray-200 bg-white py-2.5 pl-10 pr-3 text-sm text-gray-700 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-100">
                    </div>
                    <div x-show="open" x-transition @click.outside="open = false" x-cloak
                        class="absolute z-30 mt-2 max-h-56 w-full overflow-y-auto rounded-xl border border-gray-100 bg-white p-2 shadow-lg shadow-gray-200/70">
                        <template x-for="slot in slots" :key="slot">
                            <button type="button" @click="selected = slot; open = false"
                                class="block w-full rounded-lg px-3 py-1.5 text-left text-sm"
                                :class="selected === slot ? 'bg-primary-500 text-white' : 'text-gray-700 hover:bg-gray-100'"
                                x-text="slot"></button>
                        </template>
                    </div>
                </div>
            </x-card>
        </div>
    </div>

    {{-- ===================== COLOR PICKERS ===================== --}}
    <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-2">
        {{-- Native color inputs with live hex --}}
        <x-card>
            <x-slot:header>{{ __('Native Color Picker') }}</x-slot:header>
            <p class="mb-4 text-sm text-gray-500">{{ __('A native color input bound to a hex field via Alpine x-model.') }}</p>
            <div class="space-y-4">
                @foreach ([
                    [__('Primary'), '#007bff'],
                    [__('Accent'), '#dc3545'],
                    [__('Success'), '#28a745'],
                    [__('Amber'), '#f59e0b'],
                ] as $idx => [$lbl, $val])
                    <div x-data="{ color: '{{ $val }}' }" class="flex items-center gap-4">
                        <input type="color" x-model="color"
                            class="h-12 w-12 shrink-0 cursor-pointer rounded-lg border border-gray-200 bg-white p-1">
                        <div class="flex-1">
                            <label class="mb-1 block text-sm font-medium text-gray-700">{{ $lbl }}</label>
                            <input type="text" x-model="color"
                                class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm uppercase text-gray-700 shadow-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-100">
                        </div>
                        <div class="h-10 w-10 shrink-0 rounded-lg border border-gray-200" :style="`background:${color}`"></div>
                    </div>
                @endforeach
            </div>
        </x-card>

        {{-- Swatch palette picker --}}
        <x-card>
            <x-slot:header>{{ __('Swatch Palette Picker') }}</x-slot:header>
            <p class="mb-4 text-sm text-gray-500">{{ __('Click a swatch to select it. The ring marks the active color and the preview updates instantly.') }}</p>
            <div x-data="{
                    swatches: ['#007bff','#dc3545','#28a745','#f59e0b','#06b6d4','#6f42c1','#e83e8c','#20c997','#fd7e14','#6610f2','#343a40','#adb5bd'],
                    selected: '#007bff',
                }">
                <div class="grid grid-cols-6 gap-3">
                    <template x-for="swatch in swatches" :key="swatch">
                        <button type="button" @click="selected = swatch"
                            class="h-10 w-full rounded-lg border border-gray-200 transition"
                            :class="selected === swatch ? 'ring-2 ring-offset-2 ring-gray-400' : 'hover:scale-105'"
                            :style="`background:${swatch}`"></button>
                    </template>
                </div>

                <div class="mt-5 flex items-center gap-4 rounded-lg bg-gray-50 p-4">
                    <div class="h-14 w-14 shrink-0 rounded-lg border border-gray-200 shadow-sm" :style="`background:${selected}`"></div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-400">{{ __('Selected color') }}</p>
                        <p class="text-lg font-semibold uppercase text-gray-700" x-text="selected"></p>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Hex value') }}</label>
                    <input type="text" x-model="selected"
                        class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm uppercase text-gray-700 shadow-sm outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-100">
                </div>
            </div>
        </x-card>
    </div>
@endsection
