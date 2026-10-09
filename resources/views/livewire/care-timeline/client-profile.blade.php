{{--
    Batch 14: carer-only client profile — today's Care Call Timeline plus
    quick links into Notes and Messages for this same person.
--}}
<div>
    <div class="mb-5 flex items-center gap-3">
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary-50 text-base font-semibold text-primary-600">
            {{ mb_strtoupper(mb_substr($serviceUser->name, 0, 1)) }}
        </span>
        <div class="min-w-0">
            <h1 class="truncate text-lg font-semibold text-gray-800">{{ $serviceUser->name }}</h1>
            <p class="text-sm text-gray-400">{{ now()->format('l, d M Y') }}</p>
        </div>
    </div>

    <x-card hover>
        <x-slot:header>{{ __("Today's calls") }}</x-slot:header>
        <livewire:care-timeline.care-call-timeline :service-user-id="$serviceUser->id" :key="'timeline-'.$serviceUser->id" />
    </x-card>

    <div class="mt-4 grid grid-cols-2 gap-3">
        <a href="{{ route('notes.quick') }}" wire:navigate
            class="rounded-xl border border-gray-100 bg-white p-4 text-left shadow-sm hover:border-primary-200">
            <i class="ik ik-edit-3 text-xl text-primary-500"></i>
            <div class="mt-2 text-sm font-semibold text-gray-700">{{ __('Add a note') }}</div>
        </a>
        <a href="{{ route('messages.carer') }}" wire:navigate
            class="rounded-xl border border-gray-100 bg-white p-4 text-left shadow-sm hover:border-primary-200">
            <i class="ik ik-message-square text-xl text-primary-500"></i>
            <div class="mt-2 text-sm font-semibold text-gray-700">{{ __('Messages') }}</div>
        </a>
    </div>
</div>
