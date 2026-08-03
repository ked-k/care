@extends('layouts.main')
@section('title', 'Form Components')
@section('content')
    <x-page-header title="{{ __('Components') }}" subtitle="{{ __('lorem ipsum dolor sit amet, consectetur adipisicing elit') }}" icon="ik ik-edit"
                   :breadcrumbs="['Home' => route('dashboard'), 'Forms' => null, 'Components' => null]" />

    @php
        $selectClass = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100';
    @endphp

    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
        <x-card>
            <x-slot:header>{{ __('Default form') }}</x-slot:header>
            <form class="space-y-4">
                <x-form.input name="exampleInputUsername1" label="{{ __('Username') }}" icon="ik ik-user" placeholder="Username" />
                <x-form.input name="exampleInputEmail1" type="email" label="{{ __('Email address') }}" icon="ik ik-mail" placeholder="Email" />
                <x-form.input name="exampleInputPassword1" type="password" label="{{ __('Password') }}" icon="ik ik-lock" placeholder="Password" />
                <x-form.input name="exampleInputConfirmPassword1" type="password" label="{{ __('Confirm Password') }}" icon="ik ik-lock" placeholder="Password" />
                <x-form.checkbox name="rememberMe1" type="radio" label="{{ __('Remember me') }}" />
                <div class="flex items-center gap-2">
                    <x-button type="submit">{{ __('Submit') }}</x-button>
                    <x-button variant="secondary">{{ __('Cancel') }}</x-button>
                </div>
            </form>
        </x-card>

        <x-card>
            <x-slot:header>{{ __('Horizontal Form') }}</x-slot:header>
            <form class="space-y-4">
                @foreach ([
                    ['exampleInputUsername2', __('Username'), 'text', 'Username', 'ik ik-user'],
                    ['exampleInputEmail2', __('Email'), 'email', 'Email', 'ik ik-mail'],
                    ['exampleInputMobile', __('Mobile'), 'text', 'Mobile number', 'ik ik-phone'],
                    ['exampleInputPassword2', __('Password'), 'password', 'Password', 'ik ik-lock'],
                    ['exampleInputConfirmPassword2', __('Re Password'), 'password', 'Password', 'ik ik-lock'],
                ] as [$id, $lbl, $type, $ph, $icon])
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-4 sm:items-center">
                        <label for="{{ $id }}" class="text-sm font-medium text-gray-700">{{ $lbl }}</label>
                        <div class="sm:col-span-3">
                            <x-form.input :name="$id" :type="$type" :placeholder="$ph" :icon="$icon" />
                        </div>
                    </div>
                @endforeach
                <x-form.checkbox name="rememberMe2" type="radio" label="{{ __('Remember me') }}" />
                <div class="flex items-center gap-2">
                    <x-button type="submit">{{ __('Submit') }}</x-button>
                    <x-button variant="secondary">{{ __('Cancel') }}</x-button>
                </div>
            </form>
        </x-card>
    </div>

    <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-2">
        <x-card>
            <x-slot:header>{{ __('Basic form elements') }}</x-slot:header>
            <form class="space-y-4">
                <x-form.input name="exampleInputName1" label="{{ __('Name') }}" icon="ik ik-user" placeholder="Name" />
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-form.input name="exampleInputEmail3" type="email" label="{{ __('Email address') }}" icon="ik ik-mail" placeholder="Email" />
                    <x-form.select name="exampleSelectGender" label="{{ __('Gender') }}">
                        <option>{{ __('Male') }}</option>
                        <option>{{ __('Female') }}</option>
                    </x-form.select>
                </div>
                <x-form.input name="exampleInputPassword4" type="password" label="{{ __('Password') }}" icon="ik ik-lock" placeholder="Password" />
                <x-form.input name="exampleInputFile1" type="file" label="{{ __('File upload') }}" />
                <x-form.textarea name="exampleTextarea1" label="{{ __('Textarea') }}" rows="4" />
                <div class="flex items-center gap-2">
                    <x-button type="submit">{{ __('Submit') }}</x-button>
                    <x-button variant="secondary">{{ __('Cancel') }}</x-button>
                </div>
            </form>
        </x-card>

        <div class="space-y-5">
            <x-card>
                <x-slot:header>{{ __('Input Sizes') }}</x-slot:header>
                <form class="space-y-3">
                    <input type="text" placeholder=".form-control-lg" class="w-full rounded-lg border border-gray-200 bg-white px-4 py-3 text-base outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                    <input type="text" placeholder=".form-control" class="{{ $selectClass }}">
                    <input type="text" placeholder=".form-control-sm" class="w-full rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-xs outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                </form>
            </x-card>
            <x-card>
                <x-slot:header>{{ __('Text-color') }}</x-slot:header>
                @php
                    $textColors = [
                        'primary' => 'text-primary-600 border-primary-300 focus:border-primary-500 focus:ring-primary-100',
                        'warning' => 'text-amber-600 border-amber-300 focus:border-amber-500 focus:ring-amber-100',
                        'default' => 'text-gray-600 border-gray-300 focus:border-gray-500 focus:ring-gray-100',
                        'danger' => 'text-accent-600 border-accent-300 focus:border-accent-500 focus:ring-accent-100',
                        'success' => 'text-green-600 border-green-300 focus:border-green-500 focus:ring-green-100',
                        'inverse' => 'text-gray-800 border-gray-400 focus:border-gray-700 focus:ring-gray-200',
                        'info' => 'text-cyan-600 border-cyan-300 focus:border-cyan-500 focus:ring-cyan-100',
                    ];
                @endphp
                <form class="space-y-3">
                    <input type="text" value="Primary text" class="w-full rounded-lg border bg-white px-3 py-2 text-sm outline-none focus:ring-2 text-primary-600 border-primary-300 focus:border-primary-500 focus:ring-primary-100">
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        @foreach ($textColors as $variant => $cls)
                            @continue($variant === 'primary')
                            <input type="text" value="{{ ucfirst($variant) }} text" class="w-full rounded-lg border bg-white px-3 py-2 text-sm outline-none focus:ring-2 {{ $cls }}">
                        @endforeach
                    </div>
                </form>
            </x-card>
        </div>

        <x-card>
            <x-slot:header>{{ __('Color Inputs') }}</x-slot:header>
            @php
                $colorSwatches = [
                    'primary' => ['#007bff', 'text-primary-600 border-primary-300 focus:border-primary-500 focus:ring-primary-100'],
                    'warning' => ['#f59e0b', 'text-amber-600 border-amber-300 focus:border-amber-500 focus:ring-amber-100'],
                    'default' => ['#6b7280', 'text-gray-600 border-gray-300 focus:border-gray-500 focus:ring-gray-100'],
                    'danger' => ['#eb525d', 'text-accent-600 border-accent-300 focus:border-accent-500 focus:ring-accent-100'],
                    'success' => ['#22c55e', 'text-green-600 border-green-300 focus:border-green-500 focus:ring-green-100'],
                    'inverse' => ['#1f2937', 'text-gray-800 border-gray-400 focus:border-gray-700 focus:ring-gray-200'],
                    'info' => ['#06b6d4', 'text-cyan-600 border-cyan-300 focus:border-cyan-500 focus:ring-cyan-100'],
                ];
            @endphp
            <form class="space-y-3">
                @foreach ($colorSwatches as $variant => [$hex, $cls])
                    <div class="flex items-center gap-3">
                        <input type="color" value="{{ $hex }}" aria-label="{{ ucfirst($variant) }} color"
                            class="h-10 w-12 shrink-0 cursor-pointer rounded-lg border border-gray-200 bg-white p-1">
                        <input type="text" value="{{ ucfirst($variant) }} ({{ $hex }})" readonly
                            class="w-full rounded-lg border bg-white px-3 py-2 text-sm outline-none focus:ring-2 {{ $cls }}">
                    </div>
                @endforeach
            </form>
        </x-card>

        <x-card>
            <x-slot:header>{{ __('Background-color') }}</x-slot:header>
            @php
                $bgColors = [
                    'primary' => 'bg-primary-500 text-white placeholder-white/70',
                    'warning' => 'bg-amber-500 text-white placeholder-white/70',
                    'default' => 'bg-gray-500 text-white placeholder-white/70',
                    'danger' => 'bg-accent-500 text-white placeholder-white/70',
                    'success' => 'bg-green-500 text-white placeholder-white/70',
                    'inverse' => 'bg-slate-800 text-white placeholder-white/70',
                    'info' => 'bg-cyan-500 text-white placeholder-white/70',
                ];
            @endphp
            <form class="space-y-3">
                <input type="text" placeholder="Primary background" class="w-full rounded-lg border-0 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary-200 bg-primary-500 text-white placeholder-white/70">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    @foreach ($bgColors as $variant => $cls)
                        @continue($variant === 'primary')
                        <input type="text" placeholder="{{ ucfirst($variant) }} background" class="w-full rounded-lg border-0 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-white/40 {{ $cls }}">
                    @endforeach
                </div>
            </form>
        </x-card>

        <x-card>
            <x-slot:header>{{ __('Inline forms') }}</x-slot:header>
            <form class="flex flex-wrap items-center gap-3">
                <input type="text" id="inlineFormInputName2" placeholder="Shanker Raj" class="{{ $selectClass }} w-auto">
                <div class="flex items-stretch">
                    <span class="inline-flex items-center rounded-l-lg border border-r-0 border-gray-200 bg-gray-50 px-3 text-sm text-gray-500">@</span>
                    <input type="text" id="inlineFormInputGroupUsername2" placeholder="Username" class="rounded-r-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                </div>
                <x-form.checkbox name="inlineRememberMe" label="{{ __('Remember Me') }}" :checked="true" />
                <x-button type="submit">{{ __('Submit') }}</x-button>
            </form>
        </x-card>

        <x-card>
            <x-slot:header>{{ __('Validation States') }}</x-slot:header>
            <form class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label for="exampleInputNameValid" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Name') }}</label>
                    <input type="text" id="exampleInputNameValid" placeholder="Name" class="w-full rounded-lg border border-green-400 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-green-200">
                </div>
                <div>
                    <label for="exampleInputEmailInvalid" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Email address') }}</label>
                    <input type="email" id="exampleInputEmailInvalid" placeholder="Email" class="w-full rounded-lg border border-accent-400 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-accent-400/30">
                </div>
            </form>
        </x-card>
    </div>

    <div class="mt-5 space-y-5">
        <x-card>
            <x-slot:header>{{ __('Input Grid') }}</x-slot:header>
            <form class="space-y-3">
                @php
                    $gridRows = [
                        [['1 of 12', 'sm:col-span-1'], ['11 of 12', 'sm:col-span-11']],
                        [['2 of 12', 'sm:col-span-2'], ['10 of 12', 'sm:col-span-10']],
                        [['3 of 12', 'sm:col-span-3'], ['9 of 12', 'sm:col-span-9']],
                        [['4 of 12', 'sm:col-span-4'], ['8 of 12', 'sm:col-span-8']],
                        [['6 of 12', 'sm:col-span-6'], ['6 of 12', 'sm:col-span-6']],
                        [['4 of 12', 'sm:col-span-4'], ['4 of 12', 'sm:col-span-4'], ['4 of 12', 'sm:col-span-4']],
                    ];
                @endphp
                @foreach ($gridRows as $row)
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-12">
                        @foreach ($row as [$ph, $span])
                            <input type="text" placeholder="{{ $ph }}" class="{{ $selectClass }} {{ $span }}">
                        @endforeach
                    </div>
                @endforeach
                <input type="text" placeholder="12 of 12" class="{{ $selectClass }}">
            </form>
        </x-card>

        <x-card>
            <x-slot:header>{{ __('Responsive Input Grid') }}</x-slot:header>
            <form class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-form.input name="gridFirstName" label="{{ __('First Name') }}" placeholder="First name" />
                <x-form.input name="gridLastName" label="{{ __('Last Name') }}" placeholder="Last name" />
                <x-form.input name="gridEmail" type="email" label="{{ __('Email') }}" placeholder="Email" />
                <x-form.input name="gridCity" label="{{ __('City') }}" placeholder="City" />
                <x-form.input name="gridState" label="{{ __('State') }}" placeholder="State" />
                <x-form.input name="gridZip" label="{{ __('Zip') }}" placeholder="Zip" />
            </form>
        </x-card>

        <x-card>
            <x-slot:header>{{ __('File Upload') }}</x-slot:header>
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">{{ __('Simple file input') }}</label>
                    <input type="file"
                        class="block w-full cursor-pointer rounded-lg border border-gray-200 bg-white text-sm text-gray-600 shadow-sm outline-none file:mr-3 file:rounded-lg file:border-0 file:bg-primary-50 file:px-4 file:py-2 file:text-primary-600 hover:file:bg-primary-100">
                    <p class="text-xs text-gray-400">{{ __('Choose any file from your device.') }}</p>
                </div>

                <div x-data="{
                        files: [],
                        dragging: false,
                        addFiles(list) {
                            this.files = Array.from(list).map(f => ({ name: f.name, size: f.size }));
                        },
                        formatSize(bytes) {
                            if (bytes < 1024) return bytes + ' B';
                            if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
                            return (bytes / 1048576).toFixed(1) + ' MB';
                        },
                    }">
                    <label class="mb-2 block text-sm font-medium text-gray-700">{{ __('Drag & drop upload') }}</label>
                    <div @click="$refs.file.click()"
                        @dragover.prevent="dragging = true"
                        @dragleave.prevent="dragging = false"
                        @drop.prevent="dragging = false; addFiles($event.dataTransfer.files)"
                        :class="dragging ? 'border-primary-500 bg-primary-50' : 'border-gray-300 bg-gray-50 hover:border-primary-400 hover:bg-primary-50/50'"
                        class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed px-6 py-8 text-center transition">
                        <i class="ik ik-upload-cloud text-3xl text-primary-500"></i>
                        <p class="mt-2 text-sm font-medium text-gray-700">{{ __('Drop files here or click to browse') }}</p>
                        <p class="mt-1 text-xs text-gray-400">{{ __('Multiple files supported') }}</p>
                        <input type="file" x-ref="file" multiple class="hidden" @change="addFiles($event.target.files)">
                    </div>
                    <ul class="mt-3 space-y-2" x-show="files.length" x-cloak>
                        <template x-for="(file, index) in files" :key="index">
                            <li class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm">
                                <span class="flex items-center gap-2 text-gray-700">
                                    <i class="ik ik-file text-gray-400"></i>
                                    <span x-text="file.name"></span>
                                </span>
                                <span class="text-xs text-gray-400" x-text="formatSize(file.size)"></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>
        </x-card>
    </div>
@endsection
