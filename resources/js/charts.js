/*
 | Charting bundle — loaded ONLY on the chart demo pages via
 |   @push('head') @vite('resources/js/charts.js') @endpush
 |
 | Bundles the libraries locally (no CDN) and exposes them on `window` so the
 | per-page init scripts can use them. Because this is a deferred ES module, a
 | page's inline init may run first — so we fire a `charts:loaded` event (and
 | set window.chartsReady) for init code to wait on:
 |
 |   function boot() { ... use window.ApexCharts / window.Chartist / window.am5 ... }
 |   window.chartsReady ? boot() : document.addEventListener('charts:loaded', boot);
 */

import Chartist from 'chartist';
import 'chartist/dist/chartist.min.css';

import * as am5 from '@amcharts/amcharts5';
import * as am5xy from '@amcharts/amcharts5/xy';
import * as am5percent from '@amcharts/amcharts5/percent';
import * as am5radar from '@amcharts/amcharts5/radar';
import am5themes_Animated from '@amcharts/amcharts5/themes/Animated';

import ApexCharts from 'apexcharts';

window.Chartist = Chartist;
window.am5 = am5;
window.am5xy = am5xy;
window.am5percent = am5percent;
window.am5radar = am5radar;
window.am5themes_Animated = am5themes_Animated;
window.ApexCharts = ApexCharts;

/*
 | Global ApexCharts defaults — a mid slate (#94a3b8) for all chart text so it
 | stays readable on BOTH light cards and dark cards (the libs otherwise render
 | near-black labels that vanish in dark mode). Applies to every ApexCharts
 | instance unless a chart explicitly overrides it.
 */
window.Apex = {
    chart: { foreColor: '#94a3b8', fontFamily: 'inherit', toolbar: { show: false } },
    grid: { borderColor: 'rgba(148,163,184,0.18)' },
    xaxis: { axisBorder: { color: 'rgba(148,163,184,0.25)' }, axisTicks: { color: 'rgba(148,163,184,0.25)' } },
    legend: { labels: { colors: '#94a3b8' } },
    tooltip: { theme: 'dark' },
};

window.chartsReady = true;
document.dispatchEvent(new CustomEvent('charts:loaded'));
