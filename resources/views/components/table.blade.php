@props([
    'paginator' => null,       // Laravel paginator → real footer + pagination
    'title' => null,           // optional heading
    'total' => null,           // static "of X" total (when no paginator)
    'from' => null,            // static "showing X"
    'to' => null,              // static "to Y"
    'staticPages' => 0,        // render a static pager with N pages (demo tables)
    'perPageOptions' => [10, 25, 50, 100],
    'perPage' => 10,           // default page size for static tables
    'search' => false,         // show the search box
    'searchName' => 'search',
    'searchPlaceholder' => 'Search...',
    'searchAction' => null,
    'exports' => true,         // Copy / CSV / Excel / PDF buttons
    'print' => true,           // Print button
    'addUrl' => null,
    'addLabel' => 'Add New',
    'noTools' => false,        // hide the right-side toolbar cluster
    'selectable' => false,     // enable row selection + bulk action bar
])

@php
    $serverMode = (bool) $paginator;
    $currentPerPage = $serverMode ? (int) $paginator->perPage() : (int) $perPage;

    if ($serverMode) {
        $fFrom = $paginator->firstItem();
        $fTo = $paginator->lastItem();
        $fTotal = $paginator->total();
    } else {
        $fFrom = $from;
        $fTo = $to;
        $fTotal = $total;
    }

    $exportBtn = 'inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-50';
    $selectCls = 'rounded-lg border border-gray-200 bg-white py-1.5 pl-2.5 pr-7 text-sm text-gray-700 outline-none transition focus:border-primary-400 focus:ring-2 focus:ring-primary-100';
@endphp

