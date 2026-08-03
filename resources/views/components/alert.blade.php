{{-- Session flash (success/error/status) is surfaced as a toast (see <x-toast>).
     This component now renders only the inline validation-error summary for forms. --}}
@if ($errors->any())
    <div x-data="{ show: true }" x-show="show" x-transition
         class="mb-4 rounded-lg border border-accent-500/30 bg-accent-500/10 px-4 py-3 text-sm text-accent-600">
        <div class="mb-1 flex items-center justify-between">
            <span class="font-semibold">{{ __('Please fix the following:') }}</span>
            <button @click="show = false" class="opacity-60 hover:opacity-100" aria-label="{{ __('Dismiss') }}"><i class="ik ik-x"></i></button>
        </div>
        <ul class="list-inside list-disc space-y-0.5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
