<div x-data x-on:toast.window="$store.toast.push($event.detail.message, $event.detail.type)">
    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('family.portal') }}" wire:navigate class="text-gray-400 hover:text-gray-600">
            <i class="ik ik-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-xl font-semibold text-gray-800">{{ $serviceUser->name }}</h1>
            <p class="text-sm text-gray-400">{{ __('Care summary and recent updates') }}</p>
        </div>
    </div>

    <x-card hover class="mb-5">
        <x-slot:header>{{ __('Care plan') }}</x-slot:header>
        @forelse ($carePlans as $plan)
            <div class="border-b border-gray-50 py-3 last:border-0 dark:border-gray-800">
                <div class="font-semibold text-gray-700 dark:text-gray-200">{{ $plan->title }}</div>
                @if ($plan->summary)
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $plan->summary }}</p>
                @endif
                @if ($plan->review_date)
                    <p class="mt-1 text-xs text-gray-400">{{ __('Next review') }}: {{ $plan->review_date->format('d M Y') }}</p>
                @endif
            </div>
        @empty
            <x-empty-state title="{{ __('No active care plan to show yet') }}" icon="ik ik-file-text" />
        @endforelse
    </x-card>

    <x-card no-padding hover>
        <x-slot:header>{{ __('Recent updates') }}</x-slot:header>
        <div class="divide-y divide-gray-50 dark:divide-gray-800">
            @forelse ($timeline as $entry)
                <div class="px-5 py-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                            {{ ucfirst(str_replace('_', ' ', $entry->entry_type)) }}
                        </span>
                        <span class="text-xs text-gray-400">{{ $entry->created_at->format('d M Y, H:i') }}</span>
                    </div>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $entry->content }}</p>
                    <p class="mt-1 text-xs text-gray-400">{{ __('by') }} {{ $entry->creator->name ?? __('a carer') }}</p>
                </div>
            @empty
                <div class="px-5 py-10">
                    <x-empty-state title="{{ __('No updates yet') }}"
                        description="{{ __('Updates appear here as carers complete visits.') }}" icon="ik ik-clock" />
                </div>
            @endforelse
        </div>
    </x-card>
</div>
