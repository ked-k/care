<div x-data x-on:toast.window="$store.toast.push($event.detail.message, $event.detail.type)">
    <x-page-header title="{{ __('Family Access') }}" subtitle="{{ $serviceUser->name }}" icon="ik ik-users"
        :breadcrumbs="['Home' => url('dashboard'), 'Service Users' => route('service-users.index'), $serviceUser->name => null]">
        @if ($canManage)
            <x-button variant="primary" size="sm" wire:click="openAddForm"
                @click="$dispatch('open-drawer', 'family-member-form')">
                <i class="ik ik-plus mr-1"></i>{{ __('Add family member') }}
            </x-button>
        @endif
    </x-page-header>

    <x-card no-padding hover>
        <x-slot:header>{{ __('Linked family members') }}</x-slot:header>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400 dark:border-gray-700 dark:text-gray-500">
                        <th class="px-5 py-3 font-medium">{{ __('Name') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Email') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Relationship') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Primary Contact') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Receives Updates') }}</th>
                        @if ($canManage)
                            <th class="px-5 py-3 font-medium text-right">{{ __('Action') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                    @forelse ($familyMembers as $fm)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40" wire:key="fm-{{ $fm->id }}">
                            <td class="px-5 py-3 font-semibold text-gray-700 dark:text-gray-200">{{ $fm->user->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $fm->user->email ?? '—' }}</td>
                            <td class="px-5 py-3 text-gray-600 dark:text-gray-300">{{ $fm->relationship }}</td>
                            <td class="px-5 py-3">
                                @if ($fm->is_primary_contact)
                                    <x-badge color="success">{{ __('Yes') }}</x-badge>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <x-badge color="{{ $fm->can_receive_updates ? 'success' : 'secondary' }}">
                                    {{ $fm->can_receive_updates ? __('Yes') : __('No') }}
                                </x-badge>
                            </td>
                            @if ($canManage)
                                <td class="px-5 py-3 text-right">
                                    <button type="button" wire:click="removeFamilyMember('{{ $fm->id }}')"
                                        wire:confirm="{{ __('Remove this family member\'s access?') }}"
                                        class="text-accent-500 hover:underline text-sm font-medium">{{ __('Remove') }}</button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canManage ? 6 : 5 }}" class="px-5 py-10">
                                <x-empty-state title="{{ __('No family members linked') }}"
                                    description="{{ __('Add a family member to give them a portal login for this person.') }}"
                                    icon="ik ik-users" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <x-drawer name="family-member-form" title="{{ __('Add family member') }}">
        @if ($generatedPassword)
            <div class="space-y-4">
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    <p class="font-semibold">{{ __('Account created — share these details securely') }}</p>
                    <p class="mt-2">{{ __('Email') }}: <span class="font-mono">{{ $formEmail }}</span></p>
                    <p>{{ __('Temporary password') }}: <span class="font-mono">{{ $generatedPassword }}</span></p>
                    <p class="mt-2 text-xs">{{ __('This password is shown once and cannot be retrieved again.') }}</p>
                </div>
            </div>
            <x-slot:footer>
                <x-button wire:click="dismissGeneratedPassword">{{ __('Done') }}</x-button>
            </x-slot:footer>
        @else
            <div class="space-y-4">
                <x-form.input name="formName" label="{{ __('Full name') }}" wire:model="formName" required />
                <x-form.input type="email" name="formEmail" label="{{ __('Email') }}" wire:model="formEmail" required />
                <x-form.input name="formRelationship" label="{{ __('Relationship') }}" wire:model="formRelationship" required
                    placeholder="{{ __('e.g. Daughter, Son, Spouse') }}" />
                <x-form.checkbox name="formIsPrimaryContact" label="{{ __('Primary contact') }}" wire:model="formIsPrimaryContact" />
                <x-form.checkbox name="formCanReceiveUpdates" label="{{ __('Can receive care updates') }}" wire:model="formCanReceiveUpdates" />
                <p class="text-xs text-gray-400">{{ __('If this email doesn\'t have an account yet, one is created automatically with a Family login — restricted to this person\'s record only.') }}</p>
            </div>
            <x-slot:footer>
                <x-button wire:click="addFamilyMember">{{ __('Add') }}</x-button>
            </x-slot:footer>
        @endif
    </x-drawer>
</div>
