@extends('layouts.main')
@section('title', 'Invoice')
@section('content')
    <x-page-header
        title="{{ __('Invoice') }}"
        subtitle="{{ __('Five polished, print-ready invoice templates — switch between styles below') }}"
        icon="ik ik-file-text"
        :breadcrumbs="['Home' => url('dashboard'), 'Pages' => '#', 'Invoice' => null]"
    />

    <div x-data="{ tab: 1 }">
        {{-- Variant switcher --}}
        <div class="no-print mb-6">
            <div class="inline-flex flex-wrap gap-1 rounded-xl border border-gray-100 bg-white p-1 shadow-sm shadow-gray-200/60">
                @php
                    $variants = [
                        1 => ['label' => __('Classic'),  'icon' => 'ik ik-file-text'],
                        2 => ['label' => __('Modern'),   'icon' => 'ik ik-layers'],
                        3 => ['label' => __('Minimal'),  'icon' => 'ik ik-minus'],
                        4 => ['label' => __('Detailed'), 'icon' => 'ik ik-list'],
                        5 => ['label' => __('Receipt'),  'icon' => 'ik ik-credit-card'],
                    ];
                @endphp
                @foreach ($variants as $i => $v)
                    <button
                        type="button"
                        @click="tab = {{ $i }}"
                        :class="tab === {{ $i }} ? 'bg-primary-500 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-50'"
                        class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition"
                    >
                        <i class="{{ $v['icon'] }}"></i>
                        <span>{{ $v['label'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- ============================================================= --}}
        {{-- VARIANT 1 — CLASSIC / PROFESSIONAL                            --}}
        {{-- ============================================================= --}}
        <div x-show="tab === 1" x-transition>
            <x-card noPadding>
                <div class="p-8 md:p-10">
                    {{-- Header --}}
                    <div class="flex flex-col gap-6 border-b border-gray-100 pb-8 md:flex-row md:items-start md:justify-between">
                        <div>
                            <x-brand-logo markClass="h-11 w-11" textClass="text-xl" />
                            <div class="mt-4 text-sm leading-relaxed text-gray-500">
                                <p class="font-medium text-gray-700">Radminly Inc.</p>
                                <p>1200 Market Street, Suite 400</p>
                                <p>San Francisco, CA 94103</p>
                                <p>billing@radminly.com &middot; +1 (415) 555-0142</p>
                            </div>
                        </div>
                        <div class="md:text-right">
                            <h1 class="text-3xl font-bold tracking-tight text-gray-800">{{ __('INVOICE') }}</h1>
                            <dl class="mt-4 space-y-1 text-sm">
                                <div class="flex justify-between gap-8 md:justify-end">
                                    <dt class="text-gray-400">{{ __('Invoice #') }}</dt>
                                    <dd class="font-medium text-gray-700">INV-2026-0042</dd>
                                </div>
                                <div class="flex justify-between gap-8 md:justify-end">
                                    <dt class="text-gray-400">{{ __('Date') }}</dt>
                                    <dd class="font-medium text-gray-700">Jun 22, 2026</dd>
                                </div>
                                <div class="flex justify-between gap-8 md:justify-end">
                                    <dt class="text-gray-400">{{ __('Due Date') }}</dt>
                                    <dd class="font-medium text-gray-700">Jul 06, 2026</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    {{-- Billed to --}}
                    <div class="grid gap-8 py-8 sm:grid-cols-2">
                        <div>
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-400">{{ __('Billed To') }}</p>
                            <p class="font-medium text-gray-700">Acme Logistics LLC</p>
                            <p class="text-sm leading-relaxed text-gray-500">
                                Attn: Jordan Mathews<br>
                                88 Riverside Avenue<br>
                                Austin, TX 78701<br>
                                accounts@acmelogistics.com
                            </p>
                        </div>
                        <div class="sm:text-right">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-400">{{ __('Amount Due') }}</p>
                            <p class="text-3xl font-bold text-gray-800">$5,940.00</p>
                            <p class="mt-1 text-sm text-gray-500">{{ __('Due by Jul 06, 2026') }}</p>
                        </div>
                    </div>

                    {{-- Line items --}}
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 text-left text-xs uppercase tracking-wider text-gray-400">
                                    <th class="py-3 font-semibold">{{ __('Description') }}</th>
                                    <th class="py-3 text-center font-semibold">{{ __('Qty') }}</th>
                                    <th class="py-3 text-right font-semibold">{{ __('Unit Price') }}</th>
                                    <th class="py-3 text-right font-semibold">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-700">
                                <tr class="border-b border-gray-100">
                                    <td class="py-3.5">
                                        <p class="font-medium">{{ __('Dashboard UI Design') }}</p>
                                        <p class="text-xs text-gray-400">{{ __('Custom admin dashboard layouts') }}</p>
                                    </td>
                                    <td class="py-3.5 text-center">1</td>
                                    <td class="py-3.5 text-right">$2,400.00</td>
                                    <td class="py-3.5 text-right font-medium">$2,400.00</td>
                                </tr>
                                <tr class="border-b border-gray-100">
                                    <td class="py-3.5">
                                        <p class="font-medium">{{ __('Frontend Development') }}</p>
                                        <p class="text-xs text-gray-400">{{ __('Tailwind + Alpine components') }}</p>
                                    </td>
                                    <td class="py-3.5 text-center">24</td>
                                    <td class="py-3.5 text-right">$95.00</td>
                                    <td class="py-3.5 text-right font-medium">$2,280.00</td>
                                </tr>
                                <tr class="border-b border-gray-100">
                                    <td class="py-3.5">
                                        <p class="font-medium">{{ __('API Integration') }}</p>
                                        <p class="text-xs text-gray-400">{{ __('Third-party payment gateway') }}</p>
                                    </td>
                                    <td class="py-3.5 text-center">8</td>
                                    <td class="py-3.5 text-right">$95.00</td>
                                    <td class="py-3.5 text-right font-medium">$760.00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Summary --}}
                    <div class="mt-6 flex justify-end">
                        <div class="w-full max-w-xs space-y-2 text-sm">
                            <div class="flex justify-between text-gray-500">
                                <span>{{ __('Subtotal') }}</span>
                                <span class="font-medium text-gray-700">$5,440.00</span>
                            </div>
                            <div class="flex justify-between text-gray-500">
                                <span>{{ __('Tax (10%)') }}</span>
                                <span class="font-medium text-gray-700">$544.00</span>
                            </div>
                            <div class="flex justify-between text-gray-500">
                                <span>{{ __('Shipping') }}</span>
                                <span class="font-medium text-gray-700">$0.00</span>
                            </div>
                            <div class="mt-2 flex justify-between border-t border-gray-200 pt-3 text-base font-bold text-gray-800">
                                <span>{{ __('Total') }}</span>
                                <span>$5,940.00</span>
                            </div>
                        </div>
                    </div>

                    {{-- Notes & terms --}}
                    <div class="mt-8 grid gap-6 border-t border-gray-100 pt-6 text-sm sm:grid-cols-2">
                        <div>
                            <p class="mb-1 font-semibold text-gray-700">{{ __('Notes') }}</p>
                            <p class="text-gray-500">{{ __('Thank you for your business. Please reference the invoice number with your payment.') }}</p>
                        </div>
                        <div>
                            <p class="mb-1 font-semibold text-gray-700">{{ __('Payment Terms') }}</p>
                            <p class="text-gray-500">{{ __('Net 14 days. Bank transfer to Radminly Inc., Acct #0042-885-1190.') }}</p>
                        </div>
                    </div>
                </div>
            </x-card>

            <div class="no-print mt-5 flex flex-wrap justify-end gap-3">
                <x-button variant="outline" @click="window.print()"><i class="ik ik-printer"></i> {{ __('Print') }}</x-button>
                <x-button variant="outline"><i class="ik ik-download"></i> {{ __('Download PDF') }}</x-button>
                <x-button variant="primary"><i class="ik ik-send"></i> {{ __('Send Invoice') }}</x-button>
            </div>
        </div>

        {{-- ============================================================= --}}
        {{-- VARIANT 2 — MODERN GRADIENT                                   --}}
        {{-- ============================================================= --}}
        <div x-show="tab === 2" x-transition>
            <x-card noPadding>
                <div class="overflow-hidden rounded-2xl">
                    {{-- Gradient header band --}}
                    <div class="bg-gradient-to-r from-primary-500 to-primary-600 px-8 py-10 text-white md:px-10">
                        <div class="flex flex-col gap-6 md:flex-row md:items-start md:justify-between">
                            <div>
                                <x-brand-logo markClass="h-12 w-12" textClass="text-xl text-white" class="text-white [&_.text-primary-500]:text-white" />
                                <p class="mt-4 text-sm text-white/80">
                                    1200 Market Street, Suite 400<br>
                                    San Francisco, CA 94103
                                </p>
                            </div>
                            <div class="md:text-right">
                                <h1 class="text-4xl font-extrabold tracking-tight">{{ __('INVOICE') }}</h1>
                                <p class="mt-2 text-sm text-white/80">INV-2026-0042 &middot; Jun 22, 2026</p>
                                <div class="mt-5 rounded-xl bg-white/15 px-5 py-3 backdrop-blur">
                                    <p class="text-xs uppercase tracking-wider text-white/70">{{ __('Total Due') }}</p>
                                    <p class="text-3xl font-bold">$5,940.00</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-8 md:p-10">
                        {{-- Billed to --}}
                        <div class="mb-8 flex flex-col gap-6 sm:flex-row sm:justify-between">
                            <div>
                                <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-primary-500">{{ __('Bill To') }}</p>
                                <p class="font-semibold text-gray-800">Acme Logistics LLC</p>
                                <p class="text-sm text-gray-500">Austin, TX 78701<br>accounts@acmelogistics.com</p>
                            </div>
                            <div class="sm:text-right">
                                <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-primary-500">{{ __('Due Date') }}</p>
                                <p class="font-semibold text-gray-800">Jul 06, 2026</p>
                                <p class="text-sm text-gray-500">{{ __('Net 14 days') }}</p>
                            </div>
                        </div>

                        {{-- Items --}}
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-primary-50 text-left text-xs uppercase tracking-wider text-primary-600">
                                        <th class="rounded-l-lg px-4 py-3 font-semibold">{{ __('Item') }}</th>
                                        <th class="px-4 py-3 text-center font-semibold">{{ __('Qty') }}</th>
                                        <th class="px-4 py-3 text-right font-semibold">{{ __('Price') }}</th>
                                        <th class="rounded-r-lg px-4 py-3 text-right font-semibold">{{ __('Amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-700">
                                    <tr class="border-b border-gray-100">
                                        <td class="px-4 py-4 font-medium">{{ __('Dashboard UI Design') }}</td>
                                        <td class="px-4 py-4 text-center">1</td>
                                        <td class="px-4 py-4 text-right">$2,400.00</td>
                                        <td class="px-4 py-4 text-right font-medium">$2,400.00</td>
                                    </tr>
                                    <tr class="border-b border-gray-100">
                                        <td class="px-4 py-4 font-medium">{{ __('Frontend Development') }}</td>
                                        <td class="px-4 py-4 text-center">24</td>
                                        <td class="px-4 py-4 text-right">$95.00</td>
                                        <td class="px-4 py-4 text-right font-medium">$2,280.00</td>
                                    </tr>
                                    <tr class="border-b border-gray-100">
                                        <td class="px-4 py-4 font-medium">{{ __('API Integration') }}</td>
                                        <td class="px-4 py-4 text-center">8</td>
                                        <td class="px-4 py-4 text-right">$95.00</td>
                                        <td class="px-4 py-4 text-right font-medium">$760.00</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        {{-- Summary --}}
                        <div class="mt-6 flex justify-end">
                            <div class="w-full max-w-xs space-y-2 text-sm">
                                <div class="flex justify-between text-gray-500">
                                    <span>{{ __('Subtotal') }}</span><span class="font-medium text-gray-700">$5,440.00</span>
                                </div>
                                <div class="flex justify-between text-gray-500">
                                    <span>{{ __('Tax (10%)') }}</span><span class="font-medium text-gray-700">$544.00</span>
                                </div>
                                <div class="mt-1 flex items-center justify-between rounded-lg bg-gradient-to-r from-primary-500 to-primary-600 px-4 py-3 text-base font-bold text-white">
                                    <span>{{ __('Total Due') }}</span><span>$5,940.00</span>
                                </div>
                            </div>
                        </div>

                        <p class="mt-8 border-t border-gray-100 pt-6 text-sm text-gray-500">
                            {{ __('Payment is due within 14 days. We appreciate your business!') }}
                        </p>
                    </div>
                </div>
            </x-card>

            <div class="no-print mt-5 flex flex-wrap justify-end gap-3">
                <x-button variant="outline" @click="window.print()"><i class="ik ik-printer"></i> {{ __('Print') }}</x-button>
                <x-button variant="outline"><i class="ik ik-download"></i> {{ __('Download PDF') }}</x-button>
                <x-button variant="primary"><i class="ik ik-send"></i> {{ __('Send Invoice') }}</x-button>
            </div>
        </div>

        {{-- ============================================================= --}}
        {{-- VARIANT 3 — MINIMAL                                           --}}
        {{-- ============================================================= --}}
        <div x-show="tab === 3" x-transition>
            <x-card noPadding>
                <div class="p-10 md:p-14">
                    {{-- Header --}}
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-[0.25em] text-gray-400">{{ __('Invoice') }}</p>
                            <p class="mt-1 text-2xl font-light text-gray-800">INV-2026-0042</p>
                        </div>
                        <x-brand-logo :wordmark="false" markClass="h-10 w-10" />
                    </div>

                    <hr class="my-10 border-gray-100">

                    {{-- Meta grid --}}
                    <div class="grid grid-cols-2 gap-8 sm:grid-cols-4">
                        <div>
                            <p class="text-[11px] uppercase tracking-widest text-gray-400">{{ __('From') }}</p>
                            <p class="mt-1.5 text-sm text-gray-700">Radminly Inc.<br>San Francisco, CA</p>
                        </div>
                        <div>
                            <p class="text-[11px] uppercase tracking-widest text-gray-400">{{ __('To') }}</p>
                            <p class="mt-1.5 text-sm text-gray-700">Acme Logistics<br>Austin, TX</p>
                        </div>
                        <div>
                            <p class="text-[11px] uppercase tracking-widest text-gray-400">{{ __('Issued') }}</p>
                            <p class="mt-1.5 text-sm text-gray-700">Jun 22, 2026</p>
                        </div>
                        <div>
                            <p class="text-[11px] uppercase tracking-widest text-gray-400">{{ __('Due') }}</p>
                            <p class="mt-1.5 text-sm text-gray-700">Jul 06, 2026</p>
                        </div>
                    </div>

                    <hr class="my-10 border-gray-100">

                    {{-- Items --}}
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-[11px] uppercase tracking-widest text-gray-400">
                                <th class="pb-4 font-normal">{{ __('Description') }}</th>
                                <th class="pb-4 text-right font-normal">{{ __('Qty') }}</th>
                                <th class="pb-4 text-right font-normal">{{ __('Price') }}</th>
                                <th class="pb-4 text-right font-normal">{{ __('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700">
                            <tr class="border-t border-gray-100">
                                <td class="py-4">{{ __('Dashboard UI Design') }}</td>
                                <td class="py-4 text-right text-gray-400">1</td>
                                <td class="py-4 text-right text-gray-400">$2,400.00</td>
                                <td class="py-4 text-right">$2,400.00</td>
                            </tr>
                            <tr class="border-t border-gray-100">
                                <td class="py-4">{{ __('Frontend Development') }}</td>
                                <td class="py-4 text-right text-gray-400">24</td>
                                <td class="py-4 text-right text-gray-400">$95.00</td>
                                <td class="py-4 text-right">$2,280.00</td>
                            </tr>
                            <tr class="border-t border-gray-100">
                                <td class="py-4">{{ __('API Integration') }}</td>
                                <td class="py-4 text-right text-gray-400">8</td>
                                <td class="py-4 text-right text-gray-400">$95.00</td>
                                <td class="py-4 text-right">$760.00</td>
                            </tr>
                        </tbody>
                    </table>

                    <hr class="my-8 border-gray-100">

                    {{-- Total --}}
                    <div class="flex justify-end">
                        <div class="w-full max-w-xs space-y-3 text-sm">
                            <div class="flex justify-between text-gray-400">
                                <span class="uppercase tracking-widest text-[11px]">{{ __('Subtotal') }}</span>
                                <span class="text-gray-700">$5,440.00</span>
                            </div>
                            <div class="flex justify-between text-gray-400">
                                <span class="uppercase tracking-widest text-[11px]">{{ __('Tax') }}</span>
                                <span class="text-gray-700">$544.00</span>
                            </div>
                            <div class="flex items-baseline justify-between border-t border-gray-200 pt-3">
                                <span class="text-[11px] uppercase tracking-widest text-gray-400">{{ __('Total') }}</span>
                                <span class="text-2xl font-light text-gray-800">$5,940.00</span>
                            </div>
                        </div>
                    </div>

                    <p class="mt-12 text-center text-[11px] uppercase tracking-[0.2em] text-gray-300">
                        {{ __('Thank you') }}
                    </p>
                </div>
            </x-card>

            <div class="no-print mt-5 flex flex-wrap justify-end gap-3">
                <x-button variant="ghost" @click="window.print()"><i class="ik ik-printer"></i> {{ __('Print') }}</x-button>
                <x-button variant="ghost"><i class="ik ik-download"></i> {{ __('Download PDF') }}</x-button>
                <x-button variant="primary"><i class="ik ik-send"></i> {{ __('Send') }}</x-button>
            </div>
        </div>

        {{-- ============================================================= --}}
        {{-- VARIANT 4 — DETAILED / ITEMIZED                               --}}
        {{-- ============================================================= --}}
        <div x-show="tab === 4" x-transition>
            <x-card noPadding>
                <div class="p-8 md:p-10">
                    {{-- Header --}}
                    <div class="flex flex-col gap-6 border-b border-gray-100 pb-6 md:flex-row md:items-start md:justify-between">
                        <div>
                            <x-brand-logo markClass="h-11 w-11" textClass="text-xl" />
                            <p class="mt-3 text-sm text-gray-500">Radminly Inc. &middot; billing@radminly.com</p>
                        </div>
                        <div class="md:text-right">
                            <div class="mb-3 flex items-center gap-2 md:justify-end">
                                <h1 class="text-2xl font-bold text-gray-800">{{ __('INVOICE') }}</h1>
                                <x-badge color="danger">{{ __('Overdue') }}</x-badge>
                            </div>
                            <p class="text-sm text-gray-500">INV-2026-0042</p>
                            <p class="text-sm text-gray-500">{{ __('Issued') }}: Jun 22, 2026</p>
                            <p class="text-sm text-gray-500">{{ __('Due') }}: Jul 06, 2026</p>
                        </div>
                    </div>

                    {{-- Parties --}}
                    <div class="grid gap-6 py-6 sm:grid-cols-3">
                        <div>
                            <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-gray-400">{{ __('Bill To') }}</p>
                            <p class="font-medium text-gray-700">Acme Logistics LLC</p>
                            <p class="text-sm text-gray-500">Austin, TX 78701</p>
                        </div>
                        <div>
                            <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-gray-400">{{ __('Project') }}</p>
                            <p class="font-medium text-gray-700">{{ __('Admin Platform v4') }}</p>
                            <p class="text-sm text-gray-500">{{ __('PO #ACM-7741') }}</p>
                        </div>
                        <div>
                            <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-gray-400">{{ __('Status') }}</p>
                            <x-badge color="danger">{{ __('Unpaid') }}</x-badge>
                            <p class="mt-1 text-sm text-gray-500">{{ __('14 days overdue') }}</p>
                        </div>
                    </div>

                    {{-- Detailed items --}}
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-y border-gray-200 bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                                    <th class="px-3 py-3 font-semibold">{{ __('Item') }}</th>
                                    <th class="px-3 py-3 font-semibold">{{ __('Description') }}</th>
                                    <th class="px-3 py-3 text-right font-semibold">{{ __('Hrs/Qty') }}</th>
                                    <th class="px-3 py-3 text-right font-semibold">{{ __('Rate') }}</th>
                                    <th class="px-3 py-3 text-right font-semibold">{{ __('Tax %') }}</th>
                                    <th class="px-3 py-3 text-right font-semibold">{{ __('Amount') }}</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-700">
                                <tr class="border-b border-gray-100">
                                    <td class="px-3 py-3.5 font-medium">{{ __('UI Design') }}</td>
                                    <td class="px-3 py-3.5 text-gray-500">{{ __('Dashboard & components') }}</td>
                                    <td class="px-3 py-3.5 text-right">1.0</td>
                                    <td class="px-3 py-3.5 text-right">$2,400.00</td>
                                    <td class="px-3 py-3.5 text-right">10%</td>
                                    <td class="px-3 py-3.5 text-right font-medium">$2,400.00</td>
                                </tr>
                                <tr class="border-b border-gray-100">
                                    <td class="px-3 py-3.5 font-medium">{{ __('Frontend Dev') }}</td>
                                    <td class="px-3 py-3.5 text-gray-500">{{ __('Tailwind + Alpine build') }}</td>
                                    <td class="px-3 py-3.5 text-right">24.0</td>
                                    <td class="px-3 py-3.5 text-right">$95.00</td>
                                    <td class="px-3 py-3.5 text-right">10%</td>
                                    <td class="px-3 py-3.5 text-right font-medium">$2,280.00</td>
                                </tr>
                                <tr class="border-b border-gray-100">
                                    <td class="px-3 py-3.5 font-medium">{{ __('API Integration') }}</td>
                                    <td class="px-3 py-3.5 text-gray-500">{{ __('Payment gateway') }}</td>
                                    <td class="px-3 py-3.5 text-right">8.0</td>
                                    <td class="px-3 py-3.5 text-right">$95.00</td>
                                    <td class="px-3 py-3.5 text-right">10%</td>
                                    <td class="px-3 py-3.5 text-right font-medium">$760.00</td>
                                </tr>
                                <tr class="border-b border-gray-100">
                                    <td class="px-3 py-3.5 font-medium">{{ __('QA & Testing') }}</td>
                                    <td class="px-3 py-3.5 text-gray-500">{{ __('Cross-browser checks') }}</td>
                                    <td class="px-3 py-3.5 text-right">6.0</td>
                                    <td class="px-3 py-3.5 text-right">$80.00</td>
                                    <td class="px-3 py-3.5 text-right">5%</td>
                                    <td class="px-3 py-3.5 text-right font-medium">$480.00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Summary with discount & multiple taxes --}}
                    <div class="mt-6 flex justify-end">
                        <div class="w-full max-w-sm space-y-2 text-sm">
                            <div class="flex justify-between text-gray-500">
                                <span>{{ __('Subtotal') }}</span><span class="font-medium text-gray-700">$5,920.00</span>
                            </div>
                            <div class="flex justify-between text-gray-500">
                                <span>{{ __('Discount (5%)') }}</span><span class="font-medium text-green-600">-$296.00</span>
                            </div>
                            <div class="flex justify-between text-gray-500">
                                <span>{{ __('State Tax (10%)') }}</span><span class="font-medium text-gray-700">$544.00</span>
                            </div>
                            <div class="flex justify-between text-gray-500">
                                <span>{{ __('City Tax (5%)') }}</span><span class="font-medium text-gray-700">$24.00</span>
                            </div>
                            <div class="mt-2 flex justify-between border-t border-gray-200 pt-3 text-base font-bold text-gray-800">
                                <span>{{ __('Total Due') }}</span><span>$6,192.00</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 rounded-lg border border-accent-500/20 bg-accent-500/5 p-4 text-sm text-accent-600">
                        <i class="ik ik-alert-circle"></i>
                        {{ __('This invoice is overdue. A late fee of 1.5% per month may apply after the due date.') }}
                    </div>
                </div>
            </x-card>

            <div class="no-print mt-5 flex flex-wrap justify-end gap-3">
                <x-button variant="outline" @click="window.print()"><i class="ik ik-printer"></i> {{ __('Print') }}</x-button>
                <x-button variant="outline"><i class="ik ik-download"></i> {{ __('Download PDF') }}</x-button>
                <x-button variant="success"><i class="ik ik-mail"></i> {{ __('Send Reminder') }}</x-button>
            </div>
        </div>

        {{-- ============================================================= --}}
        {{-- VARIANT 5 — RECEIPT / COMPACT                                 --}}
        {{-- ============================================================= --}}
        <div x-show="tab === 5" x-transition>
            <div class="flex justify-center">
                <div class="w-full max-w-sm">
                    <div class="relative rounded-2xl border border-gray-100 bg-white p-7 font-mono text-gray-700 shadow-sm shadow-gray-200/60">
                        {{-- PAID stamp --}}
                        <div class="pointer-events-none absolute right-5 top-16 -rotate-12 rounded border-2 border-green-500/70 px-3 py-1 text-lg font-bold uppercase tracking-widest text-green-500/80">
                            {{ __('Paid') }}
                        </div>

                        {{-- Header --}}
                        <div class="text-center">
                            <div class="flex justify-center">
                                <x-brand-logo :wordmark="false" markClass="h-10 w-10" />
                            </div>
                            <p class="mt-2 text-sm font-bold tracking-tight">RADMINLY INC.</p>
                            <p class="text-xs text-gray-400">1200 Market St, San Francisco</p>
                            <p class="text-xs text-gray-400">+1 (415) 555-0142</p>
                        </div>

                        <div class="my-4 border-t border-dashed border-gray-300"></div>

                        <div class="space-y-1 text-xs">
                            <div class="flex justify-between"><span class="text-gray-400">{{ __('Receipt #') }}</span><span>INV-2026-0042</span></div>
                            <div class="flex justify-between"><span class="text-gray-400">{{ __('Date') }}</span><span>Jun 22, 2026 14:08</span></div>
                            <div class="flex justify-between"><span class="text-gray-400">{{ __('Cashier') }}</span><span>A. Mathews</span></div>
                        </div>

                        <div class="my-4 border-t border-dashed border-gray-300"></div>

                        {{-- Items --}}
                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between">
                                <span>{{ __('Dashboard UI') }} <span class="text-gray-400">x1</span></span>
                                <span>$2,400.00</span>
                            </div>
                            <div class="flex justify-between">
                                <span>{{ __('Frontend Dev') }} <span class="text-gray-400">x24</span></span>
                                <span>$2,280.00</span>
                            </div>
                            <div class="flex justify-between">
                                <span>{{ __('API Integration') }} <span class="text-gray-400">x8</span></span>
                                <span>$760.00</span>
                            </div>
                        </div>

                        <div class="my-4 border-t border-dashed border-gray-300"></div>

                        <div class="space-y-1 text-xs">
                            <div class="flex justify-between text-gray-400"><span>{{ __('Subtotal') }}</span><span>$5,440.00</span></div>
                            <div class="flex justify-between text-gray-400"><span>{{ __('Tax (10%)') }}</span><span>$544.00</span></div>
                            <div class="flex justify-between pt-1 text-sm font-bold text-gray-800"><span>{{ __('TOTAL') }}</span><span>$5,940.00</span></div>
                        </div>

                        <div class="my-4 border-t border-dashed border-gray-300"></div>

                        <div class="space-y-1 text-xs text-gray-400">
                            <div class="flex justify-between"><span>{{ __('Paid — Visa') }}</span><span>**** 4242</span></div>
                            <div class="flex justify-between"><span>{{ __('Approval') }}</span><span>#88A17C</span></div>
                        </div>

                        <div class="my-4 border-t border-dashed border-gray-300"></div>

                        <p class="text-center text-[11px] uppercase tracking-widest text-gray-400">{{ __('Thank you for your purchase') }}</p>
                        <p class="mt-3 text-center text-[10px] tracking-[0.3em] text-gray-300">|| ||| | || ||| | |||| || |</p>
                    </div>

                    <div class="no-print mt-5 flex justify-center gap-3">
                        <x-button variant="outline" size="sm" @click="window.print()"><i class="ik ik-printer"></i> {{ __('Print') }}</x-button>
                        <x-button variant="outline" size="sm"><i class="ik ik-download"></i> {{ __('Save') }}</x-button>
                        <x-button variant="primary" size="sm"><i class="ik ik-mail"></i> {{ __('Email') }}</x-button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
