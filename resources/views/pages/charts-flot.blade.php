@extends('layouts.main')
@section('title', 'Apex Charts')

@push('head')
    @vite('resources/js/charts.js')
@endpush

@section('content')
    <x-page-header title="{{ __('Apex Charts') }}" subtitle="{{ __('lorem ipsum dolor sit amet, consectetur adipisicing elit') }}" icon="ik ik-pie-chart" :breadcrumbs="['Home' => url('dashboard'), 'Charts' => '#', 'Apex Charts' => null]" />

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <x-card>
            <x-slot:header>{{ __('Categories chart') }}</x-slot:header>
            <div id="apex-1"></div>
        </x-card>
        <x-card>
            <x-slot:header>{{ __('Stracking chart') }}</x-slot:header>
            <div id="apex-2"></div>
        </x-card>
        <x-card>
            <x-slot:header>{{ __('Pie chart ( without legend ) ') }}</x-slot:header>
            <div id="apex-3"></div>
        </x-card>
        <x-card>
            <x-slot:header>{{ __('Image plots') }}</x-slot:header>
            <div id="apex-4"></div>
        </x-card>
        <x-card>
            <x-slot:header>{{ __('Series types') }}</x-slot:header>
            <div id="apex-5"></div>
        </x-card>
        <x-card>
            <x-slot:header>{{ __('Real-time update') }}</x-slot:header>
            <div id="apex-6"></div>
        </x-card>
        <x-card>
            <x-slot:header>{{ __('Percentiles') }}</x-slot:header>
            <div id="apex-7"></div>
        </x-card>
    </div>
@endsection

@push('script')
    <script>
        (function () {
            function boot() {
                var colors = {
                    primary: '#2563eb',
                    accent:  '#eb525d',
                    green:   '#38c172',
                    amber:   '#f59e0b',
                    cyan:    '#06b6d4',
                    purple:  '#7c3aed'
                };

                var baseChart = { height: 288, toolbar: { show: false }, fontFamily: 'inherit' };
                var baseGrid = { borderColor: '#eef2f7' };

                // 1. Categories chart — bar (primary)
                new ApexCharts(document.querySelector('#apex-1'), {
                    chart: Object.assign({ type: 'bar' }, baseChart),
                    series: [{ name: 'Categories', data: [42, 68, 55, 81, 63, 92] }],
                    xaxis: { categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'] },
                    colors: [colors.primary],
                    grid: baseGrid,
                    dataLabels: { enabled: false },
                    plotOptions: { bar: { columnWidth: '55%', borderRadius: 4 } }
                }).render();

                // 2. Stracking chart — bar (purple), suffix " hrs"
                new ApexCharts(document.querySelector('#apex-2'), {
                    chart: Object.assign({ type: 'bar' }, baseChart),
                    series: [{ name: 'Hours', data: [28, 44, 36, 52, 40, 60, 48] }],
                    xaxis: { categories: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] },
                    yaxis: { labels: { formatter: function (v) { return v + ' hrs'; } } },
                    colors: [colors.purple],
                    grid: baseGrid,
                    dataLabels: { enabled: false },
                    plotOptions: { bar: { columnWidth: '55%', borderRadius: 4 } }
                }).render();

                // 3. Pie chart (without legend) — segments A/B/C/D
                new ApexCharts(document.querySelector('#apex-3'), {
                    chart: Object.assign({ type: 'pie' }, baseChart),
                    series: [38, 27, 20, 15],
                    labels: ['A', 'B', 'C', 'D'],
                    colors: [colors.primary, colors.accent, colors.green, colors.amber],
                    legend: { show: false },
                    dataLabels: { enabled: true }
                }).render();

                // 4. Image plots — area smooth (cyan)
                new ApexCharts(document.querySelector('#apex-4'), {
                    chart: Object.assign({ type: 'area' }, baseChart),
                    series: [{ name: 'Value', data: [14, 22, 18, 30, 25, 38, 32, 44] }],
                    xaxis: { categories: ['W1', 'W2', 'W3', 'W4', 'W5', 'W6', 'W7', 'W8'] },
                    colors: [colors.cyan],
                    grid: baseGrid,
                    dataLabels: { enabled: false },
                    stroke: { curve: 'smooth', width: 2 },
                    fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } }
                }).render();

                // 5. Series types — area smooth (green), prefix "$" suffix "k"
                new ApexCharts(document.querySelector('#apex-5'), {
                    chart: Object.assign({ type: 'area' }, baseChart),
                    series: [{ name: 'Revenue', data: [20, 14, 26, 19, 32, 24, 38, 30] }],
                    xaxis: { categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug'] },
                    yaxis: { labels: { formatter: function (v) { return '$' + v + 'k'; } } },
                    colors: [colors.green],
                    grid: baseGrid,
                    dataLabels: { enabled: false },
                    stroke: { curve: 'smooth', width: 2 },
                    fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } }
                }).render();

                // 6. Real-time update — line without fill (accent)
                new ApexCharts(document.querySelector('#apex-6'), {
                    chart: Object.assign({ type: 'line' }, baseChart),
                    series: [{ name: 'Value', data: [35, 28, 42, 30, 48, 38, 55, 44, 60, 50] }],
                    xaxis: { categories: ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'] },
                    colors: [colors.accent],
                    grid: baseGrid,
                    dataLabels: { enabled: false },
                    stroke: { curve: 'smooth', width: 2 }
                }).render();

                // 7. Percentiles — bar (amber), suffix "%"
                new ApexCharts(document.querySelector('#apex-7'), {
                    chart: Object.assign({ type: 'bar' }, baseChart),
                    series: [{ name: 'Percentile', data: [25, 50, 65, 75, 85, 90, 95, 99] }],
                    xaxis: { categories: ['10', '25', '50', '75', '90', '95', '99', '100'] },
                    yaxis: { labels: { formatter: function (v) { return v + '%'; } } },
                    colors: [colors.amber],
                    grid: baseGrid,
                    dataLabels: { enabled: false },
                    plotOptions: { bar: { columnWidth: '55%', borderRadius: 4 } }
                }).render();
            }

            if (window.chartsReady) boot(); else document.addEventListener('charts:loaded', boot);
        })();
    </script>
@endpush
