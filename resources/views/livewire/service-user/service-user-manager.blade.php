<div x-data x-on:toast.window="$store.toast.push($event.detail.message, $event.detail.type)">
    <x-page-header title="{{ __('Service Users') }}" subtitle="{{ __('The people your carers support') }}"
        icon="ik ik-users" :breadcrumbs="['Home' => url('dashboard'), 'Service Users' => null]">
        <div class="flex items-center gap-3">
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="{{ __('Search by name...') }}"
                class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
            <label class="flex items-center gap-2 text-sm text-gray-500">
                <input type="checkbox" wire:model.live="showInactive"
                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                {{ __('Show inactive') }}
            </label>
            <x-button variant="primary" size="sm" wire:click="openCreateForm"
                @click="$dispatch('open-drawer', 'service-user-form')">
                <i class="ik ik-plus mr-1"></i>{{ __('Add service user') }}
            </x-button>
        </div>
    </x-page-header>

    <x-card no-padding hover>
        <x-table :paginator="$serviceUsers" title="{{ __('Service Users') }}">
            <table class="w-full text-sm">
                <thead>
                    <tr
                        class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400 dark:border-gray-700 dark:text-gray-500">
                        <th class="px-5 py-3 font-medium">{{ __('Service user') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Date of Birth') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Next of Kin') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Consent') }}</th>
                        <th class="px-5 py-3 font-medium">{{ __('Status') }}</th>
                        {{-- Batch 37: at-a-glance whether this person has a usable
                             location on file — the same thing the geofenced
                             check-in (Batch 35) and "Get directions" rely on. --}}
                        <th class="px-5 py-3 font-medium">{{ __('Location') }}</th>
                        <th class="px-5 py-3 font-medium text-right">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                    @forelse ($serviceUsers as $su)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40" wire:key="su-{{ $su->id }}">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    {{-- Batch 39: photo if one's been uploaded, initials otherwise. --}}
                                    <x-avatar :name="$su->name" :src="$su->photoUrl()" size="h-9 w-9" />
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5 font-semibold text-gray-700 dark:text-gray-200">
                                            <span class="truncate">{{ $su->name }}</span>
                                            @if ($su->hasKeyCareInformation())
                                                <i class="ik ik-alert-triangle shrink-0 text-amber-500"
                                                    title="{{ __('Has allergies, medical conditions, or other key care information on file') }}"></i>
                                            @endif
                                            @unless ($su->hasBillingConfigured())
                                                <i class="ik ik-dollar-sign shrink-0 text-gray-300"
                                                    title="{{ __('Billing not set up yet — will be skipped on the next invoice run') }}"></i>
                                            @endunless
                                        </div>
                                        @if ($su->nhs_number)
                                            <div class="text-xs text-gray-400">{{ __('NHS') }} {{ $su->nhs_number }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-gray-500 dark:text-gray-400">
                                {{ $su->dob?->format('d M Y') ?? '—' }}</td>
                            <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $su->next_of_kin_name ?? '—' }}
                            </td>
                            <td class="px-5 py-3">
                                <x-badge color="{{ $su->consent_status ? 'success' : 'amber' }}">
                                    {{ $su->consent_status ? __('Recorded') : __('Pending') }}
                                </x-badge>
                            </td>
                            <td class="px-5 py-3">
                                <x-badge color="{{ $su->trashed() ? 'secondary' : 'success' }}">
                                    {{ $su->trashed() ? __('Inactive') : __('Active') }}
                                </x-badge>
                            </td>
                            <td class="px-5 py-3">
                                @if ($su->directionsUrl())
                                    <a href="{{ $su->directionsUrl() }}" target="_blank" rel="noopener"
                                        class="inline-flex items-center gap-1 text-xs font-medium text-primary-600 hover:underline"
                                        title="{{ __('Open turn-by-turn directions') }}">
                                        <i class="ik ik-navigation"></i>{{ __('Directions') }}
                                    </a>
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs text-gray-300"
                                        title="{{ __('No address/location on file yet') }}">
                                        <i class="ik ik-map-pin"></i>{{ __('Not located') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    {{-- Batch 37: the new consolidated profile — the one
                                         link most people reaching for this row actually
                                         want, so it's a first-class button rather than
                                         buried in the menu with everything else. --}}
                                    <a href="{{ route('service-users.profile', $su->id) }}" wire:navigate
                                        class="text-primary-600 hover:underline text-sm font-medium">{{ __('Full Profile') }}</a>

                                    <x-dropdown align="right" width="w-48">
                                        <x-slot:trigger>
                                            <button type="button"
                                                class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800"
                                                title="{{ __('More actions') }}">
                                                <i class="ik ik-more-vertical"></i>
                                            </button>
                                        </x-slot:trigger>

                                        @unless ($su->trashed())
                                            <a href="{{ route('medications.manage', $su->id) }}" wire:navigate
                                                class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800"><i class="ik ik-heart text-gray-400"></i>{{ __('Medications') }}</a>
                                            <a href="{{ route('consents.manage', $su->id) }}" wire:navigate
                                                class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800"><i class="ik ik-check-square text-gray-400"></i>{{ __('Consents') }}</a>
                                            <a href="{{ route('family.manage', $su->id) }}" wire:navigate
                                                class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800"><i class="ik ik-users text-gray-400"></i>{{ __('Family') }}</a>
                                            <a href="{{ route('messages.manage', $su->id) }}" wire:navigate
                                                class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800"><i class="ik ik-message-square text-gray-400"></i>{{ __('Messages') }}</a>
                                            <a href="{{ route('assessments.manage', $su->id) }}" wire:navigate
                                                class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800"><i class="ik ik-clipboard text-gray-400"></i>{{ __('Assessments') }}</a>
                                            <a href="{{ route('timeline.manage', $su->id) }}" wire:navigate
                                                class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800"><i class="ik ik-clock text-gray-400"></i>{{ __('Timeline') }}</a>
                                            <a href="{{ route('calls.manage', $su->id) }}" wire:navigate
                                                class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800"><i class="ik ik-phone text-gray-400"></i>{{ __('Calls') }}</a>
                                            <div class="my-1 border-t border-gray-100 dark:border-gray-800"></div>
                                            <button type="button" wire:click="openEditForm('{{ $su->id }}')"
                                                @click="$dispatch('open-drawer', 'service-user-form')"
                                                class="flex w-full items-center gap-2.5 px-4 py-2 text-left text-sm text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800"><i class="ik ik-edit-3 text-gray-400"></i>{{ __('Edit') }}</button>
                                            <div class="my-1 border-t border-gray-100 dark:border-gray-800"></div>
                                        @endunless
                                        <button type="button" wire:click="toggleActive('{{ $su->id }}')"
                                            wire:confirm="{{ $su->trashed() ? __('Reactivate this service user?') : __('Mark this service user inactive?') }}"
                                            class="flex w-full items-center gap-2.5 px-4 py-2 text-left text-sm text-accent-500 hover:bg-gray-50 dark:hover:bg-gray-800"><i class="ik {{ $su->trashed() ? 'ik-refresh-ccw' : 'ik-minus-circle' }}"></i>{{ $su->trashed() ? __('Reactivate') : __('Deactivate') }}</button>
                                    </x-dropdown>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10">
                                <x-empty-state title="{{ __('No service users yet') }}"
                                    description="{{ __('Add the people your carers will support.') }}"
                                    icon="ik ik-users" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-table>
    </x-card>

    <x-drawer name="service-user-form"
        title="{{ $editingServiceUserId ? __('Edit service user') : __('Add service user') }}" width="w-[30rem]">
        <div class="space-y-4">
            <x-form.input name="formName" label="{{ __('Name') }}" wire:model="formName" required />

            <div class="grid grid-cols-2 gap-3">
                <x-form.input type="date" name="formDob" label="{{ __('Date of birth') }}" wire:model="formDob" />
                <x-form.select name="formGender" label="{{ __('Gender') }}" wire:model="formGender">
                    <option value="">{{ __('Not specified') }}</option>
                    <option value="female">{{ __('Female') }}</option>
                    <option value="male">{{ __('Male') }}</option>
                    <option value="other">{{ __('Other') }}</option>
                </x-form.select>
            </div>

            <x-form.textarea name="formAddress" label="{{ __('Address') }}" rows="2" wire:model="formAddress" />

            {{-- Batch 35: geofenced check-in restriction — a carer must be
                 within the agency's configured radius of this location to
                 check in. Auto-located from the address above by default. --}}
            <x-form.checkbox name="formManualLocation"
                label="{{ __('Pin exact location manually (instead of locating it from the address above)') }}"
                wire:model.live="formManualLocation" />

            @if ($formManualLocation)
                <div class="grid grid-cols-2 gap-3">
                    <x-form.input type="number" step="0.0000001" name="formLatitude" label="{{ __('Latitude') }}" wire:model="formLatitude" />
                    <x-form.input type="number" step="0.0000001" name="formLongitude" label="{{ __('Longitude') }}" wire:model="formLongitude" />
                </div>
            @else
                <p class="text-xs text-gray-400 -mt-2">
                    {{ __('Location for check-in geofencing is located automatically from the address above when this is saved.') }}
                </p>
            @endif

            <x-form.input name="formNhsNumber" label="{{ __('NHS number (optional)') }}" wire:model="formNhsNumber" />

            <div class="grid grid-cols-2 gap-3">
                <x-form.input name="formNextOfKinName" label="{{ __('Next of kin name') }}"
                    wire:model="formNextOfKinName" />
                <x-form.input name="formNextOfKinContact" label="{{ __('Next of kin contact') }}"
                    wire:model="formNextOfKinContact" />
            </div>

            <x-form.checkbox name="formConsentStatus" label="{{ __('Consent recorded') }}"
                wire:model="formConsentStatus" />

            {{-- Batch 15: key care information — kept on the profile itself
                 (not only inside a care plan) so it's visible at a glance,
                 including before any care plan exists. --}}
            <div class="border-t border-gray-100 pt-4 dark:border-gray-800">
                <h6 class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-400">
                    {{ __('Key care information') }}</h6>
                <div class="space-y-3">
                    <x-form.textarea name="formAllergies" label="{{ __('Allergies') }}" rows="2"
                        wire:model="formAllergies" placeholder="{{ __('e.g. penicillin, latex, peanuts') }}" />
                    <x-form.textarea name="formMedicalConditions" label="{{ __('Medical conditions') }}" rows="2"
                        wire:model="formMedicalConditions" />
                    <x-form.textarea name="formCommunicationNeeds" label="{{ __('Communication needs') }}" rows="2"
                        wire:model="formCommunicationNeeds"
                        placeholder="{{ __('e.g. hard of hearing on the left, uses a communication board') }}" />
                    <x-form.textarea name="formPreferences" label="{{ __('Preferences') }}" rows="2"
                        wire:model="formPreferences" />
                    <x-form.textarea name="formImportantInformation" label="{{ __('Other important information') }}"
                        rows="2" wire:model="formImportantInformation" />
                </div>
            </div>

            {{-- Batch 16: the named person responsible for this service
                 user's alerts (late/missed calls, missing records,
                 medication concerns) — see App\Support\AlertGenerator. --}}
            <div class="border-t border-gray-100 pt-4 dark:border-gray-800">
                <x-form.select name="formCareCoordinatorId" label="{{ __('Care coordinator') }}"
                    wire:model="formCareCoordinatorId">
                    <option value="">{{ __('Not set (alerts go to any manager/admin)') }}</option>
                    @foreach ($coordinatorOptions as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </x-form.select>
            </div>

            {{-- Batch 21: who pays for this person's care and how the
                 charge is worked out — see InvoiceRun::generateInvoices(). --}}
            <div class="border-t border-gray-100 pt-4 dark:border-gray-800">
                <h6 class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-400">
                    {{ __('Billing') }}</h6>
                <div class="space-y-3">
                    <x-form.select name="formBillingType" label="{{ __('Billing basis') }}"
                        wire:model.live="formBillingType">
                        <option value="per_visit">{{ __('Per completed visit') }}</option>
                        <option value="flat_package">{{ __('Flat recurring care package') }}</option>
                    </x-form.select>

                    @if ($formBillingType === 'per_visit')
                        <x-form.input type="number" step="0.01" min="0" name="formBillingRate"
                            label="{{ __('Hourly billing rate') }}" wire:model="formBillingRate"
                            placeholder="{{ __('Charged per hour — separate from the carer\'s own pay rate') }}" />
                    @else
                        <div class="grid grid-cols-2 gap-3">
                            <x-form.input type="number" step="0.01" min="0" name="formBillingPackageAmount"
                                label="{{ __('Package amount') }}" wire:model="formBillingPackageAmount" />
                            <x-form.select name="formBillingPackagePeriod" label="{{ __('Per') }}"
                                wire:model="formBillingPackagePeriod">
                                <option value="weekly">{{ __('Week') }}</option>
                                <option value="monthly">{{ __('Month') }}</option>
                            </x-form.select>
                        </div>
                    @endif

                    <x-form.select name="formPayerType" label="{{ __('Who pays') }}" wire:model.live="formPayerType">
                        <option value="self_funded">{{ __('Self-funded (client/family)') }}</option>
                        <option value="local_authority">{{ __('Local authority') }}</option>
                        <option value="split">{{ __('Split — local authority + client top-up') }}</option>
                    </x-form.select>

                    @if (in_array($formPayerType, ['self_funded', 'split']))
                        <x-form.input name="formPayerName" label="{{ __('Billing contact name') }}"
                            wire:model="formPayerName"
                            placeholder="{{ __('Defaults to the service user\'s own name if left blank') }}" />
                        <x-form.textarea name="formPayerAddress" label="{{ __('Billing address') }}" rows="2"
                            wire:model="formPayerAddress" />
                        <x-form.input type="email" name="formPayerEmail" label="{{ __('Billing email') }}"
                            wire:model="formPayerEmail" />
                    @endif

                    @if (in_array($formPayerType, ['local_authority', 'split']))
                        <x-form.input name="formLocalAuthorityName" label="{{ __('Local authority name') }}"
                            wire:model="formLocalAuthorityName" />
                        <x-form.input name="formLocalAuthorityReference" label="{{ __('Contract / PO reference') }}"
                            wire:model="formLocalAuthorityReference" />
                    @endif

                    @if ($formPayerType === 'split')
                        <x-form.input type="number" step="0.01" min="0" name="formLocalAuthorityShareAmount"
                            label="{{ __('Local authority contribution per period') }}"
                            wire:model="formLocalAuthorityShareAmount"
                            placeholder="{{ __('Fixed amount the local authority pays; the client is billed the balance') }}" />
                    @endif
                </div>
            </div>
        </div>

        <x-slot:footer>
            <x-button wire:click="saveServiceUser">{{ __('Save') }}</x-button>
        </x-slot:footer>
    </x-drawer>
</div>
