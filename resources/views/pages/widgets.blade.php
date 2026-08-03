@extends('layouts.main')
@section('title', 'Widgets')
@section('content')
    <x-page-header title="{{ __('Widgets Basic') }}" subtitle="{{ __('lorem ipsum dolor sit amet, consectetur adipisicing elit') }}" icon="ik ik-layers" :breadcrumbs="['Home' => url('dashboard'), 'Widgets' => '#', 'Widgets Basic' => null]" />

    <!-- gradient stat widgets -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $topStats = [
                ['Works', '543', 'ik ik-box', 'primary', '+3.2%', true, [6,9,7,11,9,13]],
                ['Sales', '3510', 'ik ik-shopping-cart', 'green', '+8.7%', true, [8,11,9,14,12,16]],
                ['Earnings', '$43,567.53', 'ik ik-inbox', 'amber', '+12.1%', true, [10,13,11,16,14,19]],
                ['New Users', '11', 'ik ik-users', 'accent', '-0.8%', false, [14,12,13,11,12,10]],
            ];
        @endphp
        @foreach ($topStats as [$label, $value, $icon, $color, $trend, $up, $spark])
            <x-stat-card :value="$value" :label="__($label)" :icon="$icon" :color="$color"
                         :trend="$trend" :trendUp="$up" :spark="$spark" variant="gradient" />
        @endforeach
    </div>

    <!-- plain stat widgets -->
    <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $plainStats = [
                ['Server', '62%', 'ik ik-server'],
                ['Traffic', '45%', 'ik ik-trending-up'],
                ['Email', '32', 'ik ik-mail'],
                ['Domians', '11', 'ik ik-zap'],
            ];
        @endphp
        @foreach ($plainStats as [$label, $value, $icon])
            <x-card>
                <div class="flex items-center justify-between">
                    <div>
                        <h6 class="text-sm text-gray-500">{{ __($label) }}</h6>
                        <h2 class="text-2xl font-bold text-gray-700">{{ $value }}</h2>
                    </div>
                    <i class="{{ $icon }} text-3xl text-gray-300"></i>
                </div>
            </x-card>
        @endforeach
    </div>

    <!-- stat widgets with progress -->
    <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $progressStats = [
                ['Bookmarks', '1,410', 'ik ik-award', '6% higher than last month', 62, 'bg-accent-500'],
                ['Likes', '41,410', 'ik ik-thumbs-up', '61% higher than last month', 78, 'bg-green-500'],
                ['Events', '410', 'ik ik-calendar', 'Total Events', 31, 'bg-amber-500'],
                ['Comments', '41,410', 'ik ik-message-square', 'Total Comments', 20, 'bg-primary-500'],
            ];
        @endphp
        @foreach ($progressStats as [$label, $value, $icon, $note, $width, $barColor])
            <x-card no-padding>
                <div class="p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <h6 class="text-sm text-gray-500">{{ __($label) }}</h6>
                            <h2 class="text-2xl font-bold text-gray-700">{{ $value }}</h2>
                        </div>
                        <i class="{{ $icon }} text-3xl text-gray-300"></i>
                    </div>
                    <small class="mt-3 block text-xs text-gray-400">{{ $note }}</small>
                </div>
                <div class="h-1.5 bg-gray-100"><div class="h-full {{ $barColor }}" style="width: {{ $width }}%;"></div></div>
            </x-card>
        @endforeach
    </div>

    <!-- card group with progress -->
    <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $cardGroup = [
                ['234', 'New Orders', 'ik ik-shopping-cart', 'text-green-600', 83, 'bg-green-500'],
                ['3423', 'Pending Products', 'ik ik-briefcase', 'text-primary-600', 63, 'bg-primary-500'],
                ['$ 123423', 'Online Reveneue', 'ik ik-codepen', 'text-accent-600', 77, 'bg-accent-500'],
                ['$ 355323', 'Total Profits', 'ik ik-trending-up', 'text-primary-600', 56, 'bg-primary-500'],
            ];
        @endphp
        @foreach ($cardGroup as [$value, $label, $icon, $textClass, $width, $barColor])
            <x-card>
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-bold {{ $textClass }}">{{ $value }}</h3>
                        <p class="text-sm text-gray-400">{{ $label }}</p>
                    </div>
                    <i class="{{ $icon }} text-2xl text-gray-300"></i>
                </div>
                <div class="mb-1 mt-3 h-2 rounded-full bg-gray-100"><div class="h-full rounded-full {{ $barColor }}" style="width: {{ $width }}%;"></div></div>
                <div class="flex justify-between text-xs text-gray-400">
                    <span>{{ __('Progress') }}</span>
                    <span>{{ $width }}%</span>
                </div>
            </x-card>
        @endforeach
    </div>

    <!-- social widgets -->
    <div class="mt-5 grid grid-cols-2 gap-5 sm:grid-cols-3 xl:grid-cols-6">
        @php
            $socialWidgets = [
                ['fab fa-facebook', 'Like', '123', 'text-primary-600'],
                ['fab fa-instagram', 'Followers', '231', 'text-accent-500'],
                ['fab fa-twitter', 'Followers', '31', 'text-primary-500'],
                ['fab fa-google', 'Like', '254', 'text-accent-600'],
                ['fab fa-linkedin', 'Followers', '2510', 'text-primary-700'],
                ['fab fa-behance', 'Project', '121', 'text-primary-600'],
            ];
        @endphp
        @foreach ($socialWidgets as [$icon, $text, $number, $iconClass])
            <x-card>
                <div class="flex items-center gap-3">
                    <i class="{{ $icon }} text-2xl {{ $iconClass }}"></i>
                    <div>
                        <div class="text-xs text-gray-400">{{ $text }}</div>
                        <div class="text-lg font-bold text-gray-700">{{ $number }}</div>
                    </div>
                </div>
            </x-card>
        @endforeach
    </div>

    <!-- expandable / removable / overlay widgets -->
    <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <!-- Expandable / collapsible -->
        <div x-data="{ open: true }" class="rounded-2xl border border-gray-100 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h5 class="font-semibold text-gray-700">{{ __('Expandable') }}</h5>
                <button type="button" @click="open = !open" class="text-gray-400 transition hover:text-primary-600" aria-label="{{ __('Toggle') }}">
                    <i class="ik ik-chevron-down transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                </button>
            </div>
            <div x-show="open" x-collapse>
                <div class="p-5">
                    <p class="text-sm text-gray-600">{{ __('The body of the widget') }}</p>
                </div>
            </div>
        </div>

        <!-- Removable -->
        <div x-data="{ show: true }" x-show="show" x-transition class="rounded-2xl border border-gray-100 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h5 class="font-semibold text-gray-700">{{ __('Removable') }}</h5>
                <button type="button" @click="show = false" class="text-gray-400 transition hover:text-accent-500" aria-label="{{ __('Remove') }}">
                    <i class="ik ik-x"></i>
                </button>
            </div>
            <div class="p-5">
                <p class="text-sm text-gray-600">{{ __('The body of the widget') }}</p>
            </div>
        </div>

        <!-- Reload / overlay -->
        <div x-data="{ loading: false, reload() { this.loading = true; setTimeout(() => this.loading = false, 1000); } }" class="rounded-2xl border border-gray-100 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h5 class="font-semibold text-gray-700">{{ __('Reload') }}</h5>
                <button type="button" @click="reload()" class="text-gray-400 transition hover:text-primary-600" aria-label="{{ __('Reload') }}">
                    <i class="ik ik-refresh-cw" :class="loading ? 'animate-spin' : ''"></i>
                </button>
            </div>
            <div class="relative p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <h6 class="text-sm text-gray-500">{{ __('Server') }}</h6>
                        <h2 class="text-2xl font-bold text-gray-700">62%</h2>
                    </div>
                    <i class="ik ik-server text-3xl text-gray-300"></i>
                </div>
                <div x-show="loading" x-transition.opacity class="absolute inset-0 flex items-center justify-center rounded-b-2xl bg-white/70" style="display: none;">
                    <i class="ik ik-loader animate-spin text-2xl text-primary-500"></i>
                </div>
            </div>
        </div>

        <!-- Expandable + removable combo -->
        <div x-data="{ open: true, show: true }" x-show="show" x-transition class="rounded-2xl border border-gray-100 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h5 class="font-semibold text-gray-700">{{ __('Traffic') }}</h5>
                <div class="flex items-center gap-3">
                    <button type="button" @click="open = !open" class="text-gray-400 transition hover:text-primary-600" aria-label="{{ __('Toggle') }}">
                        <i class="ik ik-chevron-down transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                    </button>
                    <button type="button" @click="show = false" class="text-gray-400 transition hover:text-accent-500" aria-label="{{ __('Remove') }}">
                        <i class="ik ik-x"></i>
                    </button>
                </div>
            </div>
            <div x-show="open" x-collapse>
                <div class="p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <h6 class="text-sm text-gray-500">{{ __('Traffic') }}</h6>
                            <h2 class="text-2xl font-bold text-gray-700">45%</h2>
                        </div>
                        <i class="ik ik-trending-up text-3xl text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- colored expandable / overlay widgets -->
    <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl bg-gradient-to-br from-primary-400 to-primary-500 p-5 text-white shadow-sm dark:bg-none dark:bg-primary-500/10 dark:text-gray-800 dark:ring-1 dark:ring-primary-500/20">
            <h3 class="mb-3 font-semibold">Expandable</h3>
            <p class="text-sm text-white/80">The body of the widget</p>
        </div>
        <div class="rounded-xl bg-gradient-to-br from-emerald-400 to-teal-500 p-5 text-white shadow-sm dark:bg-none dark:bg-green-500/10 dark:text-gray-800 dark:ring-1 dark:ring-green-500/20">
            <h3 class="mb-3 font-semibold">Removable</h3>
            <p class="text-sm text-white/80">The body of the widget</p>
        </div>
        <div class="rounded-xl bg-gradient-to-br from-amber-300 to-amber-400 p-5 text-white shadow-sm dark:bg-none dark:bg-amber-500/10 dark:text-gray-800 dark:ring-1 dark:ring-amber-500/20">
            <div class="flex items-center justify-between">
                <div>
                    <h6 class="text-sm text-white/80">{{ __('Server') }}</h6>
                    <h2 class="text-2xl font-bold">62%</h2>
                </div>
                <i class="ik ik-server text-3xl text-white/40"></i>
            </div>
        </div>
        <div class="rounded-xl bg-gradient-to-br from-primary-500 to-primary-600 p-5 text-white shadow-sm dark:bg-none dark:bg-primary-500/10 dark:text-gray-800 dark:ring-1 dark:ring-primary-500/20">
            <div class="flex items-center justify-between">
                <div>
                    <h6 class="text-sm text-white/80">{{ __('Traffic') }}</h6>
                    <h2 class="text-2xl font-bold">45%</h2>
                </div>
                <i class="ik ik-trending-up text-3xl text-white/40"></i>
            </div>
        </div>
    </div>

    <!-- Feeds + Timeline + Todos -->
    <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-3">
        <x-card>
            <x-slot:header>Feeds</x-slot:header>
            <div class="space-y-4">
                @php
                    $feeds = [
                        ['ik ik-thumbs-up', 'text-primary-600', '7 New Feedback', 'Today', 'It will give a smart finishing to your site'],
                        ['ik ik-user', 'text-green-600', 'New User', '10:45', 'I feel great! Thanks team'],
                        ['ik ik-alert-circle', 'text-amber-600', 'Server Warning', '10:50', 'Your connection is not private'],
                        ['ik ik-check-circle', 'text-accent-600', 'Issue Fixed', '11:05', 'WE have fix all Design bug with Responsive'],
                        ['ik ik-shopping-cart', 'text-primary-700', '7 New Orders', '11:35', 'You received a new oder from Tina.'],
                    ];
                @endphp
                @foreach ($feeds as [$icon, $iconClass, $title, $time, $text])
                    <a href="#" class="flex gap-3">
                        <i class="{{ $icon }} {{ $iconClass }} mt-1 text-lg"></i>
                        <div class="flex-1">
                            <h4 class="flex justify-between text-sm font-semibold {{ $iconClass }}">{{ $title }} <small class="font-normal text-gray-400">{{ $time }}</small></h4>
                            <small class="text-gray-500">{{ $text }}</small>
                        </div>
                    </a>
                @endforeach
            </div>
        </x-card>

        <x-card no-padding>
            <x-slot:header>Timeline</x-slot:header>
            <div class="relative bg-cover bg-center p-5" style="background-image: url('{{ asset('img/placeholder/placeimg_400_200_nature.jpg') }}')">
                <div class="flex items-center gap-3 rounded-lg bg-black/40 p-4 text-white">
                    <div class="text-4xl font-bold">8</div>
                    <div>
                        <div class="font-semibold">Monday</div>
                        <div class="text-sm text-white/80">February 2018</div>
                    </div>
                </div>
            </div>
            <ul class="space-y-5 p-5">
                @foreach ([['bg-accent-500','11am','Attendance','Computer Class'],['bg-green-500','12pm','Design Team','Hangouts'],['bg-amber-500','2pm','Finish','Go to Home']] as [$bullet, $time, $title, $sub])
                    <li class="flex gap-4">
                        <span class="mt-1.5 h-3 w-3 shrink-0 rounded-full {{ $bullet }}"></span>
                        <div class="text-sm text-gray-400">{{ $time }}</div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-700">{{ $title }}</h3>
                            <h4 class="text-xs text-gray-400">{{ $sub }}</h4>
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-card>

        <x-card>
            <x-slot:header>Todos</x-slot:header>
            <ul class="space-y-5">
                @php
                    $todos = [
                        ['Meeting', 'text-primary-600', 'Upcoming in 5 days', 'Meeting with Sara in the Caffee Caldo for Brunch', 'Scheduled for 16th Mar, 2017', false],
                        ['Meeting', 'text-primary-600', 'Delay 7 days', 'Technical management meeting', 'Completed 15 days ago', false],
                        ['Transfer', 'text-accent-600', 'Completed', 'Transfer all domain names as soon as possible!', 'Completed 2 days ago', true],
                    ];
                @endphp
                @foreach ($todos as [$tag, $tagClass, $tagNote, $title, $note, $done])
                    <li class="flex gap-3">
                        <span class="mt-1 h-4 w-4 shrink-0 rounded border {{ $done ? 'border-green-500 bg-green-500' : 'border-gray-300' }}"></span>
                        <div class="{{ $done ? 'line-through opacity-60' : '' }}">
                            <p class="text-xs"><span class="{{ $tagClass }}">{{ $tag }}</span> - {{ $tagNote }}</p>
                            <p class="text-sm text-gray-700">{{ $title }}</p>
                            <small class="text-gray-400">{{ $note }}</small>
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-card>
    </div>

    <!-- Recent Chat + Visitors By Countries -->
    <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-3">
        <x-card no-padding>
            <x-slot:header>Recent Chat</x-slot:header>
            <div class="max-h-80 space-y-4 overflow-y-auto p-5">
                <div class="flex gap-3">
                    <img src="{{ asset('img/users/1.jpg') }}" alt="user" class="h-10 w-10 shrink-0 rounded-full object-cover">
                    <div>
                        <h6 class="text-sm font-medium text-gray-700">{{ __('James Anderson') }}</h6>
                        <div class="mt-1 inline-block rounded-lg bg-primary-50 px-3 py-2 text-sm text-gray-600">Lorem Ipsum is simply dummy text of the printing &amp; type setting industry.</div>
                    </div>
                    <div class="ml-auto whitespace-nowrap text-xs text-gray-400">10:56 am</div>
                </div>
                <div class="flex gap-3">
                    <img src="{{ asset('img/users/2.jpg') }}" alt="user" class="h-10 w-10 shrink-0 rounded-full object-cover">
                    <div>
                        <h6 class="text-sm font-medium text-gray-700">Bianca Doe</h6>
                        <div class="mt-1 inline-block rounded-lg bg-primary-50 px-3 py-2 text-sm text-gray-600">It’s Great opportunity to work.</div>
                    </div>
                    <div class="ml-auto whitespace-nowrap text-xs text-gray-400">10:57 am</div>
                </div>
                <div class="flex flex-row-reverse">
                    <div class="inline-block rounded-lg bg-gray-100 px-3 py-2 text-sm text-gray-700">I would love to join the team.</div>
                </div>
                <div class="flex flex-row-reverse items-end gap-2">
                    <div class="inline-block rounded-lg bg-gray-100 px-3 py-2 text-sm text-gray-700">Whats budget of the new project.</div>
                    <div class="whitespace-nowrap text-xs text-gray-400">10:59 am</div>
                </div>
                <div class="flex gap-3">
                    <img src="{{ asset('img/users/3.jpg') }}" alt="user" class="h-10 w-10 shrink-0 rounded-full object-cover">
                    <div>
                        <h6 class="text-sm font-medium text-gray-700">Angelina Rhodes</h6>
                        <div class="mt-1 inline-block rounded-lg bg-primary-50 px-3 py-2 text-sm text-gray-600">Well we have good budget for the project</div>
                    </div>
                    <div class="ml-auto whitespace-nowrap text-xs text-gray-400">11:00 am</div>
                </div>
            </div>
            <div class="flex items-center gap-2 border-t border-gray-100 p-4">
                <input type="text" placeholder="Type and enter" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-400 focus:outline-none">
                <x-button type="button"><i class="fa fa-paper-plane"></i></x-button>
            </div>
        </x-card>

        <x-card class="xl:col-span-2">
            <div class="grid grid-cols-1 items-center gap-5 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <h3 class="mb-4 font-semibold text-gray-700">Visitors By Countries</h3>
                    <x-chart.line height="h-64" color="primary" :area="true"
                                  :data="[1200, 980, 1450, 1180, 1680, 1380, 1880, 1600, 2100, 1850, 2280, 2050]"
                                  :labels="['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']" />
                </div>
                <div class="space-y-4">
                    @foreach ([['India','28%',48,'bg-green-500'],['UK','21%',33,'bg-primary-400'],['USA','18%',40,'bg-primary-600'],['China','12%',15,'bg-accent-500']] as [$country, $pct, $width, $barColor])
                        <div>
                            <div class="flex justify-between text-sm text-gray-600">
                                <span>{{ $country }}</span>
                                <span>{{ $pct }}</span>
                            </div>
                            <div class="mt-1 h-1.5 rounded-full bg-gray-100"><div class="h-full rounded-full {{ $barColor }}" style="width: {{ $width }}%"></div></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </x-card>
    </div>

    <!-- Profile + Datepicker + Weather -->
    <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-3">
        <x-card no-padding>
            <div class="p-5 text-center">
                <img src="{{ asset('img/user.jpg') }}" width="150" class="mx-auto rounded-full" alt="user">
                <h4 class="mt-5 font-semibold text-gray-700">John Doe</h4>
                <a href="#" class="text-sm text-primary-600 hover:underline">johndoe@admin.com</a>
                <div class="mt-3 flex flex-wrap justify-center gap-2">
                    <x-badge color="dark">Dashboard</x-badge>
                    <x-badge color="dark">UI</x-badge>
                    <x-badge color="dark">UX</x-badge>
                    <x-badge color="primary" title="3 more">+3</x-badge>
                </div>
            </div>
            <div class="grid grid-cols-2 divide-x divide-gray-100 border-t border-gray-100">
                <a href="#" class="flex items-center justify-center gap-2 py-4 text-sm text-gray-600 hover:text-primary-600"><i class="ik ik-message-square text-lg"></i>Message</a>
                <a href="#" class="flex items-center justify-center gap-2 py-4 text-sm text-gray-600 hover:text-primary-600"><i class="ik ik-box text-lg"></i>Portfolio</a>
            </div>
        </x-card>

        <x-card>
            <div x-data="{
                today: new Date(),
                current: new Date(),
                events: [3, 12, 18, 25],
                monthNames: ['January','February','March','April','May','June','July','August','September','October','November','December'],
                weekDays: ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'],
                get label() { return this.monthNames[this.current.getMonth()] + ' ' + this.current.getFullYear(); },
                prev() { this.current = new Date(this.current.getFullYear(), this.current.getMonth() - 1, 1); },
                next() { this.current = new Date(this.current.getFullYear(), this.current.getMonth() + 1, 1); },
                days() {
                    const year = this.current.getFullYear();
                    const month = this.current.getMonth();
                    const firstDay = new Date(year, month, 1).getDay();
                    const total = new Date(year, month + 1, 0).getDate();
                    const cells = [];
                    for (let i = 0; i < firstDay; i++) cells.push(null);
                    for (let d = 1; d <= total; d++) cells.push(d);
                    return cells;
                },
                isToday(d) {
                    return d &&
                        this.current.getFullYear() === this.today.getFullYear() &&
                        this.current.getMonth() === this.today.getMonth() &&
                        d === this.today.getDate();
                },
                hasEvent(d) {
                    return d &&
                        this.current.getFullYear() === this.today.getFullYear() &&
                        this.current.getMonth() === this.today.getMonth() &&
                        this.events.includes(d);
                }
            }">
                <div class="mb-4 flex items-center justify-between">
                    <h4 class="font-semibold text-gray-700" x-text="label"></h4>
                    <div class="flex items-center gap-1">
                        <button type="button" @click="prev()" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-100 hover:text-primary-600" aria-label="{{ __('Previous month') }}">
                            <i class="ik ik-chevron-left"></i>
                        </button>
                        <button type="button" @click="next()" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-100 hover:text-primary-600" aria-label="{{ __('Next month') }}">
                            <i class="ik ik-chevron-right"></i>
                        </button>
                    </div>
                </div>
                <div class="grid grid-cols-7 gap-1 text-center text-xs font-medium text-gray-400">
                    <template x-for="day in weekDays" :key="day">
                        <div class="py-1" x-text="day"></div>
                    </template>
                </div>
                <div class="mt-1 grid grid-cols-7 gap-1 text-center text-sm">
                    <template x-for="(d, idx) in days()" :key="idx">
                        <div class="relative flex h-9 items-center justify-center">
                            <template x-if="d">
                                <span
                                    class="flex h-8 w-8 items-center justify-center rounded-full transition"
                                    :class="isToday(d) ? 'bg-primary-500 text-white' : 'text-gray-600 hover:bg-gray-100'"
                                    x-text="d"></span>
                            </template>
                            <span x-show="hasEvent(d) && !isToday(d)" class="absolute bottom-0.5 h-1 w-1 rounded-full bg-accent-500"></span>
                        </div>
                    </template>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="flex items-center justify-between">
                <h4 class="font-semibold text-gray-700">Weather Report</h4>
                <select class="w-24 rounded-lg border border-gray-200 px-2 py-1 text-sm focus:border-primary-400 focus:outline-none">
                    <option selected="">Today</option>
                    <option value="1">Weekly</option>
                </select>
            </div>
            <div class="mt-3 flex items-center gap-3">
                <div class="text-4xl text-primary-500"><i class="wi wi-day-showers"></i> <span class="text-3xl text-gray-700">73<sup>°</sup></span></div>
                <div>
                    <h3 class="font-semibold text-gray-700">Saturday</h3><small class="text-gray-400">Banglore, India</small>
                </div>
            </div>
            <table class="mt-4 w-full text-sm">
                <tbody>
                    <tr class="border-b border-gray-50"><td class="py-2 text-gray-500">Wind</td><td class="py-2 font-medium text-gray-700">ESE 17 mph</td></tr>
                    <tr class="border-b border-gray-50"><td class="py-2 text-gray-500">Humidity</td><td class="py-2 font-medium text-gray-700">83%</td></tr>
                    <tr><td class="py-2 text-gray-500">Pressure</td><td class="py-2 font-medium text-gray-700">28.56 in</td></tr>
                </tbody>
            </table>
            <hr class="my-4 border-gray-100">
            <ul class="grid grid-cols-4 gap-2 text-center">
                <li><i class="wi wi-day-sunny text-primary-500"></i><br><span class="text-xs text-gray-400">09:30</span><h3 class="font-semibold text-gray-700">70<sup>°</sup></h3></li>
                <li><i class="wi wi-day-cloudy text-primary-500"></i><br><span class="text-xs text-gray-400">11:30</span><h3 class="font-semibold text-gray-700">72<sup>°</sup></h3></li>
                <li><i class="wi wi-day-hail text-primary-500"></i><br><span class="text-xs text-gray-400">13:30</span><h3 class="font-semibold text-gray-700">75<sup>°</sup></h3></li>
                <li><i class="wi wi-day-sprinkle text-primary-500"></i><br><span class="text-xs text-gray-400">15:30</span><h3 class="font-semibold text-gray-700">76<sup>°</sup></h3></li>
            </ul>
        </x-card>
    </div>
@endsection
