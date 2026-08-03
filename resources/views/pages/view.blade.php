@extends('layouts.main')
@section('title', 'View')
@section('content')
    <x-page-header title="{{ __('View') }}" icon="ik ik-eye" :breadcrumbs="['Home' => route('dashboard'), 'View' => null]" />

    <!-- Stat widgets -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $widgets = [
                ['Bookmarks', '1,410', 'ik ik-award', '6% higher than last month', 62, 'bg-accent-500'],
                ['Likes', '41,410', 'ik ik-thumbs-up', '61% higher than last month', 78, 'bg-green-500'],
                ['Events', '410', 'ik ik-calendar', 'Total Events', 31, 'bg-amber-500'],
                ['Comments', '41,410', 'ik ik-message-square', 'Total Comments', 20, 'bg-primary-500'],
            ];
        @endphp
        @foreach ($widgets as [$label, $value, $icon, $note, $progress, $bar])
            <x-card class="overflow-hidden" no-padding>
                <div class="p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <h6 class="text-sm text-gray-400">{{ __($label) }}</h6>
                            <h2 class="text-2xl font-bold text-gray-700">{{ __($value) }}</h2>
                        </div>
                        <i class="{{ $icon }} text-3xl text-gray-300"></i>
                    </div>
                    <small class="mt-2 block text-xs text-gray-400">{{ __($note) }}</small>
                </div>
                <div class="h-1.5 w-full bg-gray-100">
                    <div class="h-full {{ $bar }}" style="width: {{ $progress }}%;"></div>
                </div>
            </x-card>
        @endforeach
    </div>

    <!-- Visitors + Donut -->
    <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-3">
        <x-card class="xl:col-span-2">
            <div class="grid grid-cols-1 items-center gap-5 lg:grid-cols-2">
                <div>
                    <h3 class="mb-4 font-semibold text-gray-700">{{ __('Visitors By Countries') }}</h3>
                    <x-chart.line height="h-44" color="primary" :area="true"
                                  :data="[820, 640, 950, 760, 1080, 880, 1180, 1020]"
                                  :labels="['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug']" />
                </div>
                <div class="space-y-4">
                    @php
                        $countries = [
                            ['India', '28%', 48, 'bg-green-500'],
                            ['Bangladesh', '21%', 33, 'bg-primary-400'],
                            ['USA', '18%', 40, 'bg-primary-600'],
                            ['China', '12%', 15, 'bg-accent-500'],
                        ];
                    @endphp
                    @foreach ($countries as [$name, $percent, $width, $bar])
                        <div>
                            <div class="flex items-center justify-between text-sm text-gray-600">
                                <span>{{ __($name) }}</span>
                                <span>{{ $percent }}</span>
                            </div>
                            <div class="mt-2 h-1.5 w-full rounded-full bg-gray-100">
                                <div class="h-full rounded-full {{ $bar }}" style="width: {{ $width }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </x-card>
        <x-card title="{{ __('Donut chart') }}">
            <x-slot:header>{{ __('Donut chart') }}</x-slot:header>
            <x-chart.donut label="79%" sublabel="{{ __('Traffic') }}"
                           :segments="[
                               ['value' => 45, 'color' => 'primary', 'label' => 'Direct'],
                               ['value' => 30, 'color' => 'green', 'label' => 'Organic'],
                               ['value' => 25, 'color' => 'amber', 'label' => 'Referral'],
                           ]" />
        </x-card>
    </div>

    <!-- Chat + Weather + Timeline -->
    <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-3">
        <!-- Recent Chat -->
        <x-card no-padding>
            <x-slot:header>{{ __('Recent Chat') }}</x-slot:header>
            <div class="max-h-72 space-y-4 overflow-y-auto p-5">
                <div class="flex items-start gap-3">
                    <img src="img/users/1.jpg" alt="user" class="h-9 w-9 shrink-0 rounded-full object-cover">
                    <div class="flex-1">
                        <h6 class="text-sm font-medium text-gray-700">James Anderson</h6>
                        <div class="mt-1 inline-block rounded-lg bg-primary-50 px-3 py-2 text-sm text-gray-600">Lorem Ipsum is simply dummy text of the printing &amp; type setting industry.</div>
                    </div>
                    <span class="whitespace-nowrap text-xs text-gray-400">10:56 am</span>
                </div>
                <div class="flex items-start gap-3">
                    <img src="img/users/2.jpg" alt="user" class="h-9 w-9 shrink-0 rounded-full object-cover">
                    <div class="flex-1">
                        <h6 class="text-sm font-medium text-gray-700">Bianca Doe</h6>
                        <div class="mt-1 inline-block rounded-lg bg-primary-50 px-3 py-2 text-sm text-gray-600">It’s Great opportunity to work.</div>
                    </div>
                    <span class="whitespace-nowrap text-xs text-gray-400">10:57 am</span>
                </div>
                <div class="flex justify-end">
                    <div class="inline-block rounded-lg bg-gray-100 px-3 py-2 text-sm text-gray-600">I would love to join the team.</div>
                </div>
                <div class="flex items-start justify-end gap-3">
                    <div class="inline-block rounded-lg bg-gray-100 px-3 py-2 text-sm text-gray-600">Whats budget of the new project.</div>
                    <span class="whitespace-nowrap text-xs text-gray-400">10:59 am</span>
                </div>
                <div class="flex items-start gap-3">
                    <img src="img/users/3.jpg" alt="user" class="h-9 w-9 shrink-0 rounded-full object-cover">
                    <div class="flex-1">
                        <h6 class="text-sm font-medium text-gray-700">Angelina Rhodes</h6>
                        <div class="mt-1 inline-block rounded-lg bg-primary-50 px-3 py-2 text-sm text-gray-600">Well we have good budget for the project</div>
                    </div>
                    <span class="whitespace-nowrap text-xs text-gray-400">11:00 am</span>
                </div>
            </div>
            <div class="flex items-center gap-2 border-t border-gray-100 p-4">
                <input type="text" placeholder="Type and enter" class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                <x-button variant="primary"><i class="fa fa-paper-plane"></i></x-button>
            </div>
        </x-card>

        <!-- Weather Report -->
        <x-card>
            <div class="flex items-center justify-between">
                <h4 class="font-semibold text-gray-700">{{ __('Weather Report') }}</h4>
                <select class="w-28 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                    <option selected="">Today</option>
                    <option value="1">Weekly</option>
                </select>
            </div>
            <div class="mt-6 flex items-center gap-3">
                <div class="text-5xl text-primary-500"><i class="wi wi-day-showers"></i> <span>23<sup>°</sup></span></div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-700">Saturday</h3>
                    <small class="text-gray-400">Banglore, India</small>
                </div>
            </div>
            <table class="mt-4 w-full text-sm">
                <tbody class="divide-y divide-gray-50">
                    <tr>
                        <td class="py-2 text-gray-600">Wind</td>
                        <td class="py-2 font-medium text-gray-700">ESE 17 mph</td>
                    </tr>
                    <tr>
                        <td class="py-2 text-gray-600">Humidity</td>
                        <td class="py-2 font-medium text-gray-700">83%</td>
                    </tr>
                    <tr>
                        <td class="py-2 text-gray-600">Pressure</td>
                        <td class="py-2 font-medium text-gray-700">28.56 in</td>
                    </tr>
                </tbody>
            </table>
            <hr class="my-4 border-gray-100">
            <ul class="grid grid-cols-3 text-center text-gray-600">
                <li><i class="wi wi-day-sunny mr-1"></i><span>09:30</span><h3 class="text-lg font-semibold">20<sup>°</sup></h3></li>
                <li><i class="wi wi-day-cloudy mr-1"></i><span>11:30</span><h3 class="text-lg font-semibold">22<sup>°</sup></h3></li>
                <li><i class="wi wi-day-hail mr-1"></i><span>13:30</span><h3 class="text-lg font-semibold">25<sup>°</sup></h3></li>
            </ul>
        </x-card>

        <!-- Timeline -->
        <x-card no-padding>
            <x-slot:header>{{ __('Timeline') }}</x-slot:header>
            <div class="relative flex items-center gap-3 bg-gradient-to-br from-primary-500 to-primary-600 px-5 py-6 text-white">
                <div class="text-4xl font-bold">8</div>
                <div>
                    <div class="font-medium">Monday</div>
                    <div class="text-sm text-white/80">February 2018</div>
                </div>
            </div>
            <ul class="space-y-4 p-5">
                <li class="flex items-center gap-3">
                    <span class="h-3 w-3 shrink-0 rounded-full bg-accent-500"></span>
                    <span class="w-12 text-sm text-gray-400">11am</span>
                    <div>
                        <h3 class="font-semibold text-gray-700">Attendance</h3>
                        <h4 class="text-sm text-gray-400">Computer Class</h4>
                    </div>
                </li>
                <li class="flex items-center gap-3">
                    <span class="h-3 w-3 shrink-0 rounded-full bg-green-500"></span>
                    <span class="w-12 text-sm text-gray-400">12pm</span>
                    <div>
                        <h3 class="font-semibold text-gray-700">Design Team</h3>
                        <h4 class="text-sm text-gray-400">Hangouts</h4>
                    </div>
                </li>
                <li class="flex items-center gap-3">
                    <span class="h-3 w-3 shrink-0 rounded-full bg-amber-500"></span>
                    <span class="w-12 text-sm text-gray-400">2pm</span>
                    <div>
                        <h3 class="font-semibold text-gray-700">Finish</h3>
                        <h4 class="text-sm text-gray-400">Go to Home</h4>
                    </div>
                </li>
            </ul>
        </x-card>
    </div>

    <!-- List view card -->
    <div class="mt-5">
        <x-card no-padding>
            <x-slot:header>
                <div class="flex w-full flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3 text-gray-400">
                        <a href="#" class="hover:text-primary-600"><i class="ik ik-inbox"></i></a>
                        <a href="#" class="hover:text-primary-600"><i class="ik ik-plus"></i></a>
                        <a href="#" class="hover:text-primary-600"><i class="ik ik-rotate-cw"></i></a>
                        <a href="#" class="hover:text-primary-600"><i class="ik ik-more-horizontal"></i></a>
                    </div>
                    <div class="relative max-w-md flex-1">
                        <input type="text" placeholder="Search.." class="w-full rounded-lg border border-gray-200 bg-white py-2 pl-3 pr-9 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                        <i class="ik ik-search absolute right-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                    <div class="flex items-center gap-3 text-gray-400">
                        <span class="text-sm">1 - 50 of 2,500</span>
                        <a href="#" class="hover:text-primary-600"><i class="ik ik-chevron-left"></i></a>
                        <a href="#" class="hover:text-primary-600"><i class="ik ik-chevron-right"></i></a>
                    </div>
                </div>
            </x-slot:header>
            <ul class="divide-y divide-gray-50">
                @foreach ([
                    'Lorem Ipsum is simply dumm dummy text of the printing and typesetting industry.',
                    'Aenean eu pharetra arcu, vitae elementum sem. Sed non ligula molestie, finibus lacus at, suscipit mi. Nunc luctus lacus vel felis blandit, eu finibus augue tincidunt.',
                    'Donec lectus augue, suscipit in sodales sit amet, semper sit amet enim. Duis pretium, nisi id pretium ornare, tortor nibh sodales tellus.',
                ] as $title)
                    <li class="px-5 py-4 hover:bg-gray-50">
                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="item_checkbox" class="h-4 w-4 shrink-0 rounded border-gray-300 text-primary-500 focus:ring-primary-200">
                            <div class="flex-1"><a href="javascript:void(0)" class="text-gray-700 hover:text-primary-600">{{ $title }}</a></div>
                            <div class="flex items-center gap-3 text-gray-400">
                                <a href="#" class="hover:text-primary-600"><i class="ik ik-eye"></i></a>
                                <a href="#" class="hover:text-primary-600"><i class="ik ik-inbox"></i></a>
                                <a href="#" class="hover:text-green-600"><i class="ik ik-edit-2"></i></a>
                                <a href="#" class="hover:text-accent-600"><i class="ik ik-trash-2"></i></a>
                            </div>
                        </div>
                        <p class="mt-2 text-sm text-gray-400">Fusce suscipit turpis a dolor posuere ornare at a ante. Quisque nec libero facilisis, egestas tortor eget, mattis dui. Curabitur viverra laoreet ligula at hendrerit. Nullam sollicitudin maximus leo, vel pulvinar orci semper id. Donec vehicula tempus enim a facilisis. Proin dignissim porttitor sem, sed pulvinar tortor gravida vitae.</p>
                    </li>
                @endforeach
            </ul>
        </x-card>
    </div>

    <!-- Advanced table card -->
    <div class="mt-5">
        <x-card no-padding>
            <x-slot:header>
                <div class="flex w-full flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3 text-gray-400">
                        <a href="#" class="hover:text-primary-600"><i class="ik ik-inbox"></i></a>
                        <a href="#" class="hover:text-primary-600"><i class="ik ik-plus"></i></a>
                        <a href="#" class="hover:text-primary-600"><i class="ik ik-rotate-cw"></i></a>
                        <a href="#" class="hover:text-primary-600"><i class="ik ik-more-horizontal"></i></a>
                    </div>
                    <div class="relative max-w-md flex-1">
                        <input type="text" id="global_filter" placeholder="Search.." class="w-full rounded-lg border border-gray-200 bg-white py-2 pl-3 pr-9 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                        <i class="ik ik-search absolute right-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                    <div class="flex items-center gap-3 text-gray-400">
                        <span class="text-sm" id="top">1 - 50 of 2,500</span>
                        <a href="#" class="hover:text-primary-600"><i class="ik ik-chevron-left"></i></a>
                        <a href="#" class="hover:text-primary-600"><i class="ik ik-chevron-right"></i></a>
                    </div>
                </div>
            </x-slot:header>
            <div class="overflow-x-auto">
                <table id="advanced_table" class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                            <th class="px-5 py-3 font-medium">
                                <input type="checkbox" id="selectall" class="h-4 w-4 rounded border-gray-300 text-primary-500 focus:ring-primary-200">
                            </th>
                            <th class="px-5 py-3 font-medium">Avatar</th>
                            <th class="px-5 py-3 font-medium">Name</th>
                            <th class="px-5 py-3 font-medium">Position</th>
                            <th class="px-5 py-3 font-medium">Office</th>
                            <th class="px-5 py-3 font-medium">Age</th>
                            <th class="px-5 py-3 font-medium">Start date</th>
                            <th class="px-5 py-3 font-medium">Salary</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ([
                            ['1.jpg', 'Tiger Nixon', 'System Architect', 'Edinburgh', '61', '2011/04/25', '$320,800'],
                            ['2.jpg', 'Garrett Winters', 'Accountant', 'Tokyo', '63', '2011/07/25', '$170,750'],
                            ['3.jpg', 'Ashton Cox', 'Junior Technical Author', 'San Francisco', '66', '2009/01/12', '$86,000'],
                            ['4.jpg', 'Cedric Kelly', 'Senior Javascript Developer', 'Edinburgh', '22', '2012/03/29', '$433,060'],
                            ['5.jpg', 'Airi Satou', 'Accountant', 'Tokyo', '33', '2008/11/28', '$162,700'],
                            ['1.jpg', 'Brielle Williamson', 'Integration Specialist', 'New York', '61', '2012/12/02', '$372,000'],
                            ['2.jpg', 'Herrod Chandler', 'Sales Assistant', 'San Francisco', '59', '2012/08/06', '$137,500'],
                            ['3.jpg', 'Rhona Davidson', 'Integration Specialist', 'Tokyo', '55', '2010/10/14', '$327,900'],
                            ['4.jpg', 'Colleen Hurst', 'Javascript Developer', 'San Francisco', '39', '2009/09/15', '$205,500'],
                        ] as [$avatar, $name, $position, $office, $age, $start, $salary])
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3">
                                    <input type="checkbox" class="select_all_child h-4 w-4 rounded border-gray-300 text-primary-500 focus:ring-primary-200">
                                </td>
                                <td class="px-5 py-3"><img src="img/users/{{ $avatar }}" class="h-8 w-8 rounded-full object-cover" alt=""></td>
                                <td class="px-5 py-3 text-gray-700">{{ $name }}</td>
                                <td class="px-5 py-3 text-gray-600">{{ $position }}</td>
                                <td class="px-5 py-3 text-gray-600">{{ $office }}</td>
                                <td class="px-5 py-3 text-gray-600">{{ $age }}</td>
                                <td class="px-5 py-3 text-gray-600">{{ $start }}</td>
                                <td class="px-5 py-3 text-gray-700">{{ $salary }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
@endsection
