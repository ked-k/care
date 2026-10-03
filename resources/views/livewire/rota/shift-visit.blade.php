{{--
    Batch 11: the carer's single mobile screen for one shift (My Rota links
    here instead of to separate Tasks/MAR-chart pages), and also the
    "admin takes over a carer's shift" screen — see
    App\Livewire\Rota\ShiftVisitComponent's class doc comment.

    Tabs switch instantly via Alpine (x-show + @entangle('tab').live), the
    same pattern Batch 9/10 established in family-service-user.blade.php,
    so a phone doesn't wait on a network round trip just to change screens.
--}}
<div x-data="{ tab: @entangle('tab').live }" x-on:toast.window="$store.toast.push($event.detail.message, $event.detail.type)">

    {{-- Header. Batch 12 fix: the back arrow used to always go to "My Rota"
         — wrong for a manager/admin who arrived here from the Rota Builder
         to cover someone else's shift (they have no reason to land on
         their own, probably-empty, carer rota). It now goes back to where
         that audience actually came from. --}}
    <div class="mb-4 flex items-center gap-3">
        <a href="{{ $isOwnShift ? route('rota.mine') : route('rota.builder', $shift->rota_period_id) }}" wire:navigate class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-600">
            <i class="ik ik-arrow-left"></i>
        </a>
        <div class="min-w-0">
            <h1 class="truncate text-lg font-semibold leading-tight text-gray-800">{{ $shift->serviceUser->name ?? __('Unassigned visit') }}</h1>
            <p class="truncate text-xs text-gray-400">
                {{ $shift->scheduled_start->format('D, d M · H:i') }} – {{ $shift->scheduled_end->format('H:i') }}
                @unless ($isOwnShift)
                    <span class="mx-1 text-gray-300">·</span>{{ __('carer') }}: {{ $shift->carer->name ?? '—' }}
                @endunless
            </p>
        </div>
    </div>

    {{-- ===================== TAKEOVER REASON GATE ===================== --}}
    @if ($this->needsTakeoverReason())
        <x-card>
            <div class="flex flex-col items-center gap-3 py-4 text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-50 text-amber-500"><i class="ik ik-user-check text-xl"></i></span>
                <p class="font-semibold text-gray-700">{{ __('Covering this shift') }}</p>
                <p class="max-w-sm text-sm text-gray-500">
                    {{ __('You\'re not the carer assigned to this shift. Everything you fill in here will be clearly marked as entered by you on :carer\'s behalf, so give a short reason before continuing — this is important for safeguarding and compliance review later.', ['carer' => $shift->carer->name ?? __('the assigned carer')]) }}
                </p>
            </div>

            <div class="mx-auto max-w-sm space-y-3 pt-2">
                <x-form.textarea name="takeoverReason" label="{{ __('Why are you covering this shift?') }}" rows="3"
                    wire:model="takeoverReason" placeholder="{{ __('e.g. carer unwell, reported via phone, filling in missed records') }}" required />
                <x-button class="w-full" wire:click="startTakeover">{{ __('Continue') }}</x-button>
            </div>
        </x-card>
    @else
        {{-- ===================== PROXY BANNER ===================== --}}
        @unless ($isOwnShift)
            <div class="mb-4 flex items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-500/30 dark:bg-amber-500/10">
                <div class="flex items-center gap-2 text-sm">
                    <i class="ik ik-user-check text-amber-500"></i>
                    <span class="text-amber-700 dark:text-amber-400">
                        {{ __('You\'re covering this shift for :carer.', ['carer' => $shift->carer->name ?? __('the assigned carer')]) }}
                    </span>
                </div>
                <button type="button" wire:click="endTakeover" class="shrink-0 text-xs font-semibold text-amber-700 hover:underline dark:text-amber-400">
                    {{ __('Stop covering') }}
                </button>
            </div>
        @endunless

        {{-- Tab strip --}}
        <div class="mb-4 hidden gap-1 rounded-xl bg-gray-100 p-1 sm:flex">
            @foreach ([
                'overview' => ['label' => __('Overview'), 'icon' => 'ik-home'],
                'tasks' => ['label' => __('Tasks'), 'icon' => 'ik-check-square'],
                'medications' => ['label' => __('Medications'), 'icon' => 'ik-heart'],
                'notes' => ['label' => __('Notes'), 'icon' => 'ik-clock'],
            ] as $key => $meta)
                <button type="button" @click="tab = '{{ $key }}'"
                    :class="tab === '{{ $key }}' ? 'bg-white text-primary-600 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                    class="flex flex-1 items-center justify-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition">
                    <i class="ik {{ $meta['icon'] }}"></i>{{ $meta['label'] }}
                </button>
            @endforeach
        </div>

        {{-- ===================== OVERVIEW ===================== --}}
        <div x-show="tab === 'overview'">
            <div class="space-y-4">
                <x-card hover>
                    <x-slot:header>{{ __('Visit') }}</x-slot:header>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-gray-400">{{ __('Service user') }}</span><span class="font-semibold text-gray-700 dark:text-gray-200">{{ $shift->serviceUser->name ?? '—' }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-400">{{ __('Scheduled') }}</span><span>{{ $shift->scheduled_start->format('D, d M H:i') }} – {{ $shift->scheduled_end->format('H:i') }}</span></div>
                        @if ($shift->notes)
                            <div class="pt-1"><span class="text-gray-400">{{ __('Notes') }}</span><p class="mt-1 text-gray-700 dark:text-gray-200">{{ $shift->notes }}</p></div>
                        @endif
                    </div>
                </x-card>

                <x-card hover>
                    <x-slot:header>{{ __('Check in / out') }}</x-slot:header>
                    @if ($openCheckin)
                        <div class="flex items-center justify-between">
                            <div class="text-sm">
                                <div class="font-semibold text-gray-700 dark:text-gray-200">{{ __('Checked in') }}</div>
                                <div class="text-xs text-gray-400">{{ $openCheckin->checkin_time->format('H:i') }}</div>
                            </div>
                            <x-button variant="danger" size="sm" wire:click="checkOut">{{ __('Check out') }}</x-button>
                        </div>
                    @else
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-gray-400">{{ __('Not checked in yet.') }}</p>
                            <x-button size="sm" wire:click="checkIn">{{ __('Check in') }}</x-button>
                        </div>
                    @endif
                </x-card>

                <div class="grid grid-cols-3 gap-3">
                    <button type="button" @click="tab = 'tasks'" class="rounded-xl border border-gray-100 bg-white p-4 text-left shadow-sm hover:border-primary-200 dark:border-gray-800 dark:bg-gray-900">
                        <div class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $this->tasks->count() }}</div>
                        <div class="text-xs text-gray-400">{{ __('Task(s)') }}</div>
                    </button>
                    <button type="button" @click="tab = 'medications'" class="rounded-xl border border-gray-100 bg-white p-4 text-left shadow-sm hover:border-primary-200 dark:border-gray-800 dark:bg-gray-900">
                        <div class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $this->medications->count() }}</div>
                        <div class="text-xs text-gray-400">{{ __('Medication(s)') }}</div>
                    </button>
                    <button type="button" @click="tab = 'notes'" class="rounded-xl border border-gray-100 bg-white p-4 text-left shadow-sm hover:border-primary-200 dark:border-gray-800 dark:bg-gray-900">
                        <div class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ $this->timelineEntries->count() }}</div>
                        <div class="text-xs text-gray-400">{{ __('Recent note(s)') }}</div>
                    </button>
                </div>
            </div>
        </div>

        {{-- ===================== TASKS ===================== --}}
        <div x-show="tab === 'tasks'">
            <div class="space-y-3">
                @forelse ($this->tasks as $task)
                    @php $status = $task->status(); @endphp
                    <x-card hover wire:key="task-{{ $task->id }}">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <h6 class="font-semibold text-gray-700 dark:text-gray-200">{{ $task->title }}</h6>
                                    <x-badge color="{{ $task->priority >= 4 ? 'danger' : ($task->priority >= 3 ? 'amber' : 'secondary') }}">P{{ $task->priority }}</x-badge>
                                    @if ($task->latestLog?->shift_takeover_id)
                                        <x-badge color="secondary">{{ __('Covered') }}</x-badge>
                                    @endif
                                </div>
                                @if ($task->description)
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $task->description }}</p>
                                @endif
                                <div class="mt-2 flex gap-3 text-xs text-gray-400">
                                    @if ($task->requires_photo)
                                        <span><i class="ik ik-camera mr-1"></i>{{ __('Photo required') }}</span>
                                    @endif
                                    @if ($task->requires_signature)
                                        <span><i class="ik ik-edit-3 mr-1"></i>{{ __('Signature required') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex shrink-0 flex-col items-end gap-2">
                                <x-badge color="{{ match ($status) {
                                    'completed' => 'success',
                                    'refused' => 'danger',
                                    'skipped' => 'secondary',
                                    'overdue' => 'danger',
                                    default => 'primary',
                                } }}">{{ ucfirst($status) }}</x-badge>
                                @if (!$task->isComplete())
                                    <x-button size="sm" variant="primary" wire:click="openCompleteForm('{{ $task->id }}')"
                                        @click="$dispatch('open-drawer', 'task-complete-form')">
                                        {{ __('Mark complete') }}
                                    </x-button>
                                @endif
                            </div>
                        </div>
                    </x-card>
                @empty
                    <x-empty-state title="{{ __('Nothing scheduled') }}" description="{{ __('No tasks for this shift.') }}" icon="ik ik-check-square" />
                @endforelse
            </div>
        </div>

        {{-- ===================== MEDICATIONS (today) ===================== --}}
        <div x-show="tab === 'medications'">
            <div class="space-y-3">
                @forelse ($this->medications as $item)
                    @php [$med, $admin, $state] = [$item['medication'], $item['administration'], $item['state']]; @endphp
                    <x-card hover wire:key="med-{{ $med->id }}">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <h6 class="font-semibold text-gray-700 dark:text-gray-200">{{ $med->medication_name }}</h6>
                                    @if ($admin?->shift_takeover_id)
                                        <x-badge color="secondary">{{ __('Covered') }}</x-badge>
                                    @endif
                                </div>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $med->dosage }} · {{ ucfirst($med->administration_route ?? '—') }}
                                    @if ($med->is_prn)
                                        <span class="mx-1 text-gray-300">·</span>{{ __('As needed (PRN)') }}
                                    @elseif ($med->scheduledTimeFormatted())
                                        <span class="mx-1 text-gray-300">·</span>{{ $med->scheduledTimeFormatted() }}
                                    @endif
                                </p>
                                @if ($admin)
                                    <p class="mt-1 text-xs text-gray-400">
                                        {{ __('Recorded') }} {{ $admin->actual_time?->format('H:i') ?? '—' }} {{ __('by') }} {{ $admin->administeredBy->name ?? '—' }}
                                    </p>
                                @endif
                            </div>
                            <div class="flex shrink-0 flex-col items-end gap-2">
                                <x-badge color="{{ match ($state) {
                                    'given' => 'success',
                                    'prompted' => 'primary',
                                    'refused', 'overdue' => 'danger',
                                    'missed' => 'amber',
                                    'prn' => 'secondary',
                                    default => 'gray',
                                } }}">{{ ucfirst($state) }}</x-badge>
                                @unless ($admin)
                                    <x-button size="sm" variant="primary" wire:click="openRecordForm('{{ $med->id }}')"
                                        @click="$dispatch('open-drawer', 'mar-record-form')">
                                        {{ __('Record') }}
                                    </x-button>
                                @endunless
                            </div>
                        </div>
                    </x-card>
                @empty
                    <x-empty-state title="{{ __('No medications due') }}" description="{{ __('Nothing scheduled for this service user today.') }}" icon="ik ik-heart" />
                @endforelse
            </div>
        </div>

        {{-- ===================== NOTES ===================== --}}
        <div x-show="tab === 'notes'">
            <div class="space-y-4">
                <x-card hover>
                    <x-slot:header>{{ __('Add a note') }}</x-slot:header>
                    <div class="space-y-3">
                        <x-form.textarea name="noteContent" rows="3" wire:model="noteContent"
                            placeholder="{{ __('Quick observation about the visit...') }}" />
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                <input type="checkbox" wire:model="noteVisibleToFamily" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                {{ __('Visible to family') }}
                            </label>
                            <x-button size="sm" wire:click="addNote">{{ __('Add note') }}</x-button>
                        </div>
                    </div>
                </x-card>

                <x-card no-padding hover>
                    <x-slot:header>{{ __('Recent updates') }}</x-slot:header>
                    <div class="divide-y divide-gray-50 dark:divide-gray-800">
                        @forelse ($this->timelineEntries as $entry)
                            <div class="px-5 py-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ ucfirst(str_replace('_', ' ', $entry->entry_type)) }}</span>
                                    <span class="text-xs text-gray-400">{{ $entry->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $entry->content }}</p>
                                <p class="mt-1 text-xs text-gray-400">{{ __('by') }} {{ $entry->creator->name ?? __('a carer') }}</p>
                            </div>
                        @empty
                            <div class="px-5 py-8">
                                <x-empty-state title="{{ __('No updates yet') }}" icon="ik ik-clock" />
                            </div>
                        @endforelse
                    </div>
                </x-card>
            </div>
        </div>

        {{-- Mobile bottom tab bar — nested inside this component's single
             root element for the same wire:id/entangle reason explained in
             family-service-user.blade.php. Not a layout-level nav, since
             the carer flow is a drill-down (My Rota -> one shift), not a
             multi-destination app shell. --}}
        <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-gray-100 bg-white/95 backdrop-blur sm:hidden"
            style="padding-bottom: env(safe-area-inset-bottom);">
            <div class="mx-auto grid max-w-3xl grid-cols-4">
                @foreach ([
                    'overview' => ['label' => __('Visit'), 'icon' => 'ik-home'],
                    'tasks' => ['label' => __('Tasks'), 'icon' => 'ik-check-square'],
                    'medications' => ['label' => __('Meds'), 'icon' => 'ik-heart'],
                    'notes' => ['label' => __('Notes'), 'icon' => 'ik-clock'],
                ] as $key => $meta)
                    <button type="button" @click="tab = '{{ $key }}'"
                        :class="tab === '{{ $key }}' ? 'text-primary-600' : 'text-gray-400'"
                        class="flex flex-col items-center gap-0.5 py-2.5 text-[11px] font-medium">
                        <i class="ik {{ $meta['icon'] }} text-lg"></i>
                        {{ $meta['label'] }}
                    </button>
                @endforeach
            </div>
        </nav>
    @endif

    <x-drawer name="task-complete-form" title="{{ __('Complete task') }}" width="w-[28rem]">
        <div class="space-y-4">
            <x-form.select name="completeStatus" label="{{ __('Outcome') }}" wire:model="completeStatus" required>
                <option value="completed">{{ __('Completed') }}</option>
                <option value="refused">{{ __('Refused by service user') }}</option>
                <option value="skipped">{{ __('Skipped') }}</option>
            </x-form.select>

            <x-form.textarea name="completeNotes" label="{{ __('Notes') }}" rows="3" wire:model="completeNotes"
                placeholder="{{ __('What happened, any observations...') }}" />

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('Photo') }}</label>
                <input type="file" wire:model="completePhoto" accept="image/*"
                    class="block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-primary-50 file:px-3 file:py-1.5 file:text-primary-600 hover:file:bg-primary-100 dark:file:bg-primary-500/10">
                <div wire:loading wire:target="completePhoto" class="mt-1 text-xs text-gray-400">{{ __('Uploading...') }}</div>
                @if ($completePhoto)
                    <img src="{{ $completePhoto->temporaryUrl() }}" class="mt-2 h-24 w-24 rounded-lg object-cover">
                @endif
                @error('completePhoto')
                    <p class="mt-1 text-xs text-accent-500">{{ $message }}</p>
                @enderror
            </div>

            <div x-data="signaturePad()" x-on:reset-signature-pad.window="clear()">
                <label class="mb-1 block text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('Signature') }}</label>
                <canvas x-ref="canvas" width="380" height="140"
                    class="w-full touch-none rounded-lg border border-gray-200 bg-white dark:border-gray-700"
                    x-on:mousedown="start" x-on:mousemove="draw" x-on:mouseup="end" x-on:mouseleave="end"
                    x-on:touchstart.prevent="start" x-on:touchmove.prevent="draw" x-on:touchend.prevent="end"></canvas>
                <button type="button" x-on:click="clear()" class="mt-1 text-xs text-gray-400 hover:text-gray-600">{{ __('Clear signature') }}</button>
                @error('completeSignatureData')
                    <p class="mt-1 text-xs text-accent-500">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <x-slot:footer>
            <x-button wire:click="completeTask">{{ __('Save') }}</x-button>
        </x-slot:footer>
    </x-drawer>

    <x-drawer name="mar-record-form" title="{{ __('Record administration') }}" width="w-[28rem]">
        <div class="space-y-4">
            <x-form.select name="recordStatus" label="{{ __('Outcome') }}" wire:model.live="recordStatus" required>
                <option value="given">{{ __('Given') }}</option>
                <option value="prompted">{{ __('Prompted (self-administered with support)') }}</option>
                <option value="refused">{{ __('Refused') }}</option>
                <option value="missed">{{ __('Missed') }}</option>
            </x-form.select>

            @if ($recordStatus !== 'missed')
                <x-form.input type="datetime-local" name="recordActualTime" label="{{ __('Actual time') }}" wire:model="recordActualTime" required />
            @endif

            @if ($recordStatus === 'refused')
                <x-form.textarea name="recordRefusalReason" label="{{ __('Reason for refusal') }}" rows="2" wire:model="recordRefusalReason" required />
            @endif

            <x-form.textarea name="recordNotes" label="{{ __('Notes (optional)') }}" rows="2" wire:model="recordNotes" />
            <x-form.input name="recordWitness" label="{{ __('Witnessed by (optional)') }}" wire:model="recordWitness"
                placeholder="{{ __('Name of witnessing colleague, if applicable') }}" />

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('Photo (optional)') }}</label>
                <input type="file" wire:model="recordPhoto" accept="image/*"
                    class="block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-primary-50 file:px-3 file:py-1.5 file:text-primary-600 hover:file:bg-primary-100 dark:file:bg-primary-500/10">
                @if ($recordPhoto)
                    <img src="{{ $recordPhoto->temporaryUrl() }}" class="mt-2 h-20 w-20 rounded-lg object-cover">
                @endif
                @error('recordPhoto')
                    <p class="mt-1 text-xs text-accent-500">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <x-slot:footer>
            <x-button wire:click="recordAdministration">{{ __('Save') }}</x-button>
        </x-slot:footer>
    </x-drawer>
