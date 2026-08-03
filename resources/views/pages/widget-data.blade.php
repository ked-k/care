@extends('layouts.main')
@section('title', 'Widget Data')
@section('content')
    <x-page-header title="{{ __('Widget Data') }}" subtitle="{{ __('lorem ipsum dolor sit amet, consectetur adipisicing elit') }}" icon="ik ik-layers" :breadcrumbs="['Home' => url('dashboard'), 'Widgets' => '#', 'Widget Data' => null]" />

    <!-- New customers + new products -->
    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
        <x-card>
            <x-slot:header>{{ __('New Customers') }}</x-slot:header>
            @php
                $customers = [
                    ['Alex Thompson', 'Cheers!', '1.jpg', true, null],
                    ['John Doue', 'stay hungry stay foolish!', '2.jpg', true, null],
                    ['Alex Thompson', 'Cheers!', '3.jpg', false, '30 min ago'],
                    ['John Doue', 'Cheers!', '4.jpg', false, '10 min ago'],
                ];
            @endphp
            <div class="space-y-4">
                @foreach ($customers as [$name, $msg, $img, $online, $ago])
                    <div class="flex items-center gap-3">
                        <span class="relative shrink-0">
                            <img src="{{ asset('img/users/'.$img) }}" alt="user image" class="h-10 w-10 rounded-full object-cover">
                            @if ($online)<span class="absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-white bg-green-500"></span>@endif
                        </span>
                        <div class="min-w-0 flex-1">
                            <a href="#!"><h6 class="truncate text-sm font-semibold text-gray-700">{{ $name }}</h6></a>
                            <p class="truncate text-xs text-gray-400">{{ $msg }}</p>
                        </div>
                        @if ($ago)<span class="whitespace-nowrap text-xs text-gray-400"><i class="far fa-clock mr-1"></i>{{ $ago }}</span>@endif
                    </div>
                @endforeach
            </div>
        </x-card>

        <x-card class="xl:col-span-2" no-padding>
            <x-slot:header>{{ __('New Products') }}</x-slot:header>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                            <th class="px-5 py-3 font-medium">{{ __('Product Name') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Image') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Price') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ([['HeadPhone','p1.jpg','$10'],['Iphone 6','p2.jpg','$20'],['Jacket','p3.jpg','$35'],['Sofa','p4.jpg','$85'],['Iphone 6','p2.jpg','$20']] as [$pname, $pimg, $price])
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3 text-gray-700">{{ $pname }}</td>
                                <td class="px-5 py-3"><img src="{{ asset('img/widget/'.$pimg) }}" alt="" class="h-6 w-6 rounded object-cover"></td>
                                <td class="px-5 py-3"><span class="inline-block h-2.5 w-2.5 rounded-full bg-green-500"></span></td>
                                <td class="px-5 py-3 text-gray-700">{{ $price }}</td>
                                <td class="px-5 py-3">
                                    <a href="#!" class="mr-3 text-green-500 hover:text-green-600"><i class="ik ik-edit"></i></a>
                                    <a href="#!" class="text-accent-500 hover:text-accent-600"><i class="ik ik-trash-2"></i></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    <!-- Top contacts + member performance -->
    <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-2">
        <x-card no-padding>
            <x-slot:header>{{ __('Top Contacts') }}</x-slot:header>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                            <th class="px-5 py-3 font-medium">{{ __('Company') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Start Date') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('End Date') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ([['Apple Company','23/05/2017','04/08/2018','Paid','success'],['Envato Pvt Ltd.','20/03/2017','04/08/2019','Unpaid','danger'],['Dribble Company','13/05/2017','03/01/2018','Paid','success'],['Adobe Family','11/01/2016','02/03/2017','Paid','success'],['Apple Company','23/05/2017','04/08/2018','Unpaid','danger']] as [$company, $start, $end, $status, $color])
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3 text-gray-700">{{ $company }}</td>
                                <td class="px-5 py-3 text-gray-700">{{ $start }}</td>
                                <td class="px-5 py-3 text-gray-700">{{ $end }}</td>
                                <td class="px-5 py-3"><x-badge color="{{ $color }}">{{ $status }}</x-badge></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>

        <x-card no-padding>
            <x-slot:header>{{ __('Member’s  performance') }}</x-slot:header>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-50">
                        @foreach ([['4.jpg','Shirley  Hoe','Sales executive , NY','$78.001','fas fa-level-down-alt','text-accent-500'],['2.jpg','James Alexander','Sales executive , EL','$89.051','fas fa-level-up-alt','text-green-500'],['4.jpg','Shirley  Hoe','Sales executive , NY','$89.051','fas fa-level-up-alt','text-green-500'],['2.jpg','James Alexander','Sales executive , EL','$78.001','fas fa-level-down-alt','text-accent-500'],['4.jpg','Shirley  Hoe','Sales executive , NY','$78.001','fas fa-level-down-alt','text-accent-500']] as [$img, $name, $role, $amount, $icon, $iconClass])
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <img src="{{ asset('img/users/'.$img) }}" alt="user image" class="h-10 w-10 rounded-full object-cover">
                                        <div>
                                            <h6 class="text-sm font-semibold text-gray-700">{{ $name }}</h6>
                                            <p class="text-xs text-gray-400">{{ $role }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <h6 class="font-semibold text-gray-700">{{ $amount }}<i class="{{ $icon }} {{ $iconClass }} ml-2"></i></h6>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    <!-- What's New + Latest Activity + Campaign Statistics -->
    <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-3">
        <x-card>
            <x-slot:header>{{ __('What’s New') }}</x-slot:header>
            <div class="space-y-6">
                @php
                    $whatsNew = [
                        ['img', '4.jpg', 'Your Manager Posted.', 'Jonny michel'],
                        ['icon', 'ik ik-briefcase bg-accent-500', 'You have 3 pending Task.', 'Hemilton'],
                        ['icon', 'ik ik-check bg-green-500', 'New Order Received.', 'Hemilton'],
                        ['icon', 'ik ik-briefcase bg-accent-500', 'You have 3 pending Task.', 'Hemilton'],
                        ['icon', 'ik ik-check bg-green-500', 'New Order Received.', 'Hemilton'],
                        ['img', '4.jpg', 'Your Manager Posted.', 'Jonny michel'],
                    ];
                @endphp
                @foreach ($whatsNew as [$type, $val, $title, $sub])
                    <div class="flex items-center gap-3">
                        @if ($type === 'img')
                            <img src="{{ asset('img/users/'.$val) }}" alt="user image" class="h-10 w-10 rounded-full object-cover">
                        @else
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-white"><i class="{{ $val }} rounded-full p-2.5"></i></span>
                        @endif
                        <div>
                            <a href="#!"><h6 class="text-sm font-semibold text-gray-700">{{ $title }}</h6></a>
                            <p class="text-xs text-gray-400">{{ $sub }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>

        <x-card>
            <x-slot:header>{{ __('Latest Activity') }}</x-slot:header>
            <div class="space-y-6">
                @php
                    $activity = [
                        ['bg-primary-500', 'Devlopment & Update', 'Lorem ipsum dolor sit amet,', 'text-primary-600'],
                        ['bg-primary-500', 'Showcases', 'Lorem dolor sit amet,', 'text-primary-600'],
                        ['bg-green-500', 'Miscellaneous', 'Lorem ipsum dolor sit ipsum amet,', 'text-green-600'],
                        ['bg-primary-500', 'Showcases', 'Lorem dolor sit amet,', 'text-primary-600'],
                        ['bg-green-500', 'Miscellaneous', 'Lorem ipsum dolor sit ipsum amet,', 'text-green-600'],
                        ['bg-accent-500', 'Your Manager Posted.', 'Lorem ipsum dolor sit amet,', 'text-accent-600'],
                    ];
                @endphp
                @foreach ($activity as [$dot, $title, $text, $linkClass])
                    <div class="flex gap-3">
                        <span class="mt-1.5 h-3 w-3 shrink-0 rounded-full ring-2 ring-white {{ $dot }}"></span>
                        <div>
                            <a href="#!"><h6 class="text-sm font-semibold text-gray-700">{{ $title }}</h6></a>
                            <p class="text-xs text-gray-400">{{ $text }} <a href="#!" class="{{ $linkClass }}"> More</a></p>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>

        <x-card no-padding>
            <x-slot:header>{{ __('Campaign  Statistics') }}</x-slot:header>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-50">
                        @foreach ([['100','Engagement','43%','warning'],['480','Likes','58%','success'],['230','Clicks','30%','danger'],['480','Likes','30%','danger']] as [$num, $label, $pct, $color])
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3"><h3 class="text-xl font-bold text-gray-700">{{ __($num) }}</h3></td>
                                <td class="px-5 py-3"><p class="text-gray-600">{{ $label }}</p></td>
                                <td class="px-5 py-3 text-right"><x-badge color="{{ $color }}">{{ $pct }}</x-badge></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    <!-- Customer overview -->
    <div class="mt-5">
        <x-card no-padding>
            <x-slot:header>{{ __('Customer  Overview') }}</x-slot:header>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                            <th class="px-5 py-3 font-medium">{{ __('Customer') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Company') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Lead Score') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Date') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Tags') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @php
                            $overview = [
                                ['4.jpg','Shirley  Hoe','Sales executive , NY','Pinterest','223','19-11-2018',[['Sketch','primary'],['Ui','primary']]],
                                ['2.jpg','James Alexander','Sales executive , EL','Facebook','268','19-11-2018',[['Ux','primary'],['Ui','danger'],['php','danger']]],
                                ['4.jpg','Shirley  Hoe','Sales executive , NY','Twitter','293','16-03-2018',[['Sketch','danger'],['Ui','primary']]],
                                ['4.jpg','Shirley  Hoe','Sales executive , NY','Pinterest','223','19-11-2018',[['Ux','primary'],['Ui','success'],['php','warning']]],
                                ['2.jpg','James Alexander','Sales executive , EL','Facebook','268','19-11-2018',[['Sketch','primary'],['Ui','primary']]],
                                ['4.jpg','Shirley  Hoe','Sales executive , NY','Twitter','293','16-03-2018',[['Sketch','danger'],['Ui','primary']]],
                            ];
                        @endphp
                        @foreach ($overview as [$img, $name, $role, $company, $score, $date, $tags])
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <img src="{{ asset('img/users/'.$img) }}" alt="user image" class="h-10 w-10 rounded-full object-cover">
                                        <div>
                                            <h6 class="text-sm font-semibold text-gray-700">{{ $name }}</h6>
                                            <p class="text-xs text-gray-400">{{ $role }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-gray-700">{{ $company }}</td>
                                <td class="px-5 py-3 text-gray-700">{{ $score }}</td>
                                <td class="px-5 py-3 text-gray-700">{{ $date }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($tags as [$tag, $color])
                                            <x-badge color="{{ $color }}">{{ $tag }}</x-badge>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-5 py-3">
                                    <a href="#!" class="mr-3 text-green-500 hover:text-green-600"><i class="ik ik-edit"></i></a>
                                    <a href="#!" class="text-accent-500 hover:text-accent-600"><i class="ik ik-trash-2"></i></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    <!-- Testimonial + top selling -->
    <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-3">
        <x-card>
            <x-slot:header>{{ __('Testimonial') }}</x-slot:header>
            <div class="space-y-5">
                @foreach ([['2.jpg','bg-green-500'],['1.jpg','bg-primary-500'],['1.jpg','bg-accent-500']] as [$img, $barColor])
                    <div class="flex gap-3">
                        <img src="{{ asset('img/users/'.$img) }}" alt="user image" class="h-12 w-12 shrink-0 rounded-full object-cover">
                        <div class="flex-1">
                            <h6 class="mb-3 text-sm font-semibold text-gray-700">John Deo</h6>
                            <div class="flex items-center gap-3">
                                <div class="h-2 flex-1 rounded-full bg-gray-100"><div class="h-full rounded-full {{ $barColor }}" style="width:85%"></div></div>
                                <h6 class="text-sm font-semibold text-gray-700">3.2</h6>
                            </div>
                            <p class="mt-3 text-xs text-gray-400">Lorem Ipsum is simply dummy text of the printing and typesetting.</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>

        <x-card class="xl:col-span-2" no-padding>
            <x-slot:header>{{ __('New Products') }}</x-slot:header>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                            <th class="px-5 py-3 font-medium">{{ __('Name') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Product Code') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Customer') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Rating') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @php
                            $newProducts = [
                                ['Sofa','#PHD001','abc@gmail.com','Out Stock','danger',3],
                                ['Computer','#PHD002','cdc@gmail.com','In Stock','success',2],
                                ['Mobile','#PHD003','pqr@gmail.com','Out Stock','danger',4],
                                ['Coat','#PHD004','bcs@gmail.com','In Stock','success',3],
                                ['Watch','#PHD005','cdc@gmail.com','In Stock','success',2],
                                ['Shoes','#PHD006','pqr@gmail.com','Out Stock','danger',4],
                            ];
                        @endphp
                        @foreach ($newProducts as [$name, $code, $email, $status, $color, $rating])
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3 text-gray-700">{{ $name }}</td>
                                <td class="px-5 py-3 text-gray-700">{{ $code }}</td>
                                <td class="px-5 py-3"><a href="#" class="text-primary-600 hover:underline">{{ $email }}</a></td>
                                <td class="px-5 py-3"><x-badge color="{{ $color }}">{{ $status }}</x-badge></td>
                                <td class="px-5 py-3">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <a href="#!"><i class="fa fa-star {{ $i <= $rating ? 'text-amber-400' : 'text-gray-300' }}"></i></a>
                                    @endfor
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    <!-- Project Task List -->
    <div class="mt-5">
        <x-card no-padding>
            <x-slot:header>{{ __('Project Task List') }}</x-slot:header>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                            <th class="px-5 py-3 font-medium">{{ __('Subject') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Regarding') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Activity Type') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Activity Status') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Owner') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Priority') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Start Date') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('End Date') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ([['Building Marketing List','Open','danger'],['Project Task List','New','primary'],['Building Marketing List','Open','danger'],['Project Task List','Close','success'],['Building Marketing List','New','primary']] as [$subject, $status, $color])
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3 text-gray-700">{{ $subject }}</td>
                                <td class="px-5 py-3 text-gray-700">Software pro</td>
                                <td class="px-5 py-3 text-gray-700">Task</td>
                                <td class="px-5 py-3"><x-badge color="{{ $color }}">{{ $status }}</x-badge></td>
                                <td class="px-5 py-3 text-gray-700">Ken Malit</td>
                                <td class="px-5 py-3 text-gray-700">Normal</td>
                                <td class="px-5 py-3 text-gray-700">7/8/2017</td>
                                <td class="px-5 py-3 text-gray-700">8/9/2018</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    <!-- Feeds + My Projects + Chat -->
    <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-3">
        <x-card>
            <x-slot:header>{{ __('Feeds') }}</x-slot:header>
            <div class="space-y-6">
                @php
                    $feeds = [
                        ['ik ik-bell', 'bg-primary-500', 'You have 3 pending tasks.'],
                        ['ik ik-shopping-cart', 'bg-accent-500', 'New order received'],
                        ['ik ik-file-text', 'bg-green-500', 'You have 3 pending tasks.'],
                        ['ik ik-bell', 'bg-primary-500', 'You have 3 pending tasks.'],
                        ['ik ik-file-text', 'bg-green-500', 'You have 3 pending tasks.'],
                        ['ik ik-shopping-cart', 'bg-accent-500', 'New order received'],
                    ];
                @endphp
                @foreach ($feeds as [$icon, $bg, $text])
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $bg }} text-white"><i class="{{ $icon }}"></i></span>
                        <a href="#!" class="flex-1"><h6 class="flex justify-between text-sm font-semibold text-gray-700">{{ $text }} <span class="text-xs font-normal text-gray-400">Just Now</span></h6></a>
                    </div>
                @endforeach
            </div>
            <div class="mt-4 text-right">
                <a href="#!" class="text-sm font-medium text-primary-600 hover:underline">View all Feeds</a>
            </div>
        </x-card>

        <x-card>
            <x-slot:header>{{ __('My Projects') }}</x-slot:header>
            @php
                $projects = [
                    ['New Dashboard', '5 Mins ago', ['2.jpg','3.jpg','2.jpg']],
                    ['Web Design', '8 Mins ago', ['2.jpg','3.jpg']],
                    ['Android Design', '12 Mins ago', ['4.jpg','2.jpg','3.jpg']],
                    ['New Dashboard', '5 Mins ago', ['2.jpg','3.jpg','2.jpg']],
                ];
            @endphp
            @foreach ($projects as [$name, $ago, $imgs])
                <p class="mb-2 flex justify-between text-sm text-gray-700">{{ $name }} <span class="text-xs text-gray-400">{{ $ago }}</span></p>
                <div class="mb-5 flex -space-x-2">
                    @foreach ($imgs as $img)
                        <img src="{{ asset('img/users/'.$img) }}" alt="user-image" title="Tooltip on top" class="h-8 w-8 rounded-full border-2 border-white object-cover">
                    @endforeach
                </div>
            @endforeach
            <div class="mt-4 flex">
                <input type="text" class="w-full rounded-l-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-400 focus:outline-none" placeholder="Add Project">
                <x-button type="button" class="rounded-l-none"><i class="ik ik-plus"></i></x-button>
            </div>
        </x-card>

        <x-card>
            <x-slot:header>{{ __('Chat') }}</x-slot:header>
            <div class="space-y-5">
                <div class="flex gap-3">
                    <img src="{{ asset('img/users/2.jpg') }}" alt="user image" class="h-10 w-10 shrink-0 rounded-full object-cover">
                    <div>
                        <div class="inline-block rounded-lg bg-gray-100 px-3 py-2 text-sm text-gray-700">Nice to meet you!</div>
                        <p class="mt-1 text-xs text-gray-400"><i class="fa fa-clock-o mr-2"></i>10:20am</p>
                    </div>
                </div>
                <div class="flex flex-row-reverse gap-3">
                    <img src="{{ asset('img/users/3.jpg') }}" alt="user image" class="h-10 w-10 shrink-0 rounded-full object-cover">
                    <div class="text-right">
                        <div class="inline-block rounded-lg bg-primary-500 px-3 py-2 text-sm text-white">Nice to meet you!</div>
                        <p class="mt-1 text-xs text-gray-400"><i class="fa fa-clock-o mr-2"></i>10:20am</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <img src="{{ asset('img/users/2.jpg') }}" alt="user image" class="h-10 w-10 shrink-0 rounded-full object-cover">
                    <div>
                        <div class="inline-block rounded-lg bg-gray-100 px-3 py-2 text-sm text-gray-700">
                            <p>Nice to meet you!</p>
                            <img src="{{ asset('img/portfolio-1.jpg') }}" alt="" class="mt-2 inline-block h-14 rounded">
                            <img src="{{ asset('img/portfolio-3.jpg') }}" alt="" class="mt-2 inline-block h-14 rounded">
                        </div>
                        <p class="mt-1 text-xs text-gray-400"><i class="fa fa-clock-o mr-2"></i>10:20am</p>
                    </div>
                </div>
            </div>
            <div class="mt-4 flex">
                <input type="text" class="w-full rounded-l-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-400 focus:outline-none" placeholder="Send message">
                <x-button type="button" class="rounded-l-none"><i class="ik ik-message-square"></i></x-button>
            </div>
        </x-card>
    </div>

    <!-- Application Sales -->
    <div class="mt-5">
        <x-card no-padding>
            <x-slot:header>{{ __('Application Sales') }}</x-slot:header>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400">
                            <th class="px-5 py-3 font-medium">{{ __('Application') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Sales') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Change') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Avg Price') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('Total') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach ([['Able Pro','Powerful Admin Theme','16,300',70,'$53','$15,652'],['Photoshop','Design Software','26,421',85,'$35','$18,785'],['Guruable','Best Admin Template','8,265',45,'$98','$9,652'],['Flatable','Admin App','10,652',60,'$20','$7,856']] as [$app, $desc, $sales, $change, $avg, $total])
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3">
                                    <h6 class="font-semibold text-gray-700">{{ $app }}</h6>
                                    <p class="text-xs text-gray-400">{{ $desc }}</p>
                                </td>
                                <td class="px-5 py-3 text-gray-700">{{ $sales }}</td>
                                <td class="px-5 py-3">
                                    <div class="h-2 w-24 rounded-full bg-gray-100"><div class="h-full rounded-full bg-primary-500" style="width:{{ $change }}%"></div></div>
                                </td>
                                <td class="px-5 py-3 text-gray-700">{{ $avg }}</td>
                                <td class="px-5 py-3 font-medium text-primary-600">{{ $total }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-100 px-5 py-3 text-right">
                <a href="#!" class="text-sm font-medium text-primary-600 hover:underline">View all Projects</a>
            </div>
        </x-card>
    </div>
@endsection
