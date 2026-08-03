@extends('layouts.main')
@section('title', 'Radial Gauges')

@push('head')
    @vite('resources/js/charts.js')
@endpush

@section('content')
    <x-page-header title="{{ __('Radial Gauges') }}" subtitle="{{ __('lorem ipsum dolor sit amet, consectetur adipisicing elit') }}" icon="ik ik-pie-chart" :breadcrumbs="['Home' => url('dashboard'), 'Charts' => '#', 'Radial Gauges' => null]" />

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2 xl:grid-cols-3">
        <x-card>
            <x-slot:header>{{ __('Overloaded "draw" method') }}</x-slot:header>
            <div id="knob-1"></div>
        </x-card>
        <x-card>
            <x-slot:header>{{ __('Angle offset and arc') }}</x-slot:header>
            <div id="knob-2"></div>
        </x-card>
        <x-card>
            <x-slot:header>{{ __('Cursor mode') }}</x-slot:header>
            <div id="knob-3"></div>
        </x-card>
        <x-card>
            <x-slot:header>{{ __('Disable display input') }}</x-slot:header>
            <div id="knob-4"></div>
        </x-card>
        <x-card>
            <x-slot:header>{{ __('Display previous value ') }}</x-slot:header>
            <div id="knob-5"></div>
        </x-card>
        <x-card>
            <x-slot:header>{{ __('Read only and size') }} </x-slot:header>
            <div id="knob-6"></div>
        </x-card>
    </div>
@endsection

@push('script')
    <script>
        (function () {
            function boot() {
                function gauge(selector, value, color, label, opts) {
                    opts = opts || {};
                    var radialBar = {
                        hollow: { size: '62%' },
                        track: { background: '#eef2f7' },
                        dataLabels: {
                            name: { show: true, offsetY: 20, color: '#94a3b8', fontSize: '13px' },
                            value: { show: true, offsetY: -10, fontSize: '26px', fontWeight: 700, color: '#94a3b8', formatter: function (v) { return v + '%'; } }
                        }
                    };
                    if (opts.startAngle !== undefined) { radialBar.startAngle = opts.startAngle; }
                    if (opts.endAngle !== undefined) { radialBar.endAngle = opts.endAngle; }

                    new ApexCharts(document.querySelector(selector), {
                        chart: { type: 'radialBar', height: 230, fontFamily: 'inherit' },
                        series: [value],
                        colors: [color],
                        plotOptions: { radialBar: radialBar },
                        labels: [label],
                        stroke: { lineCap: 'round' }
                    }).render();
                }

                // primary — full circle
                gauge('#knob-1', 24, '#2563eb', 'Loaded');
                // accent — half-gauge to match "Angle offset and arc"
                gauge('#knob-2', 35, '#eb525d', 'Offset', { startAngle: -135, endAngle: 135 });
                // green — full circle
                gauge('#knob-3', 70, '#38c172', 'Cursor');
                // cyan — full circle
                gauge('#knob-4', 50, '#06b6d4', 'Hidden');
                // amber — half-gauge variant
                gauge('#knob-5', 32, '#f59e0b', 'Previous', { startAngle: -135, endAngle: 135 });
                // purple — full circle
                gauge('#knob-6', 48, '#7c3aed', 'Read only');
            }

            if (window.chartsReady) boot(); else document.addEventListener('charts:loaded', boot);
        })();
    </script>
@endpush