<div x-data="tableTools({{ $serverMode ? 0 : $currentPerPage }}, {{ $serverMode ? 'false' : 'true' }})"
     {{ $attributes->class(['rounded-2xl border border-gray-100 bg-white shadow-sm shadow-gray-200/60']) }}>

    {{-- Toolbar: title + "Show N entries" (left) · search + exports + print + add (right) --}}
    <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex flex-wrap items-center gap-3">
            @if ($title)<h5 class="font-semibold text-gray-700">{{ $title }}</h5>@endif
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <span>{{ __('Show') }}</span>
                @if ($serverMode)
                    <select class="{{ $selectCls }}"
                            onchange="const u=new URL(location);u.searchParams.set('per_page',this.value);u.searchParams.delete('page');location=u">
                        @foreach ($perPageOptions as $opt)
                            <option value="{{ $opt }}" @selected($currentPerPage === (int) $opt)>{{ $opt }}</option>
                        @endforeach
                    </select>
                @else
                    <select class="{{ $selectCls }}" @change="setPerPage($event.target.value)">
                        @foreach ($perPageOptions as $opt)
                            <option value="{{ $opt }}" @selected($currentPerPage === (int) $opt)>{{ $opt }}</option>
                        @endforeach
                    </select>
                @endif
                <span>{{ __('entries') }}</span>
            </div>
        </div>

        @unless ($noTools)
            <div class="flex flex-wrap items-center gap-2">
                @if ($search)
                    <form method="GET" action="{{ $searchAction ?? url()->current() }}" class="relative">
                        <i class="ik ik-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" name="{{ $searchName }}" value="{{ request($searchName) }}" placeholder="{{ $searchPlaceholder }}"
                               class="w-44 rounded-lg border border-gray-200 bg-white py-2 pl-9 pr-3 text-sm outline-none transition focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                    </form>
                @endif

                @if ($exports)
                    <div class="flex items-center gap-1.5">
                        <button type="button" @click="copy()" class="{{ $exportBtn }}" title="{{ __('Copy') }}"><i class="ik ik-copy"></i><span class="hidden sm:inline">{{ __('Copy') }}</span></button>
                        <button type="button" @click="csv()" class="{{ $exportBtn }}" title="{{ __('CSV') }}"><i class="ik ik-file-text"></i><span class="hidden sm:inline">{{ __('CSV') }}</span></button>
                        <button type="button" @click="excel()" class="{{ $exportBtn }}" title="{{ __('Excel') }}"><i class="ik ik-grid"></i><span class="hidden sm:inline">{{ __('Excel') }}</span></button>
                        <button type="button" @click="pdf()" class="{{ $exportBtn }}" title="{{ __('PDF') }}"><i class="ik ik-file"></i><span class="hidden sm:inline">{{ __('PDF') }}</span></button>
                    </div>
                @endif

                @if ($print)
                    <button type="button" @click="printTable()" class="{{ $exportBtn }}" title="{{ __('Print') }}"><i class="ik ik-printer"></i><span class="hidden sm:inline">{{ __('Print') }}</span></button>
                @endif

                {{ $actions ?? '' }}

                @if ($addUrl)
                    <a href="{{ $addUrl }}" class="inline-flex items-center gap-1.5 rounded-lg bg-primary-500 px-3 py-2 text-sm font-medium text-white transition hover:bg-primary-600">
                        <i class="ik ik-plus"></i>{{ $addLabel }}
                    </a>
                @endif
            </div>
        @endunless
    </div>

    {{-- Optional secondary filter row --}}
    @isset($filters)
        <div class="flex flex-wrap items-center gap-2 border-b border-gray-100 bg-gray-50/60 px-5 py-3">
            {{ $filters }}
        </div>
    @endisset

    {{-- Bulk action bar (appears when rows are selected) --}}
    @if ($selectable)
        <div x-show="selectedCount > 0" x-cloak
             class="flex flex-wrap items-center gap-3 border-b border-primary-100 bg-primary-50 px-5 py-3">
            <span class="text-sm font-medium text-primary-700"><span x-text="selectedCount"></span> {{ __('selected') }}</span>
            <button @click="clearSel()" class="text-sm text-gray-500 transition hover:text-gray-700">{{ __('Clear') }}</button>
            <div class="ml-auto flex flex-wrap items-center gap-2">
                {{ $bulkActions ?? '' }}
                <button @click="exportSelected()" class="{{ $exportBtn }}"><i class="ik ik-download"></i> {{ __('Export selected') }}</button>
            </div>
        </div>
    @endif

    {{-- Table --}}
    <div class="overflow-x-auto" x-ref="region">
        {{ $slot }}
    </div>

    {{-- Footer: showing X to Y of Z (left) · pagination (right) --}}
    <div class="flex flex-col gap-3 border-t border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-gray-500">
            @if ($serverMode)
                @if ($fTotal > 0)
                    {{ __('Showing') }} <span class="font-medium text-gray-700">{{ $fFrom }}</span>
                    {{ __('to') }} <span class="font-medium text-gray-700">{{ $fTo }}</span>
                    {{ __('of') }} <span class="font-medium text-gray-700">{{ number_format($fTotal) }}</span> {{ __('entries') }}
                @else
                    {{ __('No entries to show') }}
                @endif
            @else
                {{ __('Showing') }} <span class="font-medium text-gray-700" x-text="total ? 1 : 0"></span>
                {{ __('to') }} <span class="font-medium text-gray-700" x-text="visible"></span>
                {{ __('of') }} <span class="font-medium text-gray-700" x-text="total"></span> {{ __('entries') }}
            @endif
        </p>

        <div>
            @if ($serverMode)
                {!! $paginator->links('pagination.radmin') !!}
            @elseif ($staticPages > 1)
                <nav class="flex items-center gap-1">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-300"><i class="ik ik-chevron-left"></i></span>
                    @for ($i = 1; $i <= min($staticPages, 4); $i++)
                        <span @class([
                            'flex h-8 min-w-8 items-center justify-center rounded-lg px-2 text-sm',
                            'bg-primary-500 font-medium text-white shadow-sm' => $i === 1,
                            'border border-gray-200 text-gray-600 hover:bg-gray-50' => $i !== 1,
                        ])>{{ $i }}</span>
                    @endfor
                    <a href="#" class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50"><i class="ik ik-chevron-right"></i></a>
                </nav>
            @endif
        </div>
    </div>
