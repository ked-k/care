<div>
    <x-page-header title="{{ __('Care Plans') }}" subtitle="{{ __('One plan per service user, holding their recurring and one-off tasks') }}"
                    icon="ik ik-clipboard" :breadcrumbs="['Home' => url('dashboard'), 'Care Plans' => null]">
        <x-button variant="primary" @click="$dispatch('open-drawer', 'new-care-plan')">
            <i class="ik ik-plus mr-1"></i>{{ __('New care plan') }}
        </x-button>
    </x-page-header>

    <x-card no-padding hover>
        <x-table :paginator="$plans" title="{{ __('Care Plans') }}">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400 dark:border-gray-700 dark:text-gray-500">
                        <th class="px-5 py-3 font-medium">{{ __('Service User') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Title') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Tasks') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Review Date') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-5 py-3 font-medium text-right">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                    @forelse ($plans as $plan)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40" wire:key="cp-{{ $plan->id }}">
                            <td class="px-5 py-3 font-semibold text-gray-700 dark:text-gray-200">{{ $plan->serviceUser->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-gray-600 dark:text-gray-300">{{ $plan->title }}</td>
                            <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $plan->tasks_count }}</td>
                            <td class="px-5 py-3 text-gray-500 dark:text-gray-400">
                                {{ $plan->review_date?->format('d M Y') ?? '—' }}
                            </td>
                            <td class="px-5 py-3">
                                <x-badge color="{{ $plan->is_active ? 'success' : 'secondary' }}">
                                    {{ $plan->is_active ? __('Active') : __('Inactive') }}
                                </x-badge>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('care-plans.show', $plan) }}" wire:navigate
                                   class="text-primary-600 hover:underline text-sm font-medium">{{ __('Open') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10">
                                <x-empty-state title="{{ __('No care plans yet') }}"
                                               description="{{ __('Create a care plan for a service user to start adding tasks.') }}"
                                               icon="ik ik-clipboard" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-table>
    </x-card>

    <x-drawer name="new-care-plan" title="{{ __('New care plan') }}" width="w-[28rem]">
        <div class="space-y-4">
            <x-form.select name="newServiceUserId" label="{{ __('Service user') }}" wire:model="newServiceUserId" required>
                <option value="">{{ __('Select a service user') }}</option>
                @foreach ($serviceUserOptions as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </x-form.select>
            <x-form.input name="newTitle" label="{{ __('Title') }}" wire:model="newTitle" required
                          placeholder="{{ __('e.g. Daily personal care plan') }}" />
            <x-form.textarea name="newSummary" label="{{ __('Summary (optional)') }}" rows="3" wire:model="newSummary" />
            <x-form.input type="date" name="newReviewDate" label="{{ __('Review date (optional)') }}" wire:model="newReviewDate" />
        </div>

        <x-slot:footer>
            <x-button wire:click="createCarePlan">{{ __('Create & open') }}</x-button>
        </x-slot:footer>
    </x-drawer>
</div>
