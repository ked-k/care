<div x-data x-on:toast.window="$store.toast.push($event.detail.message, $event.detail.type)">
    <x-page-header title="{{ __('Tasks') }}"
        subtitle="{{ $shiftId ? __('Tasks for this shift') : __('Your tasks for the selected day') }}"
        icon="ik ik-check-square" :breadcrumbs="['Home' => url('dashboard'), 'Tasks' => null]">
        @unless ($shiftId)
            <div class="flex items-center gap-2">
                <input type="date" wire:model.live="dateFilter"
                    class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm text-gray-600 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                <select wire:model.live="statusFilter"
                    class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm text-gray-600 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    <option value="">{{ __('All statuses') }}</option>
                    <option value="pending">{{ __('Pending') }}</option>
                    <option value="overdue">{{ __('Overdue') }}</option>
                    <option value="completed">{{ __('Completed') }}</option>
                    <option value="refused">{{ __('Refused') }}</option>
                    <option value="skipped">{{ __('Skipped') }}</option>
                </select>
            </div>
        @endunless
    </x-page-header>

    <div class="space-y-3">
        @forelse ($tasks as $task)
            @php $status = $task->status(); @endphp
            <x-card hover wire:key="task-{{ $task->id }}">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h6 class="font-semibold text-gray-700 dark:text-gray-200">{{ $task->title }}</h6>
                            <x-badge
                                color="{{ $task->priority >= 4 ? 'danger' : ($task->priority >= 3 ? 'amber' : 'secondary') }}">
                                P{{ $task->priority }}
                            </x-badge>
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ $task->carePlan->serviceUser->name ?? '—' }}
                            @if ($task->due_at)
                                <span class="mx-1 text-gray-300">·</span>{{ __('Due') }}
                                {{ $task->due_at->format('H:i') }}
                            @endif
                        </p>
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
                        <x-badge
                            color="{{ match ($status) {
                                'completed' => 'success',
                                'refused' => 'danger',
                                'skipped' => 'secondary',
                                'overdue' => 'danger',
                                default => 'primary',
                            } }}">
                            {{ ucfirst($status) }}
                        </x-badge>
                        @if (!$task->isComplete())
                            <x-button size="sm" variant="primary"
                                wire:click="openCompleteForm('{{ $task->id }}')"
                                @click="$dispatch('open-drawer', 'task-complete-form')">
                                {{ __('Mark complete') }}
                            </x-button>
                        @endif
                    </div>
                </div>
            </x-card>
        @empty
            <x-empty-state title="{{ __('Nothing scheduled') }}"
                description="{{ __('No tasks match this day/filter.') }}" icon="ik ik-check-square" />
        @endforelse
    </div>

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
                <label
                    class="mb-1 block text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('Photo') }}</label>
                <input type="file" wire:model="completePhoto" accept="image/*"
                    class="block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-primary-50 file:px-3 file:py-1.5 file:text-primary-600 hover:file:bg-primary-100 dark:file:bg-primary-500/10">
                <div wire:loading wire:target="completePhoto" class="mt-1 text-xs text-gray-400">
                    {{ __('Uploading...') }}</div>
                @if ($completePhoto)
                    <img src="{{ $completePhoto->temporaryUrl() }}" class="mt-2 h-24 w-24 rounded-lg object-cover">
                @endif
                @error('completePhoto')
                    <p class="mt-1 text-xs text-accent-500">{{ $message }}</p>
                @enderror
            </div>

            <div x-data="signaturePad()" x-on:reset-signature-pad.window="clear()">
                <label
                    class="mb-1 block text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('Signature') }}</label>
                <canvas x-ref="canvas" width="380" height="140"
                    class="w-full touch-none rounded-lg border border-gray-200 bg-white dark:border-gray-700"
                    x-on:mousedown="start" x-on:mousemove="draw" x-on:mouseup="end" x-on:mouseleave="end"
                    x-on:touchstart.prevent="start" x-on:touchmove.prevent="draw" x-on:touchend.prevent="end"></canvas>
                <button type="button" x-on:click="clear()" class="mt-1 text-xs text-gray-400 hover:text-gray-600">
                    {{ __('Clear signature') }}
                </button>
                @error('completeSignatureData')
                    <p class="mt-1 text-xs text-accent-500">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <x-slot:footer>
            <x-button wire:click="completeTask">{{ __('Save') }}</x-button>
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