</div>

@once
    @push('script')
        <script>
            function tableTools(perPage = 0, clientLimit = false) {
                return {
                    perPage: perPage,
                    clientLimit: clientLimit,
                    visible: 0,
                    total: 0,
                    init() {
                        if (this.clientLimit) {
                            this.$nextTick(() => this.applyLimit());
                        }
                    },
                    _bodyRows() {
                        const t = this.$refs.region?.querySelector('table');
                        return t ? [...t.querySelectorAll('tbody tr')] : [];
                    },
                    applyLimit() {
                        const rows = this._bodyRows();
                        this.total = rows.length;
                        let shown = 0;
                        rows.forEach((r, i) => {
                            const show = !this.perPage || i < this.perPage;
                            r.style.display = show ? '' : 'none';
                            if (show) shown++;
                        });
                        this.visible = shown;
                    },
                    setPerPage(v) {
                        this.perPage = parseInt(v) || 0;
                        this.applyLimit();
                    },
                    // ---- row selection ----
                    selectedCount: 0,
                    _checks() {
                        return this.$refs.region ? [...this.$refs.region.querySelectorAll('.row-select')] : [];
                    },
                    toggleAll(e) {
                        this._checks().forEach((c) => { if (c.offsetParent !== null) c.checked = e.target.checked; });
                        this.sync();
                    },
                    sync() {
                        this.selectedCount = this._checks().filter((c) => c.checked).length;
                    },
                    clearSel() {
                        this._checks().forEach((c) => (c.checked = false));
                        const all = this.$refs.region?.parentElement.querySelector('.row-select-all');
                        if (all) all.checked = false;
                        this.sync();
                    },
                    exportSelected() {
                        const checked = this._checks().filter((c) => c.checked);
                        if (!checked.length) return;
                        const rows = checked.map((c) => {
                            const tr = c.closest('tr');
                            return [...tr.querySelectorAll('th,td')]
                                .filter((cell) => !cell.dataset.noExport)
                                .map((cell) => '"' + cell.innerText.replace(/\s+/g, ' ').trim().replace(/"/g, '""') + '"')
                                .join(',');
                        });
                        const head = this.$refs.region?.querySelector('thead tr');
                        const header = head ? [...head.querySelectorAll('th')].filter((cell) => !cell.dataset.noExport)
                            .map((cell) => '"' + cell.innerText.trim() + '"').join(',') : '';
                        this._download('selected.csv', [header, ...rows].join('\n'), 'text/csv;charset=utf-8;');
                    },
                    // ---- export / print (visible rows only) ----
                    _matrix() {
                        const t = this.$refs.region?.querySelector('table');
                        if (!t) return [];
                        return [...t.querySelectorAll('tr')]
                            .filter(r => r.offsetParent !== null)
                            .map(r => [...r.querySelectorAll('th,td')]
                                .filter(c => !c.dataset.noExport)
                                .map(c => c.innerText.replace(/\s+/g, ' ').trim()));
                    },
                    _csv() {
                        return this._matrix().map(r => r.map(c => '"' + c.replace(/"/g, '""') + '"').join(',')).join('\n');
                    },
                    _download(name, content, type) {
                        const a = document.createElement('a');
                        a.href = URL.createObjectURL(new Blob(["﻿" + content], { type }));
                        a.download = name;
                        a.click();
                        URL.revokeObjectURL(a.href);
                    },
                    copy() { navigator.clipboard?.writeText(this._matrix().map(r => r.join('\t')).join('\n')); },
                    csv() { this._download('export.csv', this._csv(), 'text/csv;charset=utf-8;'); },
                    excel() { this._download('export.xls', this._csv(), 'application/vnd.ms-excel;charset=utf-8;'); },
                    pdf() { window.print(); },
                    printTable() { window.print(); },
                };
            }
        </script>
    @endpush
@endonce
