<div>
    <x-page-header title="{{ __('Training Matrix') }}" subtitle="{{ __('Staff training and certificate status at a glance') }}"
        icon="ik ik-grid" :breadcrumbs="['Home' => url('dashboard'), 'Training' => route('training.index'), 'Matrix' => null]">
        <a href="{{ route('training.index') }}" class="text-primary-600 hover:underline text-sm font-medium">
            {{ __('Manage modules') }}
        </a>
    </x-page-header>

    <div class="mb-4 flex flex-wrap gap-4 text-xs text-gray-500 dark:text-gray-400">
        <span class="flex items-center gap-1"><span class="h-3 w-3 rounded bg-gray-200 dark:bg-gray-700"></span>{{ __('Not started') }}</span>
        <span class="flex items-center gap-1"><span class="h-3 w-3 rounded bg-primary-200"></span>{{ __('Started') }}</span>
        <span class="flex items-center gap-1"><span class="h-3 w-3 rounded bg-success-200"></span>{{ __('Completed') }}</span>
        <span class="flex items-center gap-1"><span class="h-3 w-3 rounded bg-warning-200"></span>{{ __('Expiring soon') }}</span>
        <span class="flex items-center gap-1"><span class="h-3 w-3 rounded bg-danger-200"></span>{{ __('Expired') }}</span>
    </div>

    <x-card no-padding hover>
        <div class="overflow-auto" style="max-height: 70vh;">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr>
                        <th class="sticky top-0 left-0 z-20 bg-white px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-400 dark:bg-gray-900 dark:text-gray-500">
                            {{ __('Staff') }}
                        </th>
                        @foreach ($modules as $module)
                            <th class="sticky top-0 z-10 min-w-[140px] bg-white px-3 py-3 text-left text-xs font-medium text-gray-500 dark:bg-gray-900 dark:text-gray-400">
                                {{ $module->title }}
                                @if ($module->is_mandatory)
                                    <span class="ml-1 text-[10px] font-semibold uppercase text-danger-500">{{ __('Mandatory') }}</span>
                                @endif
                                @if ($module->renewal_period_days)
                                    <div class="text-[10px] font-normal text-gray-400">{{ __('Renews every :days days', ['days' => $module->renewal_period_days]) }}</div>
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                    @forelse ($staff as $person)
                        <tr wire:key="staff-{{ $person->id }}">
                            <td class="sticky left-0 z-10 bg-white px-4 py-2 font-medium text-gray-700 dark:bg-gray-900 dark:text-gray-200 whitespace-nowrap">
                                {{ $person->full_name }}
                            </td>
                            @foreach ($modules as $module)
                                @php
                                    $progress = $progressByKey->get($person->id.'|'.$module->id);
                                    $status = $progress ? $progress->matrixStatus() : 'not_started';
                                    $cellColor = match ($status) {
                                        'completed' => 'bg-success-50 text-success-700 dark:bg-success-900/30 dark:text-success-300',
                                        'expiring_soon' => 'bg-warning-50 text-warning-700 dark:bg-warning-900/30 dark:text-warning-300',
                                        'expired' => 'bg-danger-50 text-danger-700 dark:bg-danger-900/30 dark:text-danger-300',
                                        'started' => 'bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300',
                                        default => 'bg-gray-50 text-gray-400 dark:bg-gray-800/40 dark:text-gray-500',
                                    };
                                    $label = match ($status) {
                                        'completed' => __('Completed'),
                                        'expiring_soon' => __('Expiring soon'),
                                        'expired' => __('Expired'),
                                        'started' => __('Started'),
                                        default => __('Not started'),
                                    };
                                @endphp
                                <td class="px-3 py-2 text-center {{ $cellColor }}" wire:key="cell-{{ $person->id }}-{{ $module->id }}">
                                    <div class="text-xs font-medium">{{ $label }}</div>
                                    @if ($progress?->expires_at)
                                        <div class="text-[10px] opacity-75">{{ $progress->expires_at->format('d M Y') }}</div>
                                    @endif
                                    @if ($progress?->certificate_media_id)
                                        <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($progress->certificate->file_path) }}" target="_blank"
                                            class="text-[10px] underline opacity-75 hover:opacity-100">{{ __('Certificate') }}</a>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $modules->count() + 1 }}" class="px-5 py-10">
                                <x-empty-state title="{{ __('No staff found') }}" icon="ik ik-users" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
