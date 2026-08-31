<div x-data x-on:toast.window="$store.toast.push($event.detail.message, $event.detail.type)">
    <x-page-header title="{{ __('Subject Access Requests') }}" subtitle="{{ __('Data-protection rights requests and their fulfilment') }}"
        icon="ik ik-file-text" :breadcrumbs="['Home' => url('dashboard'), 'Subject Access Requests' => null]">
        <x-button variant="primary" size="sm" wire:click="openCreateForm" @click="$dispatch('open-drawer', 'sar-form')">
            <i class="ik ik-plus mr-1"></i>{{ __('Log a request') }}
        </x-button>
    </x-page-header>

    <x-card no-padding hover>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400 dark:border-gray-700 dark:text-gray-500">
                        <th class="px-5 py-3 font-medium">{{ __('Service User') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Type') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Requested By') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Logged') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                        @if ($canManage)
                            <th class="px-5 py-3 font-medium text-right">{{ __('Action') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                    @forelse ($requests as $sar)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40" wire:key="sar-{{ $sar->id }}">
                            <td class="px-5 py-3 font-semibold text-gray-700 dark:text-gray-200">{{ $sar->serviceUser->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-gray-600 dark:text-gray-300">{{ $sar->typeLabel() }}</td>
                            <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $sar->requester->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $sar->created_at->format('d M Y') }}</td>
                            <td class="px-5 py-3"><x-badge color="{{ $sar->statusColor() }}">{{ ucfirst(str_replace('_', ' ', $sar->status)) }}</x-badge></td>
                            @if ($canManage)
                                <td class="px-5 py-3 text-right">
                                    @unless (in_array($sar->status, ['fulfilled', 'rejected']))
                                        <button type="button" wire:click="openResolveForm('{{ $sar->id }}')"
                                            @click="$dispatch('open-drawer', 'sar-resolve')"
                                            class="text-primary-600 hover:underline text-sm font-medium">{{ __('Update') }}</button>
                                    @endunless
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canManage ? 6 : 5 }}" class="px-5 py-10">
                                <x-empty-state title="{{ __('No requests logged') }}" icon="ik ik-file-text" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <x-drawer name="sar-form" title="{{ __('Log a data protection request') }}">
        <div class="space-y-4">
            <x-form.select name="formServiceUserId" label="{{ __('Service user') }}" wire:model="formServiceUserId" required>
                <option value="">{{ __('Select...') }}</option>
                @foreach ($this->serviceUserOptions() as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </x-form.select>
            <x-form.select name="formType" label="{{ __('Type of request') }}" wire:model="formType">
                @foreach (\App\Models\SubjectAccessRequest::TYPES as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </x-form.select>
            <x-form.textarea name="formNotes" label="{{ __('Notes (optional)') }}" rows="3" wire:model="formNotes" />
        </div>
        <x-slot:footer>
            <x-button wire:click="submitRequest">{{ __('Log request') }}</x-button>
        </x-slot:footer>
    </x-drawer>

    <x-drawer name="sar-resolve" title="{{ __('Update request') }}">
        <div class="space-y-4">
            <x-form.select name="resolveStatus" label="{{ __('Status') }}" wire:model="resolveStatus">
                <option value="in_progress">{{ __('In progress') }}</option>
                <option value="fulfilled">{{ __('Fulfilled') }}</option>
                <option value="rejected">{{ __('Rejected') }}</option>
            </x-form.select>
            <x-form.textarea name="resolveNotes" label="{{ __('Notes') }}" rows="3" wire:model="resolveNotes" />
        </div>
        <x-slot:footer>
            <x-button wire:click="resolve">{{ __('Save') }}</x-button>
        </x-slot:footer>
    </x-drawer>
</div>
