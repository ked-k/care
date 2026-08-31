<div x-data x-on:toast.window="$store.toast.push($event.detail.message, $event.detail.type)">
    <x-page-header title="{{ __('Data Breaches') }}" subtitle="{{ __('Data-protection incidents, separate from safeguarding') }}"
        icon="ik ik-alert-triangle" :breadcrumbs="['Home' => url('dashboard'), 'Data Breaches' => null]">
        <x-button variant="primary" size="sm" wire:click="openReportForm" @click="$dispatch('open-drawer', 'breach-report-form')">
            <i class="ik ik-plus mr-1"></i>{{ __('Report incident') }}
        </x-button>
    </x-page-header>

    <x-card no-padding hover>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400 dark:border-gray-700 dark:text-gray-500">
                        <th class="px-5 py-3 font-medium">{{ __('Description') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Severity') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Reported By') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Reported to ICO') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                        @if ($canManage)
                            <th class="px-5 py-3 font-medium text-right">{{ __('Action') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                    @forelse ($reports as $report)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40" wire:key="breach-{{ $report->id }}">
                            <td class="px-5 py-3 max-w-sm truncate text-gray-700 dark:text-gray-200" title="{{ $report->description }}">{{ $report->description }}</td>
                            <td class="px-5 py-3"><x-badge color="{{ $report->severityColor() }}">{{ ucfirst($report->severity) }}</x-badge></td>
                            <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $report->reporter->name ?? '—' }}</td>
                            <td class="px-5 py-3">
                                <x-badge color="{{ $report->reported_to_ico ? 'success' : 'secondary' }}">
                                    {{ $report->reported_to_ico ? __('Yes') : __('No') }}
                                </x-badge>
                            </td>
                            <td class="px-5 py-3"><x-badge color="{{ $report->isOpen() ? 'amber' : 'success' }}">{{ $report->isOpen() ? __('Open') : __('Actioned') }}</x-badge></td>
                            @if ($canManage)
                                <td class="px-5 py-3 text-right">
                                    <button type="button" wire:click="openResolveForm('{{ $report->id }}')"
                                        @click="$dispatch('open-drawer', 'breach-resolve')"
                                        class="text-primary-600 hover:underline text-sm font-medium">{{ __('Record action') }}</button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canManage ? 6 : 5 }}" class="px-5 py-10">
                                <x-empty-state title="{{ __('No data incidents reported') }}" icon="ik ik-alert-triangle" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <x-drawer name="breach-report-form" title="{{ __('Report a data incident') }}">
        <div class="space-y-4">
            <x-form.textarea name="formDescription" label="{{ __('What happened') }}" rows="5" wire:model="formDescription" required />
            <x-form.select name="formSeverity" label="{{ __('Severity') }}" wire:model="formSeverity">
                <option value="low">{{ __('Low') }}</option>
                <option value="medium">{{ __('Medium') }}</option>
                <option value="high">{{ __('High') }}</option>
                <option value="critical">{{ __('Critical') }}</option>
            </x-form.select>
        </div>
        <x-slot:footer>
            <x-button wire:click="submitReport">{{ __('Submit') }}</x-button>
        </x-slot:footer>
    </x-drawer>

    <x-drawer name="breach-resolve" title="{{ __('Record action taken') }}">
        <div class="space-y-4">
            <x-form.textarea name="actionTaken" label="{{ __('Action taken') }}" rows="4" wire:model="actionTaken" required />
            <x-form.checkbox name="reportedToIco" label="{{ __('Reported to the ICO') }}" wire:model="reportedToIco" />
        </div>
        <x-slot:footer>
            <x-button wire:click="recordAction">{{ __('Save') }}</x-button>
        </x-slot:footer>
    </x-drawer>
</div>
