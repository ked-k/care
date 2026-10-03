@php
    $selectClass = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100';
@endphp

<div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
    <div class="lg:col-span-1">
        <x-card>
            <div class="text-center">
                <img src="{{ asset('img/user.jpg') }}" class="mx-auto h-32 w-32 rounded-full object-cover" alt="">
                <h4 class="mt-4 text-lg font-semibold text-gray-800">{{ $name }}</h4>
                <p class="text-sm text-gray-400">{{ $email }}</p>
                <div class="mt-4 flex justify-center gap-6 text-sm">
                    <a href="#" class="text-gray-600 hover:text-primary-600"><i class="ik ik-user"></i> <span class="font-medium">{{ __('Profile') }}</span></a>
                </div>
            </div>
            <hr class="my-5 border-gray-100">
            <div class="space-y-3 text-sm">
                <div><span class="block text-xs text-gray-400">{{ __('Email address') }}</span><span class="font-medium text-gray-700">{{ $email }}</span></div>
                <div><span class="block text-xs text-gray-400">{{ __('Phone') }}</span><span class="font-medium text-gray-700">{{ $phone }}</span></div>
                <div><span class="block text-xs text-gray-400">{{ __('Address') }}</span><span class="font-medium text-gray-700">{{ $address }}</span></div>
            </div>
        </x-card>
    </div>

    <div class="lg:col-span-2" x-data="{ tab: 'profile' }">
        <x-card no-padding>
            <div class="flex gap-1 border-b border-gray-100 px-5 pt-4">
                @foreach (['profile' => __('Profile'), 'setting' => __('Setting')] as $key => $label)
                    <button @click="tab = '{{ $key }}'"
                            :class="tab === '{{ $key }}' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="border-b-2 px-4 py-2 text-sm font-medium transition">{{ $label }}</button>
                @endforeach
            </div>

            <div class="p-5">
                <div x-show="tab === 'profile'" class="space-y-6">
                    <div class="flex gap-4">
                        <img src="{{ asset('img/users/1.jpg') }}" alt="" class="h-10 w-10 rounded-full object-cover">
                        <div class="flex-1">
                            <a href="#" class="font-medium text-gray-700">{{ $name }}</a>
                            <p class="mt-2 text-sm text-gray-600">{{ __('Basic profile information for the current user.') }}</p>
                        </div>
                    </div>
                </div>

                <div x-show="tab === 'setting'" x-cloak style="display:none;">
                    <form wire:submit.prevent="updateProfile" class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Full Name') }}</label>
                            <input type="text" wire:model.defer="name" class="{{ $selectClass }}">
                            @error('name') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Email') }}</label>
                            <input type="email" wire:model.defer="email" class="{{ $selectClass }}">
                            @error('email') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Phone No') }}</label>
                            <input type="text" wire:model.defer="phone" class="{{ $selectClass }}">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Address') }}</label>
                            <textarea wire:model.defer="address" rows="3" class="{{ $selectClass }}"></textarea>
                        </div>
                        <x-button variant="success" type="submit">{{ __('Update Profile') }}</x-button>
                    </form>
                </div>
            </div>
        </x-card>
    </div>
</div>
