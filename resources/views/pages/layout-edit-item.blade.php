@extends('layouts.main')
@section('title', 'Layout Item')
@section('content')
    <x-page-header title="{{ __('Edit Layout Item') }}" subtitle="{{ __('lorem ipsum dolor sit amet, consectetur adipisicing elit') }}" icon="ik ik-file-text"
                   :breadcrumbs="['Home' => route('dashboard'), 'Pages' => null, 'Layouts' => null, 'Edit' => null]" />

    @php
        $selectClass = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100';
    @endphp

    <x-card>
        <x-slot:header>{{ __('Edit Layout Item') }}</x-slot:header>
        <form class="space-y-4">
            <x-form.input name="exampleInputTitle" label="{{ __('Title') }}" placeholder="Name" value="Donec felis urna, commodo eget velit interdum, malesuada lacinia eros." />
            <x-form.input name="exampleInputSubtitle" label="{{ __('Subtitle') }}" placeholder="Name" value="Nullam elementum aliquam porta." />
            <div>
                <label for="description" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Description') }}</label>
                <textarea id="description" rows="10" class="{{ $selectClass }}"><p>{{ __('Interdum et malesuada fames ac ante ipsum primis in faucibus. Phasellus fermentum imperdiet ligula, et mollis quam sagittis ac. In quis interdum sem. Vivamus blandit fringilla hendrerit. Suspendisse vulputate sapien vitae mi convallis dictum. Sed blandit felis ut quam accumsan, at condimentum nibh varius. Mauris ornare ultricies ipsum.') }}</p>
<h2>{{ __('Hello, world!') }}</h2>
<p>{{ __('This is a simple hero unit, a simple jumbotron-style component for calling extra attention to featured content or information.') }}</p>
<p>{{ __('Praesent eleifend ac felis dignissim mattis. Suspendisse eget congue enim, ac fermentum risus. Donec eget risus lacus. Nullam nec lectus quis tortor ultrices consectetur. Etiam dui erat, tristique vel quam a, maximus porttitor enim. Ut molestie turpis in est iaculis, ut congue massa porta.') }}</p></textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Images upload') }}</label>
                <div class="flex h-40 items-center justify-center rounded-lg border border-dashed border-gray-200 bg-gray-50 text-gray-300"><i class="ik ik-upload text-3xl"></i></div>
            </div>
        </form>
        <x-slot:actions>
            <x-button variant="secondary">{{ __('Save Changes') }}</x-button>
            <x-button variant="ghost">{{ __('Cancel') }}</x-button>
        </x-slot:actions>
    </x-card>
@endsection
