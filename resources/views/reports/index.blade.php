@extends('layouts.main')
@section('title', 'Reports')
@section('content')
    <x-page-header title="{{ __('Reports') }}" subtitle="{{ __('Analyse your business performance') }}" icon="ik ik-trending-up"
                   :breadcrumbs="['Home' => url('dashboard'), 'Reports' => null]" />

    @php
        $reports = [
            ['title' => 'Sales Report', 'desc' => 'Revenue, orders & top products over time', 'icon' => 'ik ik-shopping-cart', 'color' => 'primary', 'url' => url('reports/sales')],
            ['title' => 'Inventory Report', 'desc' => 'Stock levels, valuation & movement', 'icon' => 'ik ik-package', 'color' => 'green', 'url' => url('reports/inventory')],
            ['title' => 'Profit & Loss', 'desc' => 'Income vs expense and net margin', 'icon' => 'ik ik-bar-chart-2', 'color' => 'purple', 'url' => url('reports/profit-loss')],
            ['title' => 'Tax Summary', 'desc' => 'Collected & payable tax by period', 'icon' => 'ik ik-percent', 'color' => 'amber', 'url' => url('reports/tax')],
            ['title' => 'Customers', 'desc' => 'Acquisition, retention & lifetime value', 'icon' => 'ik ik-users', 'color' => 'cyan', 'url' => url('reports/sales')],
            ['title' => 'Expenses', 'desc' => 'Spend by category and vendor', 'icon' => 'ik ik-credit-card', 'color' => 'accent', 'url' => url('reports/profit-loss')],
        ];
        $tints = [
            'primary' => 'bg-primary-50 text-primary-600', 'green' => 'bg-green-50 text-green-600',
            'purple' => 'bg-purple-50 text-purple-600', 'amber' => 'bg-amber-50 text-amber-600',
            'cyan' => 'bg-cyan-50 text-cyan-600', 'accent' => 'bg-accent-500/10 text-accent-600',
        ];
    @endphp

    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($reports as $r)
            <a href="{{ $r['url'] }}" class="group flex items-start gap-4 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm shadow-gray-200/60 transition hover:-translate-y-0.5 hover:shadow-md">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $tints[$r['color']] }}"><i class="{{ $r['icon'] }} text-xl"></i></span>
                <div class="min-w-0 flex-1">
                    <h5 class="font-semibold text-gray-800">{{ __($r['title']) }}</h5>
                    <p class="mt-0.5 text-sm text-gray-400">{{ __($r['desc']) }}</p>
                </div>
                <i class="ik ik-arrow-right text-gray-300 transition group-hover:translate-x-0.5 group-hover:text-primary-500"></i>
            </a>
        @endforeach
    </div>
@endsection
