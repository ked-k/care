<div x-data x-on:toast.window="$store.toast.push($event.detail.message, $event.detail.type)">
    <x-page-header title="{{ __('Staff Profile') }}" subtitle="{{ $targetUser->full_name }}" icon="ik ik-user"
        :breadcrumbs="['Home' => url('dashboard'), 'Staff' => $canManage ? route('staff.index') : null, $targetUser->full_name => null]">
        @if ($canManage)
            <a href="{{ route('staff.index') }}" class="text-sm font-medium text-primary-600 hover:underline">
                <i class="ik ik-arrow-left mr-1"></i>{{ __('Back to Staff') }}
            </a>
        @endif
    </x-page-header>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        {{-- Personal details --}}
        <x-card class="lg:col-span-1">
            <div class="flex flex-col items-center text-center">
                <x-avatar :name="$targetUser->full_name" :src="$targetUser->avatarUrl()" size="h-20 w-20" class="text-lg" />

                @if ($canManage || $isOwnProfile)
                    <div class="mt-3 flex items-center gap-3 text-xs">
                        <label class="cursor-pointer font-medium text-primary-600 hover:underline">
                            {{ __('Upload photo') }}
                            <input type="file" wire:model="avatarUpload" class="hidden" accept="image/*">
                        </label>
                        @if ($targetUser->avatar)
                            <button type="button" wire:click="removeAvatar" wire:confirm="{{ __('Remove this photo?') }}"
                                class="font-medium text-accent-500 hover:underline">{{ __('Remove') }}</button>
                        @endif
                    </div>
                    @error('avatarUpload')
                        <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                    @enderror
                    <div wire:loading wire:target="avatarUpload" class="mt-1 text-xs text-gray-400">{{ __('Uploading…') }}</div>
                @endif

                <h5 class="mt-3 font-semibold text-gray-700 dark:text-gray-200">{{ $targetUser->full_name }}</h5>
                <div class="text-xs text-gray-400">{{ $targetUser->email }}</div>
                <x-badge class="mt-2" color="{{ match ($targetUser->roles->first()?->name) {
                    'Admin', 'Super Admin' => 'danger',
                    'Manager' => 'primary',
                    default => 'secondary',
                } }}">
                    {{ $targetUser->roles->first()?->name ?? __('No role') }}
                </x-badge>
            </div>

            <dl class="mt-5 space-y-3 border-t border-gray-100 pt-4 text-sm dark:border-gray-700">
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-400">{{ __('Phone') }}</dt>
                    <dd class="text-right text-gray-600 dark:text-gray-300">{{ $targetUser->phone_number ?: '—' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-400">{{ __('Address') }}</dt>
                    <dd class="text-right text-gray-600 dark:text-gray-300">{{ $targetUser->address ?: '—' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-400">{{ __('Employee No.') }}</dt>
                    <dd class="text-right text-gray-600 dark:text-gray-300">{{ $targetUser->payProfile?->employee_no ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-400">{{ __('Job title') }}</dt>
                    <dd class="text-right text-gray-600 dark:text-gray-300">{{ $targetUser->payProfile?->job_title ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-gray-400">{{ __('Employment start date') }}</dt>
                    @if ($canManage)
                        <dd class="flex items-center gap-2">
                            <input type="date" wire:model="formEmploymentStartDate"
                                class="rounded-lg border border-gray-200 bg-white px-2 py-1 text-xs dark:border-gray-700 dark:bg-gray-800">
                            <button type="button" wire:click="saveEmploymentStartDate"
                                class="text-xs font-medium text-primary-600 hover:underline">{{ __('Save') }}</button>
                        </dd>
                    @else
                        <dd class="text-right text-gray-600 dark:text-gray-300">
                            {{ $targetUser->payProfile?->employment_start_date?->format('d M Y') ?? '—' }}
                        </dd>
                    @endif
                </div>
            </dl>

            <p class="mt-4 text-xs text-gray-400">
                {{ __('Name, email, phone and address are edited from') }}
                <a href="{{ url('profile') }}" class="text-primary-600 hover:underline">{{ __('Profile') }}</a>
                {{ $canManage ? __(' (or the Edit drawer on the Staff list for job title and pay details).') : '.' }}
            </p>
        </x-card>

        {{-- Compliance checklist --}}
        <x-card class="lg:col-span-2" title="{{ __('Compliance checklist') }}">
            @if ($canManage && $compliance['pendingReviewCount'] > 0)
                <div class="mb-3 rounded-lg bg-blue-50 px-3 py-2 text-xs text-blue-700 dark:bg-blue-900/20 dark:text-blue-300">
                    {{ trans_choice(':count document is pending your review|:count documents are pending your review', $compliance['pendingReviewCount'], ['count' => $compliance['pendingReviewCount']]) }}
                </div>
            @endif

            <div class="divide-y divide-gray-50 dark:divide-gray-800">
                @foreach ($compliance['rows'] as $key => $row)
                    <div class="flex items-center justify-between gap-3 py-2.5">
                        <div class="font-medium text-gray-600 dark:text-gray-300">{{ __($row['label']) }}</div>
                        <div class="flex items-center gap-2">
                            @if ($row['document']?->expires_at && in_array($row['status'], ['ok', 'expiring_soon', 'expired'], true))
                                <span class="text-xs text-gray-400">{{ $row['document']->expires_at->format('d M Y') }}</span>
                            @endif
                            <x-badge color="{{ match ($row['status']) {
                                'ok' => 'success',
                                'expiring_soon' => 'warning',
                                'expired' => 'danger',
                                'pending_review' => 'primary',
                                default => 'secondary',
                            } }}">
                                {{ match ($row['status']) {
                                    'ok' => __('On file'),
                                    'expiring_soon' => __('Expiring soon'),
                                    'expired' => __('Expired'),
                                    'pending_review' => __('Pending review'),
                                    default => __('Missing'),
                                } }}
                            </x-badge>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>
    </div>

    {{-- Documents --}}
    <x-card no-padding hover class="mt-5" title="{{ __('Documents') }}">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3 dark:border-gray-700">
            <h6 class="font-semibold text-gray-700 dark:text-gray-200">{{ __('Documents') }}</h6>
            <x-button variant="primary" size="sm" @click="$dispatch('open-drawer', 'staff-document-form')" wire:click="openUploadForm">
                <i class="ik ik-plus mr-1"></i>{{ __('Upload document') }}
            </x-button>
        </div>

        <div class="divide-y divide-gray-50 dark:divide-gray-800">
            @foreach ($categories as $key => $label)
                <div class="px-5 py-3">
                    <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __($label) }}</div>

                    @forelse ($documents->get($key, collect()) as $doc)
                        <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg px-2 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-800/40" wire:key="doc-{{ $doc->id }}">
                            <div>
                                <a href="{{ $doc->media?->url() }}" target="_blank" class="text-sm font-medium text-primary-600 hover:underline">
                                    {{ $doc->title }}
                                </a>
                                <div class="text-xs text-gray-400">
                                    {{ __('Uploaded by :who on :date', ['who' => $doc->uploader?->name ?? '—', 'date' => $doc->created_at->format('d M Y')]) }}
                                    @if ($doc->expires_at)
                                        · {{ __('Expires :date', ['date' => $doc->expires_at->format('d M Y')]) }}
                                    @endif
                                </div>
                                @if ($doc->status === 'rejected' && $doc->notes)
                                    <div class="text-xs text-red-500">{{ __('Rejected:') }} {{ $doc->notes }}</div>
                                @endif
                            </div>

                            <div class="flex items-center gap-2">
                                <x-badge color="{{ match ($doc->status) {
                                    'approved' => 'success',
                                    'rejected' => 'danger',
                                    default => 'warning',
                                } }}">
                                    {{ ucfirst($doc->status) }}
                                </x-badge>

                                @if ($canManage)
                                    @if ($doc->status === 'pending')
                                        <button type="button" wire:click="approveDocument('{{ $doc->id }}')"
                                            class="text-xs font-medium text-green-600 hover:underline">{{ __('Approve') }}</button>
                                        <input type="text" wire:model="rejectNotes.{{ $doc->id }}" placeholder="{{ __('Reason (optional)') }}"
                                            class="w-32 rounded border border-gray-200 px-1.5 py-0.5 text-xs dark:border-gray-700 dark:bg-gray-800">
                                        <button type="button" wire:click="rejectDocument('{{ $doc->id }}')"
                                            class="text-xs font-medium text-accent-500 hover:underline">{{ __('Reject') }}</button>
                                    @endif
                                    <button type="button" wire:click="deleteDocument('{{ $doc->id }}')"
                                        wire:confirm="{{ __('Remove this document?') }}"
                                        class="text-xs font-medium text-gray-400 hover:text-red-500 hover:underline">{{ __('Remove') }}</button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="px-2 py-1 text-xs text-gray-400">{{ __('Nothing on file yet.') }}</div>
                    @endforelse
                </div>
            @endforeach
        </div>
    </x-card>

    <x-drawer name="staff-document-form" title="{{ __('Upload document') }}" width="w-[28rem]">
        <div class="space-y-4">
            <x-form.select name="formCategory" label="{{ __('Category') }}" wire:model="formCategory" required>
                @foreach ($categories as $key => $label)
                    <option value="{{ $key }}">{{ __($label) }}</option>
                @endforeach
            </x-form.select>

            <x-form.input name="formTitle" label="{{ __('Title') }}" wire:model="formTitle"
                placeholder="{{ __('e.g. DBS Certificate') }}" required />

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500">{{ __('File (PDF, JPG or PNG, max 8MB)') }}</label>
                <input type="file" wire:model="formFile"
                    class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-primary-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-primary-700 dark:text-gray-300">
                @error('formFile')
                    <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                @enderror
                <div wire:loading wire:target="formFile" class="mt-1 text-xs text-gray-400">{{ __('Uploading…') }}</div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <x-form.input type="date" name="formIssuedAt" label="{{ __('Issued (optional)') }}" wire:model="formIssuedAt" />
                <x-form.input type="date" name="formExpiresAt" label="{{ __('Expires (optional)') }}" wire:model="formExpiresAt" />
            </div>

            @unless ($canManage)
                <p class="text-xs text-gray-400">{{ __('This will be sent to your manager for review before it counts as on file.') }}</p>
            @endunless
        </div>

        <x-slot:footer>
            <x-button wire:click="saveDocument">{{ __('Save') }}</x-button>
        </x-slot:footer>
    </x-drawer>
</div>
