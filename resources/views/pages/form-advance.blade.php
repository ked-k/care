@extends('layouts.main')
@section('title', 'Form Advance')
@section('content')
    <x-page-header title="{{ __('Advance') }}" subtitle="{{ __('lorem ipsum dolor sit amet, consectetur adipisicing elit') }}" icon="ik ik-edit"
                   :breadcrumbs="['Home' => route('dashboard'), 'Forms' => null, 'Advance' => null]" />

    @php
        $selectClass = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100';
        $checkClass = 'h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-200';
        $radioClass = 'h-4 w-4 border-gray-300 text-primary-600 focus:ring-primary-200';
    @endphp

    <x-card>
        <x-slot:header>{{ __('Switches') }}</x-slot:header>
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <div>
                <h4 class="mb-3 font-semibold text-gray-700">{{ __('Single Switche') }}</h4>
                <x-form.toggle name="singleSwitch" :checked="true" />
            </div>
            <div>
                <h4 class="mb-3 font-semibold text-gray-700">{{ __('Multiple Switches') }}</h4>
                <div class="flex gap-2">
                    <x-form.toggle name="multiSwitch1" :checked="true" />
                    <x-form.toggle name="multiSwitch2" :checked="true" />
                    <x-form.toggle name="multiSwitch3" :checked="true" />
                </div>
            </div>
            <div>
                <h4 class="mb-3 font-semibold text-gray-700">{{ __('Enable Disable Switches') }}</h4>
                <div class="flex items-center gap-2">
                    <x-form.toggle name="enableSwitch" :checked="true" />
                    <x-button size="sm">{{ __('Enable') }}</x-button>
                    <x-button size="sm" variant="secondary">{{ __('Disable') }}</x-button>
                </div>
            </div>
        </div>
        <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-3">
            <div class="md:col-span-2">
                <h4 class="mb-3 font-semibold text-gray-700">{{ __('Color Switches') }}</h4>
                <div class="flex flex-wrap gap-4">
                    @foreach (['primary', 'green', 'accent', 'amber', 'primary', 'green', 'accent'] as $idx => $color)
                        <x-form.toggle name="colorSwitch{{ $idx }}" :color="$color" :checked="true" />
                    @endforeach
                </div>
            </div>
            <div>
                <h4 class="mb-3 font-semibold text-gray-700">{{ __('Switch Sizes') }}</h4>
                <div class="flex flex-wrap items-center gap-4">
                    @for ($i = 0; $i < 3; $i++)<x-form.toggle name="sizeSwitch{{ $i }}" :checked="true" />@endfor
                </div>
            </div>
        </div>
    </x-card>

    <div class="mt-5">
        <x-card>
            <x-slot:header>{{ __('Radio') }}</x-slot:header>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                {{-- Radio Fill Button: solid filled native radios --}}
                <div>
                    <h4 class="mb-3 font-semibold text-gray-700">{{ __('Radio Fill Button') }}</h4>
                    <div class="space-y-2.5">
                        @foreach ([__('Radio 1'), __('Radio 2'), __('Radio Disable')] as $idx => $opt)
                            <label class="flex items-center gap-2.5 text-sm text-gray-600 has-[:disabled]:cursor-not-allowed has-[:disabled]:opacity-50">
                                <input type="radio" name="radio-fill" value="{{ $idx }}" @checked($idx === 0) @disabled($idx === 2)
                                       class="h-4 w-4 accent-primary-500">
                                {{ $opt }}
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Radio Outline Button: hollow ring that fills with a colored dot --}}
                <div>
                    <h4 class="mb-3 font-semibold text-gray-700">{{ __('Radio Outline Button') }}</h4>
                    <div class="space-y-2.5">
                        @foreach ([__('Radio 1'), __('Radio 2'), __('Radio Disable')] as $idx => $opt)
                            <label class="flex cursor-pointer items-center gap-2.5 text-sm text-gray-600 has-[:disabled]:cursor-not-allowed has-[:disabled]:opacity-50">
                                <span class="relative flex h-[18px] w-[18px] items-center justify-center rounded-full border-2 border-gray-300 transition has-[:checked]:border-primary-500">
                                    <input type="radio" name="radio-outline" value="{{ $idx }}" @checked($idx === 0) @disabled($idx === 2) class="peer sr-only">
                                    <span class="h-2 w-2 scale-0 rounded-full bg-primary-500 transition-transform peer-checked:scale-100"></span>
                                </span>
                                {{ $opt }}
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Radio Button: segmented pill button group --}}
                <div>
                    <h4 class="mb-3 font-semibold text-gray-700">{{ __('Radio Button') }}</h4>
                    <div x-data="{ choice: '0' }" class="inline-flex rounded-lg border border-gray-200 bg-gray-50 p-1">
                        @foreach ([__('Daily'), __('Weekly'), __('Monthly')] as $idx => $opt)
                            <label :class="choice === '{{ $idx }}' ? 'bg-primary-500 text-white shadow-sm' : 'text-gray-600 hover:text-gray-800'"
                                   class="cursor-pointer rounded-md px-4 py-1.5 text-sm font-medium transition">
                                <input type="radio" name="radio-btn" value="{{ $idx }}" x-model="choice" class="sr-only">{{ $opt }}
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            @php
                // Full class strings kept literal so Tailwind's source scanner generates them.
                $radioColors = [
                    ['Default Color', 'accent-gray-500', 'has-[:checked]:border-gray-400', 'bg-gray-500'],
                    ['Primary Color', 'accent-primary-500', 'has-[:checked]:border-primary-500', 'bg-primary-500'],
                    ['Success Color', 'accent-green-500', 'has-[:checked]:border-green-500', 'bg-green-500'],
                    ['Info Color', 'accent-cyan-500', 'has-[:checked]:border-cyan-500', 'bg-cyan-500'],
                    ['Warning Color', 'accent-amber-500', 'has-[:checked]:border-amber-500', 'bg-amber-500'],
                    ['Danger Color', 'accent-accent-500', 'has-[:checked]:border-accent-500', 'bg-accent-500'],
                    ['Inverse Color', 'accent-slate-800', 'has-[:checked]:border-slate-800', 'bg-slate-800'],
                ];
            @endphp

            {{-- Color Radio Button: native radios tinted per colour via accent-* --}}
            <h4 class="mb-3 mt-6 font-semibold text-gray-700">{{ __('Color Radio Button') }}</h4>
            <div class="flex flex-wrap gap-x-5 gap-y-3">
                @foreach ($radioColors as $idx => [$label, $accent, $border, $dot])
                    <label class="flex items-center gap-2.5 text-sm text-gray-600">
                        <input type="radio" name="radio-color" value="{{ $idx }}" @checked($idx === 1) class="h-4 w-4 {{ $accent }}">
                        {{ __($label) }}
                    </label>
                @endforeach
            </div>

            {{-- Color Radio Material Button: custom outline + coloured fill dot per colour --}}
            <h4 class="mb-3 mt-6 font-semibold text-gray-700">{{ __('Color Radio material Button') }}</h4>
            <div class="flex flex-wrap gap-x-5 gap-y-3">
                @foreach ($radioColors as $idx => [$label, $accent, $border, $dot])
                    <label class="flex cursor-pointer items-center gap-2.5 text-sm text-gray-600">
                        <span class="relative flex h-5 w-5 items-center justify-center rounded-full border-2 border-gray-300 transition {{ $border }}">
                            <input type="radio" name="radio-material" value="{{ $idx }}" @checked($idx === 1) class="peer sr-only">
                            <span class="h-2.5 w-2.5 scale-0 rounded-full {{ $dot }} transition-transform peer-checked:scale-100"></span>
                        </span>
                        {{ __($label) }}
                    </label>
                @endforeach
            </div>

            <h4 class="mb-3 mt-6 font-semibold text-gray-700">{{ __('Segmented Card Radio') }}</h4>
            <div x-data="{ plan: 'standard' }" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                @foreach ([
                    ['basic', __('Basic'), __('For individuals'), 'ik ik-user'],
                    ['standard', __('Standard'), __('For small teams'), 'ik ik-users'],
                    ['premium', __('Premium'), __('For organizations'), 'ik ik-award'],
                ] as [$value, $title, $desc, $icon])
                    <label
                        :class="plan === '{{ $value }}'
                            ? 'border-primary-500 ring-2 ring-primary-100 bg-primary-50'
                            : 'border-gray-200 hover:border-gray-300'"
                        class="relative flex cursor-pointer flex-col gap-1 rounded-lg border bg-white p-4 transition">
                        <input type="radio" name="radio-card" value="{{ $value }}" x-model="plan" class="sr-only">
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-2 font-semibold text-gray-700">
                                <i class="{{ $icon }} text-primary-500"></i>{{ $title }}
                            </span>
                            <i class="ik ik-check-circle text-primary-500"
                               x-show="plan === '{{ $value }}'" x-cloak></i>
                        </div>
                        <span class="text-xs text-gray-500">{{ $desc }}</span>
                    </label>
                @endforeach
            </div>
        </x-card>
    </div>

    <div class="mt-5">
        <x-card>
            <x-slot:header>{{ __('Checkbox') }}</x-slot:header>
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                <div>
                    <h4 class="mb-3 font-semibold text-gray-700">{{ __('Border Checkbox') }}</h4>
                    <div class="space-y-2">
                        @foreach ([__('Do you like it?'), __('Primary'), __('Success'), __('Info'), __('Warning'), __('Danger'), __('Disabled')] as $idx => $lbl)
                            <x-form.checkbox name="borderCheck{{ $idx }}" :label="$lbl" @disabled($idx === 6) />
                        @endforeach
                    </div>
                </div>
                <div>
                    <h4 class="mb-3 font-semibold text-gray-700">{{ __('Fade-in Checkbox') }}</h4>
                    <div class="space-y-2">
                        @foreach ([__('Default'), __('Primary'), __('Warning'), __('Success'), __('Info'), __('Danger'), __('Disabled')] as $idx => $lbl)
                            <x-form.checkbox name="fadeCheck{{ $idx }}" :label="$lbl" @disabled($idx === 6) />
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
                <div>
                    <h4 class="mb-3 font-semibold text-gray-700">{{ __('Color Checkbox') }}</h4>
                    <div class="space-y-2">
                        @foreach ([__('Default'), __('Primary'), __('Success'), __('Info'), __('Warning'), __('Danger'), __('Disabled')] as $idx => $lbl)
                            <x-form.checkbox name="colorCheck{{ $idx }}" :label="$lbl" :checked="$idx !== 6" @disabled($idx === 6) />
                        @endforeach
                    </div>
                </div>
                <div>
                    <h4 class="mb-3 font-semibold text-gray-700">{{ __('zoom Checkbox') }}</h4>
                    <div class="space-y-2">
                        @foreach ([__('Default'), __('Primary'), __('Warning'), __('Success'), __('Info'), __('Danger'), __('Disabled')] as $idx => $lbl)
                            <x-form.checkbox name="zoomCheck{{ $idx }}" :label="$lbl" @disabled($idx === 6) />
                        @endforeach
                    </div>
                </div>
            </div>
        </x-card>
    </div>

    <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-2">
        <x-card>
            <x-slot:header>{{ __('Input Tag') }}</x-slot:header>
            <div x-data="{
                    tags: ['London', 'Canada', 'Australia', 'Mexico', 'India'],
                    draft: '',
                    add() {
                        const v = this.draft.trim().replace(/,+$/, '').trim();
                        if (v && ! this.tags.includes(v)) this.tags.push(v);
                        this.draft = '';
                    },
                    remove(i) { this.tags.splice(i, 1); },
                    backspace() { if (this.draft === '' && this.tags.length) this.tags.pop(); },
                }">
                <label class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Type to add a new tag') }}</label>
                <div @click="$refs.tagInput.focus()"
                     class="flex flex-wrap items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-sm shadow-sm transition focus-within:border-primary-500 focus-within:ring-2 focus-within:ring-primary-100">
                    <template x-for="(tag, i) in tags" :key="i">
                        <span class="inline-flex items-center gap-1 rounded-md bg-primary-50 px-2 py-0.5 text-xs font-medium text-primary-700">
                            <span x-text="tag"></span>
                            <button type="button" @click="remove(i)" class="text-primary-400 hover:text-accent-500"><i class="ik ik-x text-[14px]"></i></button>
                        </span>
                    </template>
                    <input x-ref="tagInput" x-model="draft" type="text"
                           @keydown.enter.prevent="add()" @keydown.comma.prevent="add()" @keydown.backspace="backspace()" @blur="add()"
                           placeholder="{{ __('Add a tag...') }}"
                           class="min-w-[8rem] flex-1 border-0 bg-transparent px-1 py-0.5 text-sm outline-none placeholder:text-gray-400">
                </div>
                {{-- Hidden inputs so the tags submit as tags[] --}}
                <template x-for="(tag, i) in tags" :key="'h' + i"><input type="hidden" name="tags[]" :value="tag"></template>
                <p class="mt-1 text-xs text-gray-400">{{ __('Press Enter or comma to add, Backspace to remove') }}</p>
            </div>
        </x-card>
        <x-card>
            <x-slot:header>{{ __('Form Repeater') }}</x-slot:header>
            <p class="mb-3 text-sm text-gray-500">{{ __('Click the add button to repeat the form') }}</p>
            @php
                $repeaterField = 'w-full rounded-lg border border-gray-200 bg-white py-2.5 pl-10 pr-3 text-sm text-gray-700 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-100';
            @endphp
            <div x-data="{
                    rows: [{ name: '', email: '', phone: '' }],
                    add() { this.rows.push({ name: '', email: '', phone: '' }); },
                    remove(i) { if (this.rows.length > 1) this.rows.splice(i, 1); },
                }" class="space-y-3">
                <template x-for="(row, i) in rows" :key="i">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="relative min-w-[8rem] flex-1">
                            <i class="ik ik-user pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input x-model="row.name" name="repeaterName[]" type="text" placeholder="{{ __('Name') }}" class="{{ $repeaterField }}">
                        </div>
                        <div class="relative min-w-[8rem] flex-1">
                            <i class="ik ik-mail pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input x-model="row.email" name="repeaterEmail[]" type="email" placeholder="{{ __('Email') }}" class="{{ $repeaterField }}">
                        </div>
                        <div class="relative min-w-[8rem] flex-1">
                            <i class="ik ik-phone pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input x-model="row.phone" name="repeaterPhone[]" type="tel" placeholder="{{ __('Phone No') }}" class="{{ $repeaterField }}">
                        </div>
                        <button type="button" @click="remove(i)" :disabled="rows.length === 1"
                                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-accent-500 text-white transition hover:bg-accent-600 disabled:cursor-not-allowed disabled:opacity-50">
                            <i class="ik ik-trash-2"></i>
                        </button>
                    </div>
                </template>
                <button type="button" @click="add()"
                        class="inline-flex items-center gap-2 rounded-lg bg-green-500 px-3 py-2 text-sm font-medium text-white transition hover:bg-green-600">
                    <i class="ik ik-plus"></i> {{ __('Add Row') }}
                </button>
            </div>
        </x-card>
    </div>

    <div class="mt-5">
        <x-card>
            <x-slot:header>{{ __('Select') }}</x-slot:header>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                {{-- Native single select --}}
                <x-form.select name="singleSelect" label="{{ __('Searchable select') }}">
                    <option value="cheese">{{ __('Cheese') }}</option>
                    <option value="tomatoes">{{ __('Tomatoes') }}</option>
                    <option value="mozarella">{{ __('Mozzarella') }}</option>
                    <option value="mushrooms">{{ __('Mushrooms') }}</option>
                    <option value="pepperoni">{{ __('Pepperoni') }}</option>
                    <option value="onions">{{ __('Onions') }}</option>
                </x-form.select>

                {{-- Alpine searchable multi-select with chips --}}
                <div x-data="{
                        open: false,
                        search: '',
                        options: [
                            { value: 'cheese', label: '{{ __('Cheese') }}' },
                            { value: 'tomatoes', label: '{{ __('Tomatoes') }}' },
                            { value: 'mozarella', label: '{{ __('Mozzarella') }}' },
                            { value: 'mushrooms', label: '{{ __('Mushrooms') }}' },
                            { value: 'pepperoni', label: '{{ __('Pepperoni') }}' },
                            { value: 'onions', label: '{{ __('Onions') }}' },
                        ],
                        selected: ['pepperoni'],
                        get filtered() {
                            return this.options.filter(o => o.label.toLowerCase().includes(this.search.toLowerCase()));
                        },
                        isSelected(value) { return this.selected.includes(value); },
                        toggle(value) {
                            this.isSelected(value)
                                ? this.selected = this.selected.filter(v => v !== value)
                                : this.selected.push(value);
                        },
                        remove(value) { this.selected = this.selected.filter(v => v !== value); },
                        labelFor(value) { return (this.options.find(o => o.value === value) || {}).label || value; },
                    }"
                    @click.outside="open = false"
                    class="relative">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Multi-select') }}</label>

                    {{-- Hidden inputs so the values submit as multiSelect[] --}}
                    <template x-for="value in selected" :key="value">
                        <input type="hidden" name="multiSelect[]" :value="value">
                    </template>

                    {{-- Control --}}
                    <div @click="open = ! open"
                        class="flex min-h-[42px] w-full cursor-pointer flex-wrap items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-sm shadow-sm transition focus-within:border-primary-500 focus-within:ring-2 focus-within:ring-primary-100"
                        :class="open && 'border-primary-500 ring-2 ring-primary-100'">
                        <template x-for="value in selected" :key="value">
                            <span class="inline-flex items-center gap-1 rounded-md bg-primary-50 px-2 py-0.5 text-xs font-medium text-primary-700">
                                <span x-text="labelFor(value)"></span>
                                <button type="button" @click.stop="remove(value)" class="text-primary-400 hover:text-accent-500">
                                    <i class="ik ik-x text-[14px]"></i>
                                </button>
                            </span>
                        </template>
                        <span x-show="selected.length === 0" class="px-1 text-gray-400">{{ __('Select options...') }}</span>
                        <i class="ik ik-chevron-down ml-auto text-gray-400 transition" :class="open && 'rotate-180'"></i>
                    </div>

                    {{-- Panel --}}
                    <div x-show="open" x-collapse x-cloak
                        class="absolute z-20 mt-1 w-full overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg">
                        <div class="border-b border-gray-100 p-2">
                            <input type="text" x-model="search" x-trap="open"
                                placeholder="{{ __('Search...') }}"
                                class="w-full rounded-md border border-gray-200 px-2.5 py-1.5 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                        </div>
                        <ul class="max-h-52 overflow-y-auto py-1">
                            <template x-for="option in filtered" :key="option.value">
                                <li @click="toggle(option.value)"
                                    class="flex cursor-pointer items-center gap-2.5 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50">
                                    <input type="checkbox" :checked="isSelected(option.value)" @click.stop="toggle(option.value)"
                                        class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-2 focus:ring-primary-200">
                                    <span x-text="option.label"></span>
                                </li>
                            </template>
                            <li x-show="filtered.length === 0" class="px-3 py-2 text-sm text-gray-400">{{ __('No results found') }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </x-card>
    </div>

    <div class="mt-5">
        <x-card>
            <x-slot:header>{{ __('Rich Text Editor') }}</x-slot:header>
            <div x-data="{
                    content: '<p>{{ __('Write your content here...') }}</p>',
                    exec(cmd, value = null) {
                        document.execCommand(cmd, false, value);
                        this.$refs.editor.focus();
                        this.sync();
                    },
                    link() {
                        const url = window.prompt('{{ __('Enter the URL') }}', 'https://');
                        if (url) this.exec('createLink', url);
                    },
                    sync() { this.content = this.$refs.editor.innerHTML; },
                }"
                x-init="$refs.editor.innerHTML = content">
                <div class="overflow-hidden rounded-lg border border-gray-200 shadow-sm focus-within:border-primary-500 focus-within:ring-2 focus-within:ring-primary-100">
                    {{-- Toolbar --}}
                    <div class="flex flex-wrap items-center gap-1 border-b border-gray-100 bg-gray-50 p-2">
                        @php
                            $rteBtn = 'flex h-8 w-8 items-center justify-center rounded-md text-gray-600 transition hover:bg-white hover:text-primary-600';
                        @endphp
                        <button type="button" class="{{ $rteBtn }}" @click="exec('bold')" title="{{ __('Bold') }}"><i class="ik ik-bold"></i></button>
                        <button type="button" class="{{ $rteBtn }}" @click="exec('italic')" title="{{ __('Italic') }}"><i class="ik ik-italic"></i></button>
                        <button type="button" class="{{ $rteBtn }}" @click="exec('underline')" title="{{ __('Underline') }}"><i class="ik ik-underline"></i></button>
                        <button type="button" class="{{ $rteBtn }}" @click="exec('strikeThrough')" title="{{ __('Strikethrough') }}"><i class="ik ik-minus"></i></button>
                        <span class="mx-1 h-5 w-px bg-gray-200"></span>
                        <button type="button" class="{{ $rteBtn }}" @click="exec('insertUnorderedList')" title="{{ __('Bullet list') }}"><i class="ik ik-list"></i></button>
                        <button type="button" class="{{ $rteBtn }}" @click="exec('insertOrderedList')" title="{{ __('Numbered list') }}"><i class="ik ik-align-left"></i></button>
                        <button type="button" class="{{ $rteBtn }}" @click="link()" title="{{ __('Insert link') }}"><i class="ik ik-link"></i></button>
                        <span class="mx-1 h-5 w-px bg-gray-200"></span>
                        <button type="button" class="{{ $rteBtn }}" @click="exec('removeFormat')" title="{{ __('Clear formatting') }}"><i class="ik ik-x-circle"></i></button>
                    </div>

                    {{-- Editable area --}}
                    <div x-ref="editor" contenteditable="true" @input="sync()"
                        class="prose max-w-none min-h-40 px-4 py-3 text-sm text-gray-700 outline-none"></div>
                </div>

                {{-- Hidden textarea so the content submits --}}
                <textarea name="editor" x-model="content" class="hidden"></textarea>
            </div>
        </x-card>
    </div>
@endsection
