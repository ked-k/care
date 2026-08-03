@props(['title' => '', 'subtitle' => null, 'icon' => 'ik ik-trending-up'])

<x-page-header :title="$title" :subtitle="$subtitle" :icon="$icon"
               :breadcrumbs="['Home' => url('dashboard'), 'Reports' => url('reports'), $title => null]" />

{{-- Filter bar --}}
<x-card class="mb-5" x-data="{ from: '', to: '' }">
    <div class="flex flex-wrap items-end gap-3">
        <div>
            <label class="mb-1.5 block text-xs font-medium text-gray-500">{{ __('From') }}</label>
            <input type="date" x-model="from" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-gray-500">{{ __('To') }}</label>
            <input type="date" x-model="to" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
        </div>
        <div>
            <label class="mb-1.5 block text-xs font-medium text-gray-500">{{ __('Group by') }}</label>
            <select class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                <option>{{ __('Day') }}</option>
                <option selected>{{ __('Week') }}</option>
                <option>{{ __('Month') }}</option>
                <option>{{ __('Quarter') }}</option>
            </select>
        </div>
        {{-- quick ranges --}}
        <div class="flex items-center gap-1.5">
            @foreach (['Today', 'This week', 'This month', 'This year'] as $range)
                <button type="button" class="rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-50">{{ __($range) }}</button>
            @endforeach
        </div>
        <div class="ml-auto flex items-center gap-2">
            <x-button variant="primary"><i class="ik ik-filter"></i> {{ __('Apply') }}</x-button>
            <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50"><i class="ik ik-download"></i> {{ __('Export') }}</button>
        </div>
    </div>
</x-card>

{{ $slot }}
