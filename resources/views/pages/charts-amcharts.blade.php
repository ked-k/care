@extends('layouts.main')
@section('title', 'Amcharts')
@section('content')
    <x-page-header title="{{ __('Amcharts Chart') }}" subtitle="{{ __('lorem ipsum dolor sit amet, consectetur adipisicing elit') }}" icon="ik ik-pie-chart" :breadcrumbs="['Home' => url('dashboard'), 'Charts' => '#', 'Amcharts Chart' => null]" />

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2 xl:grid-cols-3">
        <x-card>
            <x-slot:header>{{ __('3D Pie Chart') }}</x-slot:header>
            <div id="am-1" class="h-56 w-full"></div>
        </x-card>
        <x-card>
            <x-slot:header>{{ __('Bar Chart') }}</x-slot:header>
            <div id="am-2" class="h-56 w-full"></div>
        </x-card>
        <x-card>
            <x-slot:header>{{ __('Smoothed Line Chart') }}</x-slot:header>
            <div id="am-3" class="h-56 w-full"></div>
        </x-card>
        <x-card>
            <x-slot:header>{{ __('Angular Gauge') }}</x-slot:header>
            <div id="am-4" class="h-56 w-full"></div>
        </x-card>
        <x-card class="xl:col-span-2">
            <x-slot:header>{{ __('Line Chart') }}</x-slot:header>
            <div id="am-5" class="h-56 w-full"></div>
        </x-card>
        <x-card class="xl:col-span-3">
            <x-slot:header>{{ __('Map Chart') }}</x-slot:header>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                <div class="sm:col-span-2">
                    <div id="am-6" class="h-56 w-full"></div>
                </div>
                <div id="am-7" class="h-56 w-full"></div>
            </div>
        </x-card>
    </div>
@endsection

@push('head')
    @vite('resources/js/charts.js')
@endpush

