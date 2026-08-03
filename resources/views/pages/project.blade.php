@extends('layouts.main')
@section('title', 'Project')
@section('content')
    <x-page-header title="{{ __('Project') }}" icon="ik ik-briefcase" :breadcrumbs="['Home' => route('dashboard'), 'Project' => null]" />

    <!-- Round Chart statistic cards -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $statCards = [
                ['144', 'Leads', '42%', 'accent', true, [6,9,7,11,9,13]],
                ['102', 'Goals', '56%', 'primary', true, [8,7,10,9,12,14]],
                ['124', 'Contacts', '83%', 'green', true, [10,12,11,14,13,16]],
                ['84', 'Accounts', '42%', 'amber', false, [14,12,13,11,12,10]],
            ];
        @endphp
        @foreach ($statCards as [$value, $label, $percent, $color, $up, $spark])
            <x-stat-card :value="__($value)" :label="__($label)" :color="$color"
                         :trend="$percent" :trendUp="$up" :spark="$spark" variant="gradient" />
        @endforeach
    </div>

    <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-2 xl:grid-cols-3">
        <!-- Timeline -->
        <x-card no-padding>
            <x-slot:header>{{ __('Timeline') }}</x-slot:header>
            <div class="relative flex items-center bg-gradient-to-br from-primary-500 to-primary-600 px-5 py-6 text-white">
                <div class="text-5xl font-bold">8</div>
                <div class="ml-4">
                    <div class="font-semibold">{{ __('Monday') }}</div>
                    <div class="text-sm text-white/80">{{ __('February 2018') }}</div>
                </div>
            </div>
            <ul class="p-5">
                @php
                    $timeline = [
                        ['bg-accent-500', '11am', 'Attendance', 'Computer Class'],
                        ['bg-green-500', '12pm', 'Design Team', 'Hangouts'],
                        ['bg-amber-500', '2pm', 'Finish', 'Go to Home'],
                    ];
                @endphp
                @foreach ($timeline as [$dot, $time, $title, $sub])
                    <li class="flex items-start gap-3 @if (! $loop->last) mb-5 @endif">
                        <span class="mt-1.5 h-3 w-3 shrink-0 rounded-full {{ $dot }}"></span>
                        <div class="w-12 shrink-0 text-sm text-gray-400">{{ __($time) }}</div>
                        <div>
                            <h3 class="font-semibold text-gray-700">{{ __($title) }}</h3>
                            <h4 class="text-sm text-gray-400">{{ __($sub) }}</h4>
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-card>

        <!-- Todos -->
        <x-card>
            <x-slot:header>{{ __('Todos') }}</x-slot:header>
            @php
                $todos = [
                    ['Meeting', 'primary', 'Upcoming in 5 days', 'Meeting with Sara in the Caffee Caldo for Brunch', 'Scheduled for 16th Mar, 2017', false],
                    ['Meeting', 'primary', 'Delay 7 days', 'Technical management meeting', 'Completed 15 days ago', false],
                    ['Transfer', 'danger', 'Completed', 'Transfer all domain names as soon as possible!', 'Completed 2 days ago', true],
                ];
            @endphp
            <ul class="space-y-4">
                @foreach ($todos as [$tag, $tagColor, $status, $text, $meta, $completed])
                    <li class="border-l-2 border-gray-100 pl-4 @if ($completed) opacity-60 @endif">
                        <p class="text-xs text-gray-400">
                            <span class="font-medium text-{{ $tagColor === 'danger' ? 'accent' : 'primary' }}-600">{{ __($tag) }}</span> - {{ __($status) }}
                        </p>
                        <p class="text-sm text-gray-700 @if ($completed) line-through @endif">{{ __($text) }}</p>
                        <small class="text-xs text-gray-400">{{ __($meta) }}</small>
                    </li>
                @endforeach
            </ul>
        </x-card>

        <!-- My Projects -->
        <x-card>
            <x-slot:header>{{ __('My Projects') }}</x-slot:header>
            @php
                $myProjects = [
                    ['New Dashboard', '5 Mins ago', ['2.jpg', '3.jpg', '2.jpg']],
                    ['Web Design', '8 Mins ago', ['2.jpg', '3.jpg']],
                    ['Android Design', '12 Mins ago', ['4.jpg', '2.jpg', '3.jpg']],
                ];
            @endphp
            <div class="space-y-5">
                @foreach ($myProjects as [$name, $ago, $avatars])
                    <div>
                        <p class="mb-2 flex items-center justify-between text-sm text-gray-700">
                            {{ __($name) }}
                            <span class="text-xs text-gray-400">{{ __($ago) }}</span>
                        </p>
                        <div class="flex -space-x-2">
                            @foreach ($avatars as $avatar)
                                <img src="{{ asset('img/users/'.$avatar) }}" alt="user-image" class="h-8 w-8 rounded-full border-2 border-white object-cover">
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-5 flex">
                <input type="text" class="w-full rounded-l-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200" placeholder="{{ __('Add Project') }}">
                <x-button variant="primary" class="rounded-l-none">
                    <i class="ik ik-plus"></i>
                </x-button>
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
                        @php
                            $tasks = [
                                ['Building Marketing List', 'Software pro', 'Task', 'Open', 'danger', 'Ken Malit', 'Normal', '7/8/2017', '8/9/2018'],
                                ['Project Task List', 'Software pro', 'Task', 'New', 'primary', 'Ken Malit', 'Normal', '7/8/2017', '8/9/2018'],
                                ['Building Marketing List', 'Software pro', 'Task', 'Open', 'danger', 'Ken Malit', 'Normal', '7/8/2017', '8/9/2018'],
                                ['Project Task List', 'Software pro', 'Task', 'Close', 'success', 'Ken Malit', 'Normal', '7/8/2017', '8/9/2018'],
                                ['Building Marketing List', 'Software pro', 'Task', 'New', 'primary', 'Ken Malit', 'Normal', '7/8/2017', '8/9/2018'],
                            ];
                        @endphp
                        @foreach ($tasks as [$subject, $regarding, $type, $statusLabel, $statusColor, $owner, $priority, $start, $end])
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-3 text-gray-700">{{ __($subject) }}</td>
                                <td class="px-5 py-3 text-gray-700">{{ __($regarding) }}</td>
                                <td class="px-5 py-3 text-gray-700">{{ __($type) }}</td>
                                <td class="px-5 py-3"><x-badge :color="$statusColor">{{ __($statusLabel) }}</x-badge></td>
                                <td class="px-5 py-3 text-gray-700">{{ __($owner) }}</td>
                                <td class="px-5 py-3 text-gray-700">{{ __($priority) }}</td>
                                <td class="px-5 py-3 text-gray-700">{{ $start }}</td>
                                <td class="px-5 py-3 text-gray-700">{{ $end }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
@endsection