</div>

@script
    <script>
        Alpine.data('signaturePad', () => ({
            drawing: false,
            ctx: null,

            init() {
                const canvas = this.$refs.canvas;
                this.ctx = canvas.getContext('2d');
                this.ctx.strokeStyle = '#374151';
                this.ctx.lineWidth = 2;
                this.ctx.lineCap = 'round';
            },

            point(e) {
                const rect = this.$refs.canvas.getBoundingClientRect();
                const src = e.touches ? e.touches[0] : e;
                return {
                    x: src.clientX - rect.left,
                    y: src.clientY - rect.top
                };
            },

            start(e) {
                this.drawing = true;
                const p = this.point(e);
                this.ctx.beginPath();
                this.ctx.moveTo(p.x, p.y);
            },

            draw(e) {
                if (!this.drawing) return;
                const p = this.point(e);
                this.ctx.lineTo(p.x, p.y);
                this.ctx.stroke();
            },

            end() {
                if (!this.drawing) return;
                this.drawing = false;
                $wire.set('completeSignatureData', this.$refs.canvas.toDataURL('image/png'));
            },

            clear() {
                this.ctx.clearRect(0, 0, this.$refs.canvas.width, this.$refs.canvas.height);
                $wire.set('completeSignatureData', '');
            },
        }));
    </script>
@endscript
