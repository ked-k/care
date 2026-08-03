@extends('layouts.main')
@section('title', 'Chartist')

@push('head')
    @vite('resources/js/charts.js')
    <style>
        /* sizing */
        .ct-chart .ct-label { fill: rgba(107, 114, 128, .9); color: rgba(107, 114, 128, .9); font-size: .75rem; }

        /* brand series colours */
        .ct-primary .ct-series-a .ct-line,
        .ct-primary .ct-series-a .ct-point { stroke: #2563eb; }
        .ct-primary .ct-series-a .ct-bar { stroke: #2563eb; }
        .ct-primary .ct-series-a .ct-area,
        .ct-primary .ct-series-a .ct-slice-pie { fill: #2563eb; fill-opacity: .15; }
        .ct-primary .ct-series-a .ct-slice-donut { stroke: #2563eb; }

        .ct-cyan .ct-series-a .ct-line,
        .ct-cyan .ct-series-a .ct-point { stroke: #06b6d4; }
        .ct-cyan .ct-series-a .ct-bar { stroke: #06b6d4; }
        .ct-cyan .ct-series-a .ct-area { fill: #06b6d4; fill-opacity: .15; }

        .ct-purple .ct-series-a .ct-line,
        .ct-purple .ct-series-a .ct-point { stroke: #7c3aed; }
        .ct-purple .ct-series-a .ct-bar { stroke: #7c3aed; }
        .ct-purple .ct-series-a .ct-area { fill: #7c3aed; fill-opacity: .15; }

        .ct-accent .ct-series-a .ct-line,
        .ct-accent .ct-series-a .ct-point { stroke: #eb525d; }
        .ct-accent .ct-series-a .ct-bar { stroke: #eb525d; }
        .ct-accent .ct-series-a .ct-area { fill: #eb525d; fill-opacity: .15; }

        .ct-green .ct-series-a .ct-line,
        .ct-green .ct-series-a .ct-point { stroke: #38c172; }
        .ct-green .ct-series-a .ct-bar { stroke: #38c172; }
        .ct-green .ct-series-a .ct-area { fill: #38c172; fill-opacity: .15; }

        .ct-amber .ct-series-a .ct-line,
        .ct-amber .ct-series-a .ct-point { stroke: #f59e0b; }
        .ct-amber .ct-series-a .ct-bar { stroke: #f59e0b; }
        .ct-amber .ct-series-a .ct-area { fill: #f59e0b; fill-opacity: .15; }

        /* multi-series pie / donut palette */
        .ct-pie .ct-series-a .ct-slice-pie { fill: #2563eb; }
        .ct-pie .ct-series-b .ct-slice-pie { fill: #eb525d; }
        .ct-pie .ct-series-c .ct-slice-pie { fill: #38c172; }
        .ct-pie .ct-series-d .ct-slice-pie { fill: #f59e0b; }

        .ct-donut .ct-series-a .ct-slice-donut { stroke: #06b6d4; }
        .ct-donut .ct-series-b .ct-slice-donut { stroke: #7c3aed; }
        .ct-donut .ct-series-c .ct-slice-donut { stroke: #ec4899; }

        /* labels sit on top of the coloured slices — keep them readable */
        .ct-pie .ct-label,
        .ct-donut .ct-label { fill: #fff; color: #fff; font-weight: 600; }

        /* gauge */
        .ct-gauge .ct-series-a .ct-slice-donut { stroke: #2563eb; }
        .ct-gauge .ct-series-b .ct-slice-donut { stroke: rgba(37, 99, 235, .15); }
    </style>
@endpush

@section('content')
    <x-page-header title="{{ __('Chartist') }}" subtitle="{{ __('lorem ipsum dolor sit amet, consectetur adipisicing elit') }}" icon="ik ik-pie-chart" :breadcrumbs="['Home' => url('dashboard'), 'Charts' => '#', 'Chartist' => null]" />

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2 xl:grid-cols-3">
        <x-card>
            <x-slot:header>{{ __('Slimple Line Chart') }}</x-slot:header>
            <div id="ct-line-1" class="ct-chart ct-primary h-56"></div>
        </x-card>
        <x-card>
            <x-slot:header>{{ __('Line Chart with area') }}</x-slot:header>
            <div id="ct-line-2" class="ct-chart ct-cyan h-56"></div>
        </x-card>
        <x-card>
            <x-slot:header>{{ __('Advanced Smil Animations') }}</x-slot:header>
            <div id="ct-line-3" class="ct-chart ct-purple h-56"></div>
        </x-card>
        <x-card>
            <x-slot:header>{{ __('Bi Polar Bar Chart') }}</x-slot:header>
            <div id="ct-bar-1" class="ct-chart ct-accent h-56"></div>
        </x-card>
        <x-card>
            <x-slot:header>{{ __('Overlapping Bars on mobile') }}</x-slot:header>
            <div id="ct-bar-2" class="ct-chart ct-green h-56"></div>
        </x-card>
        <x-card>
            <x-slot:header>{{ __('Extreme Responsive Configuaration') }}</x-slot:header>
            <div id="ct-bar-3" class="ct-chart ct-amber h-56"></div>
        </x-card>
        <x-card>
            <x-slot:header>{{ __('Pie Chart') }}</x-slot:header>
            <div id="ct-pie-1" class="ct-chart ct-pie h-56"></div>
        </x-card>
        <x-card>
            <x-slot:header>{{ __('Gauge Chart') }}</x-slot:header>
            <div class="flex h-56 items-center justify-center">
                <div id="ct-gauge-1" class="ct-chart ct-gauge h-56 w-full"></div>
            </div>
        </x-card>
        <x-card>
            <x-slot:header>{{ __('Animated Donut Chart') }}</x-slot:header>
            <div id="ct-donut-1" class="ct-chart ct-donut h-56"></div>
        </x-card>
    </div>
@endsection

@push('script')
    <script>
        (function () {
            function boot() {
            // Simple Line Chart
            new Chartist.Line('#ct-line-1', {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'],
                series: [[12, 9, 17, 14, 22, 18, 26, 21, 29]]
            }, { fullWidth: true, chartPadding: { right: 20 }, showArea: false });

            // Line Chart with area
            new Chartist.Line('#ct-line-2', {
                labels: ['W1', 'W2', 'W3', 'W4', 'W5', 'W6', 'W7', 'W8'],
                series: [[8, 14, 11, 19, 16, 24, 20, 28]]
            }, { fullWidth: true, chartPadding: { right: 20 }, showArea: true });

            // Advanced animations (area)
            new Chartist.Line('#ct-line-3', {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'],
                series: [[30, 22, 35, 28, 40, 33, 45, 38, 50]]
            }, { fullWidth: true, chartPadding: { right: 20 }, showArea: true });

            // Bi Polar Bar Chart
            new Chartist.Bar('#ct-bar-1', {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                series: [[40, 65, 50, 80, 60, 90, 70]]
            }, { chartPadding: { right: 20 } });

            // Overlapping Bars on mobile
            new Chartist.Bar('#ct-bar-2', {
                labels: ['Q1', 'Q2', 'Q3', 'Q4', 'Q5', 'Q6'],
                series: [[34, 52, 41, 63, 48, 72]]
            }, {
                chartPadding: { right: 20 },
                axisY: { labelInterpolationFnc: function (value) { return value + ' units'; } }
            });

            // Extreme Responsive Configuration
            new Chartist.Bar('#ct-bar-3', {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug'],
                series: [[22, 38, 30, 45, 36, 52, 44, 60]]
            }, { chartPadding: { right: 20 } });

            // Pie Chart
            new Chartist.Pie('#ct-pie-1', {
                labels: ['Series A', 'Series B', 'Series C', 'Series D'],
                series: [35, 25, 22, 18]
            }, {
                labelInterpolationFnc: function (value) { return value + '%'; }
            });

            // Gauge Chart (75% usage rendered as a half/partial donut)
            new Chartist.Pie('#ct-gauge-1', {
                series: [75, 25]
            }, {
                donut: true,
                donutWidth: 24,
                startAngle: 270,
                total: 200,
                showLabel: false
            });

            // Animated Donut Chart
            new Chartist.Pie('#ct-donut-1', {
                labels: ['Desktop', 'Mobile', 'Tablet'],
                series: [48, 32, 20]
            }, {
                donut: true,
                donutWidth: 36,
                showLabel: true,
                labelInterpolationFnc: function (value) { return value + '%'; }
            });
            }

            if (window.chartsReady) boot();
            else document.addEventListener('charts:loaded', boot);
        })();
    </script>
@endpush