@push('script')
    <script>
    (function () {
      function boot() {
        am5.ready(function () {
            // Brand palette
            var brand = {
                primary: am5.color(0x2563eb),
                accent:  am5.color(0xeb525d),
                green:   am5.color(0x38c172),
                amber:   am5.color(0xf59e0b),
                cyan:    am5.color(0x06b6d4),
                purple:  am5.color(0x7c3aed),
            };

            // Dispose any existing roots on the same ids (safe re-run)
            am5.array.each(am5.registry.rootElements, function (root) {
                if (root && root.dom && ['am-1','am-2','am-3','am-4','am-5','am-6','am-7'].indexOf(root.dom.id) !== -1) {
                    root.dispose();
                }
            });

            function newRoot(id) {
                var root = am5.Root.new(id);
                root.setThemes([am5themes_Animated.new(root)]);
                // Readable text + subtle grid in BOTH light and dark mode (default is near-black).
                root.interfaceColors.set('text', am5.color(0x94a3b8));
                root.interfaceColors.set('secondaryText', am5.color(0x94a3b8));
                root.interfaceColors.set('grid', am5.color(0x94a3b8));
                root._logo && root._logo.set('forceHidden', false); // keep amCharts attribution (required by free license)
                return root;
            }

            /* ---------- 1) 3D-style Pie Chart ---------- */
            (function () {
                var root = newRoot('am-1');
                var chart = root.container.children.push(am5percent.PieChart.new(root, {
                    layout: root.verticalLayout
                }));
                var series = chart.series.push(am5percent.PieSeries.new(root, {
                    valueField: 'value',
                    categoryField: 'label'
                }));
                series.get('colors').set('colors', [brand.primary, brand.accent, brand.green, brand.amber]);
                series.slices.template.setAll({ strokeWidth: 2, stroke: am5.color(0xffffff) });
                series.labels.template.set('forceHidden', true);
                series.ticks.template.set('forceHidden', true);
                series.data.setAll([
                    { value: 40, label: 'Lithuania' },
                    { value: 26, label: 'Czech Rep.' },
                    { value: 20, label: 'Ireland' },
                    { value: 14, label: 'Germany' }
                ]);
                var legend = chart.children.push(am5.Legend.new(root, {
                    centerX: am5.percent(50),
                    x: am5.percent(50),
                    marginTop: 10
                }));
                legend.data.setAll(series.dataItems);
                series.appear(800, 100);
            })();

            /* ---------- 2) Bar Chart ---------- */
            (function () {
                var root = newRoot('am-2');
                var chart = root.container.children.push(am5xy.XYChart.new(root, {
                    panX: false, panY: false, wheelX: 'none', wheelY: 'none'
                }));
                var data = [
                    { cat: '2015', val: 35 }, { cat: '2016', val: 58 }, { cat: '2017', val: 47 },
                    { cat: '2018', val: 70 }, { cat: '2019', val: 55 }, { cat: '2020', val: 82 }
                ];
                var xRenderer = am5xy.AxisRendererX.new(root, { minGridDistance: 30 });
                xRenderer.grid.template.set('forceHidden', true);
                var xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
                    categoryField: 'cat', renderer: xRenderer
                }));
                xAxis.data.setAll(data);
                var yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
                    renderer: am5xy.AxisRendererY.new(root, {}),
                    numberFormat: "#'%'"
                }));
                var series = chart.series.push(am5xy.ColumnSeries.new(root, {
                    xAxis: xAxis, yAxis: yAxis, valueYField: 'val', categoryXField: 'cat',
                    tooltip: am5.Tooltip.new(root, { labelText: '{valueY}%' })
                }));
                series.columns.template.setAll({ fill: brand.primary, stroke: brand.primary, cornerRadiusTL: 4, cornerRadiusTR: 4, width: am5.percent(55) });
                series.data.setAll(data);
                series.appear(800);
                chart.appear(800, 100);
            })();

            /* ---------- 3) Smoothed Line Chart (area) ---------- */
            (function () {
                var root = newRoot('am-3');
                var chart = root.container.children.push(am5xy.XYChart.new(root, {
                    panX: false, panY: false, wheelX: 'none', wheelY: 'none'
                }));
                var data = [
                    { cat: 'Jan', val: 18 }, { cat: 'Feb', val: 26 }, { cat: 'Mar', val: 21 }, { cat: 'Apr', val: 33 },
                    { cat: 'May', val: 28 }, { cat: 'Jun', val: 40 }, { cat: 'Jul', val: 35 }, { cat: 'Aug', val: 47 }
                ];
                var xRenderer = am5xy.AxisRendererX.new(root, { minGridDistance: 30 });
                xRenderer.grid.template.set('forceHidden', true);
                var xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
                    categoryField: 'cat', renderer: xRenderer
                }));
                xAxis.data.setAll(data);
                var yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
                    renderer: am5xy.AxisRendererY.new(root, {})
                }));
                var series = chart.series.push(am5xy.SmoothedXLineSeries.new(root, {
                    xAxis: xAxis, yAxis: yAxis, valueYField: 'val', categoryXField: 'cat',
                    tooltip: am5.Tooltip.new(root, { labelText: '{valueY}' })
                }));
                series.strokes.template.setAll({ stroke: brand.cyan, strokeWidth: 3 });
                series.fills.template.setAll({ fill: brand.cyan, fillOpacity: 0.2, visible: true });
                series.bullets.push(function () {
                    return am5.Bullet.new(root, {
                        sprite: am5.Circle.new(root, { radius: 4, fill: brand.cyan, stroke: am5.color(0xffffff), strokeWidth: 2 })
                    });
                });
                series.data.setAll(data);
                series.appear(800);
                chart.appear(800, 100);
            })();

            /* ---------- 4) Angular Gauge ---------- */
            (function () {
                var root = newRoot('am-4');
                var chart = root.container.children.push(am5radar.RadarChart.new(root, {
                    panX: false, panY: false,
                    startAngle: 180, endAngle: 360
                }));
                var axisRenderer = am5radar.AxisRendererCircular.new(root, { innerRadius: -25 });
                axisRenderer.grid.template.setAll({ stroke: root.interfaceColors.get('background'), visible: true, strokeOpacity: 0.8 });
                var axis = chart.xAxes.push(am5xy.ValueAxis.new(root, {
                    maxDeviation: 0, min: 0, max: 100, strictMinMax: true, renderer: axisRenderer
                }));
                axis.get('renderer').labels.template.setAll({ forceHidden: true });
                axisRenderer.ticks.template.setAll({ forceHidden: true });

                // Coloured range up to the value
                var range = axis.createAxisRange(axis.makeDataItem({ above: true, value: 0, endValue: 75 }));
                range.get('axisFill').setAll({ visible: true, fill: brand.green, fillOpacity: 1 });
                var rangeBg = axis.createAxisRange(axis.makeDataItem({ value: 75, endValue: 100 }));
                rangeBg.get('axisFill').setAll({ visible: true, fill: am5.color(0xe5e7eb), fillOpacity: 1 });

                var clockHand = am5radar.ClockHand.new(root, { pinRadius: 12, radius: am5.percent(90), bottomWidth: 8 });
                clockHand.pin.setAll({ fill: brand.green });
                clockHand.hand.setAll({ fill: brand.green });
                var handDataItem = axis.createAxisRange(axis.makeDataItem({ value: 75 }));
                handDataItem.set('bullet', am5xy.AxisBullet.new(root, { sprite: clockHand }));

                var label = chart.radarContainer.children.push(am5.Label.new(root, {
                    centerX: am5.percent(50), centerY: am5.percent(-20),
                    fill: brand.green, fontSize: '1.8em', fontWeight: '700', text: '75%'
                }));
                chart.radarContainer.children.push(am5.Label.new(root, {
                    centerX: am5.percent(50), centerY: am5.percent(60),
                    fill: am5.color(0x6b7280), fontSize: '0.9em', text: '{{ __('Performance') }}'
                }));

                chart.appear(800, 100);
            })();

            /* ---------- 5) Line Chart (area, full width) ---------- */
            (function () {
                var root = newRoot('am-5');
                var chart = root.container.children.push(am5xy.XYChart.new(root, {
                    panX: false, panY: false, wheelX: 'none', wheelY: 'none'
                }));
                var labels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                var vals = [12, 19, 14, 23, 18, 27, 22, 31, 26, 34, 29, 38];
                var data = labels.map(function (c, i) { return { cat: c, val: vals[i] }; });
                var xRenderer = am5xy.AxisRendererX.new(root, { minGridDistance: 30 });
                xRenderer.grid.template.set('forceHidden', true);
                var xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
                    categoryField: 'cat', renderer: xRenderer
                }));
                xAxis.data.setAll(data);
                var yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
                    renderer: am5xy.AxisRendererY.new(root, {}),
                    numberFormat: "'$'#'k'"
                }));
                var series = chart.series.push(am5xy.LineSeries.new(root, {
                    xAxis: xAxis, yAxis: yAxis, valueYField: 'val', categoryXField: 'cat',
                    tooltip: am5.Tooltip.new(root, { labelText: '${valueY}k' })
                }));
                series.strokes.template.setAll({ stroke: brand.primary, strokeWidth: 3 });
                series.fills.template.setAll({ fill: brand.primary, fillOpacity: 0.18, visible: true });
                series.bullets.push(function () {
                    return am5.Bullet.new(root, {
                        sprite: am5.Circle.new(root, { radius: 4, fill: brand.primary, stroke: am5.color(0xffffff), strokeWidth: 2 })
                    });
                });
                series.data.setAll(data);
                chart.set('cursor', am5xy.XYCursor.new(root, { behavior: 'none', xAxis: xAxis }));
                series.appear(800);
                chart.appear(800, 100);
            })();

            /* ---------- 6) Bar Chart (Map card) ---------- */
            (function () {
                var root = newRoot('am-6');
                var chart = root.container.children.push(am5xy.XYChart.new(root, {
                    panX: false, panY: false, wheelX: 'none', wheelY: 'none'
                }));
                var labels = ['US','UK','DE','FR','IN','BD','JP','CN'];
                var vals = [45, 62, 53, 78, 66, 88, 74, 95];
                var data = labels.map(function (c, i) { return { cat: c, val: vals[i] }; });
                var xRenderer = am5xy.AxisRendererX.new(root, { minGridDistance: 20 });
                xRenderer.grid.template.set('forceHidden', true);
                var xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
                    categoryField: 'cat', renderer: xRenderer
                }));
                xAxis.data.setAll(data);
                var yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
                    renderer: am5xy.AxisRendererY.new(root, {}),
                    numberFormat: "#'k'"
                }));
                var series = chart.series.push(am5xy.ColumnSeries.new(root, {
                    xAxis: xAxis, yAxis: yAxis, valueYField: 'val', categoryXField: 'cat',
                    tooltip: am5.Tooltip.new(root, { labelText: '{valueY}k' })
                }));
                series.columns.template.setAll({ fill: brand.purple, stroke: brand.purple, cornerRadiusTL: 4, cornerRadiusTR: 4, width: am5.percent(55) });
                series.data.setAll(data);
                series.appear(800);
                chart.appear(800, 100);
            })();

            /* ---------- 7) Pie Chart (Map card) ---------- */
            (function () {
                var root = newRoot('am-7');
                var chart = root.container.children.push(am5percent.PieChart.new(root, {
                    layout: root.verticalLayout
                }));
                var series = chart.series.push(am5percent.PieSeries.new(root, {
                    valueField: 'value', categoryField: 'label'
                }));
                series.get('colors').set('colors', [brand.primary, brand.cyan, brand.accent]);
                series.slices.template.setAll({ strokeWidth: 2, stroke: am5.color(0xffffff) });
                series.labels.template.set('forceHidden', true);
                series.ticks.template.set('forceHidden', true);
                series.data.setAll([
                    { value: 45, label: 'Asia' },
                    { value: 30, label: 'Europe' },
                    { value: 25, label: 'America' }
                ]);
                var legend = chart.children.push(am5.Legend.new(root, {
                    centerX: am5.percent(50), x: am5.percent(50), marginTop: 10
                }));
                legend.data.setAll(series.dataItems);
                series.appear(800, 100);
            })();
        });
      }
      if (window.chartsReady) boot();
      else document.addEventListener('charts:loaded', boot);
    })();
    </script>
@endpush
