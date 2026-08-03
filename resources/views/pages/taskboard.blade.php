@extends('layouts.main')
@section('title', 'Taskboard')
@section('content')
    <x-page-header
        title="{{ __('Taskboard') }}"
        subtitle="{{ __('Drag and drop tasks between columns to organize your workflow') }}"
        icon="ik ik-check-square"
        :breadcrumbs="['Home' => url('dashboard'), 'Taskboard' => null]" />

    <div x-data="taskboard()">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-4">

        <template x-for="column in columns" :key="column.id">
            <div
                class="rounded-2xl bg-gray-50 p-4 transition-colors"
                :class="dragOverCol === column.id ? 'ring-2 ring-primary-400 bg-primary-50/40' : ''"
                @dragover.prevent="dragOverCol = column.id"
                @dragenter.prevent="dragOverCol = column.id"
                @dragleave="if ($event.target === $el) dragOverCol = null"
                @drop.prevent="drop(column.id)">

                {{-- Column header --}}
                <div class="mb-4 flex items-center justify-between border-b-2 pb-2" :class="column.color">
                    <div class="flex items-center gap-2">
                        <h3 class="font-semibold text-gray-700" x-text="column.title"></h3>
                        <x-badge color="gray" x-text="column.tasks.length"></x-badge>
                    </div>
                    <button
                        type="button"
                        class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-white hover:text-primary-600"
                        title="{{ __('Add task') }}"
                        @click="openAdd(column.id)">
                        <i class="ik ik-plus"></i>
                    </button>
                </div>

                {{-- Task list --}}
                <div class="min-h-[120px] space-y-3">
                    <template x-for="task in column.tasks" :key="task.id">
                        <div
                            draggable="true"
                            class="group cursor-grab rounded-xl border border-gray-100 bg-white p-4 shadow-sm transition active:cursor-grabbing"
                            :class="dragging.taskId === task.id ? 'opacity-50' : 'hover:shadow-md'"
                            @dragstart="dragStart(task.id, column.id)"
                            @dragend="dragEnd()">

                            <div class="mb-2 flex items-start justify-between gap-2">
                                <h6 class="font-semibold text-gray-700" x-text="task.title"></h6>
                                <button
                                    type="button"
                                    class="opacity-0 text-gray-300 transition-opacity hover:text-accent-600 group-hover:opacity-100"
                                    title="{{ __('Delete task') }}"
                                    @click="removeTask(column.id, task.id)">
                                    <i class="ik ik-x"></i>
                                </button>
                            </div>

                            <p class="mb-3 text-sm text-gray-400" x-text="task.desc" x-show="task.desc"></p>

                            <div class="flex items-center justify-between">
                                <span
                                    class="inline-flex items-center rounded px-2 py-0.5 text-xs font-medium"
                                    :class="priorityClass(task.priority)">
                                    <i class="ik ik-flag mr-1"></i><span x-text="task.priority"></span>
                                </span>
                                <span class="inline-flex items-center rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500" x-show="task.tag" x-text="task.tag"></span>
                            </div>
                        </div>
                    </template>

                    {{-- Empty state --}}
                    <p
                        x-show="column.tasks.length === 0"
                        class="rounded-xl border border-dashed border-gray-200 py-6 text-center text-sm text-gray-300">
                        {{ __('Drop tasks here') }}
                    </p>
                </div>
            </div>
        </template>
        </div>

        {{-- Add Task modal --}}
        @php
            $modalField = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 outline-none transition placeholder:text-gray-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-100';
            $modalSelect = 'w-full appearance-none rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-100';
        @endphp
        <div x-show="modalOpen" x-cloak x-transition.opacity
             @keydown.escape.window="modalOpen = false"
             x-effect="modalOpen && $nextTick(() => $refs.titleInput && $refs.titleInput.focus())"
             class="fixed inset-0 z-[90] flex items-start justify-center overflow-y-auto bg-black/40 p-4 sm:items-center">
            <div x-show="modalOpen" x-trap.noscroll="modalOpen" @click.outside="modalOpen = false"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 translate-y-3 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 role="dialog" aria-modal="true"
                 class="my-8 w-full max-w-lg rounded-2xl bg-white shadow-xl">

                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <h3 class="font-semibold text-gray-800">{{ __('Add Task') }}</h3>
                    <button type="button" @click="modalOpen = false" class="text-gray-400 transition hover:text-gray-600" aria-label="{{ __('Close') }}"><i class="ik ik-x"></i></button>
                </div>

                <form @submit.prevent="saveTask()">
                    <div class="space-y-4 p-5">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Title') }}<span class="text-accent-500">*</span></label>
                            <input x-ref="titleInput" x-model="form.title" type="text" required placeholder="{{ __('What needs to be done?') }}" class="{{ $modalField }}">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Description') }}</label>
                            <textarea x-model="form.desc" rows="3" placeholder="{{ __('Add more detail (optional)') }}" class="{{ $modalField }}"></textarea>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Column') }}</label>
                                <div class="relative">
                                    <select x-model="form.colId" class="{{ $modalSelect }} pr-9">
                                        <template x-for="c in columns" :key="c.id"><option :value="c.id" x-text="c.title"></option></template>
                                    </select>
                                    <i class="ik ik-chevron-down pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                </div>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Priority') }}</label>
                                <div class="relative">
                                    <select x-model="form.priority" class="{{ $modalSelect }} pr-9">
                                        <option value="High">{{ __('High') }}</option>
                                        <option value="Medium">{{ __('Medium') }}</option>
                                        <option value="Low">{{ __('Low') }}</option>
                                    </select>
                                    <i class="ik ik-chevron-down pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                </div>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Tag') }}</label>
                                <input x-model="form.tag" type="text" placeholder="{{ __('e.g. Design') }}" class="{{ $modalField }}">
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-gray-100 px-5 py-4">
                        <x-button type="button" variant="secondary" @click="modalOpen = false">{{ __('Cancel') }}</x-button>
                        <x-button type="submit" variant="primary"><i class="ik ik-plus"></i> {{ __('Add Task') }}</x-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function taskboard() {
            return {
                dragging: { taskId: null, fromCol: null },
                dragOverCol: null,
                nextId: 100,
                modalOpen: false,
                form: { colId: 'todo', title: '', desc: '', tag: '', priority: 'Medium' },
                columns: [
                    {
                        id: 'todo',
                        title: 'To Do',
                        color: 'border-primary-300',
                        tasks: [
                            { id: 1, title: 'Design new dashboard', desc: 'Sketch wireframes and define the layout grid for the analytics dashboard.', tag: 'Design', priority: 'High' },
                            { id: 2, title: 'Set up CI pipeline', desc: 'Configure automated tests and deployment workflow.', tag: 'DevOps', priority: 'Medium' },
                            { id: 3, title: 'Write API docs', desc: 'Document the REST endpoints for the public API.', tag: 'Docs', priority: 'Low' },
                        ],
                    },
                    {
                        id: 'progress',
                        title: 'In Progress',
                        color: 'border-amber-300',
                        tasks: [
                            { id: 4, title: 'Refactor auth module', desc: 'Migrate authentication to the new token-based flow.', tag: 'Backend', priority: 'High' },
                            { id: 5, title: 'Team sync meeting', desc: 'Weekly standup to align on sprint goals.', tag: 'Meeting', priority: 'Medium' },
                        ],
                    },
                    {
                        id: 'review',
                        title: 'Review',
                        color: 'border-accent-300',
                        tasks: [
                            { id: 6, title: 'Code review: payments', desc: 'Review the Stripe integration pull request.', tag: 'Review', priority: 'High' },
                        ],
                    },
                    {
                        id: 'done',
                        title: 'Done',
                        color: 'border-green-300',
                        tasks: [
                            { id: 7, title: 'Fix login redirect bug', desc: 'Resolved the redirect loop after session expiry.', tag: 'Bugfix', priority: 'Medium' },
                            { id: 8, title: 'Update dependencies', desc: 'Bumped all packages to the latest stable versions.', tag: 'Chore', priority: 'Low' },
                        ],
                    },
                ],

                priorityClass(priority) {
                    switch (priority) {
                        case 'High':   return 'bg-accent-500/15 text-accent-600';
                        case 'Medium': return 'bg-amber-100 text-amber-700';
                        case 'Low':    return 'bg-green-100 text-green-700';
                        default:       return 'bg-gray-100 text-gray-700';
                    }
                },

                findCol(id) {
                    return this.columns.find(c => c.id === id);
                },

                dragStart(taskId, fromCol) {
                    this.dragging = { taskId, fromCol };
                },

                dragEnd() {
                    this.dragging = { taskId: null, fromCol: null };
                    this.dragOverCol = null;
                },

                drop(targetColId) {
                    const { taskId, fromCol } = this.dragging;
                    this.dragOverCol = null;
                    if (taskId === null || fromCol === null) return;

                    const source = this.findCol(fromCol);
                    const target = this.findCol(targetColId);
                    if (!source || !target) return;

                    const index = source.tasks.findIndex(t => t.id === taskId);
                    if (index === -1) return;

                    const [task] = source.tasks.splice(index, 1);
                    target.tasks.push(task);

                    this.dragging = { taskId: null, fromCol: null };
                },

                openAdd(colId) {
                    this.form = { colId: colId || 'todo', title: '', desc: '', tag: '', priority: 'Medium' };
                    this.modalOpen = true;
                },

                saveTask() {
                    const title = this.form.title.trim();
                    if (!title) return;

                    const column = this.findCol(this.form.colId);
                    if (!column) return;

                    column.tasks.push({
                        id: ++this.nextId,
                        title,
                        desc: this.form.desc.trim(),
                        tag: this.form.tag.trim(),
                        priority: this.form.priority,
                    });

                    this.modalOpen = false;
                },

                removeTask(colId, taskId) {
                    const column = this.findCol(colId);
                    if (!column) return;
                    const index = column.tasks.findIndex(t => t.id === taskId);
                    if (index !== -1) column.tasks.splice(index, 1);
                },
            };
        }
    </script>
@endsection
