@extends('layouts.main')
@section('title', 'Profile')
@section('content')
    <x-page-header title="{{ __('Profile') }}" subtitle="{{ __('lorem ipsum dolor sit amet, consectetur adipisicing elit') }}" icon="ik ik-file-text"
                   :breadcrumbs="['Home' => route('dashboard'), 'Pages' => null, 'Profile' => null]" />

    @php
        $selectClass = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100';
    @endphp

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="lg:col-span-1">
            <x-card>
                <div class="text-center">
                    <img src="{{ asset('img/user.jpg') }}" class="mx-auto h-32 w-32 rounded-full object-cover" alt="">
                    <h4 class="mt-4 text-lg font-semibold text-gray-800">{{ __('John Doe') }}</h4>
                    <p class="text-sm text-gray-400">{{ __('Front End Developer') }}</p>
                    <div class="mt-4 flex justify-center gap-6 text-sm">
                        <a href="javascript:void(0)" class="text-gray-600 hover:text-primary-600"><i class="ik ik-user"></i> <span class="font-medium">254</span></a>
                        <a href="javascript:void(0)" class="text-gray-600 hover:text-primary-600"><i class="ik ik-image"></i> <span class="font-medium">54</span></a>
                    </div>
                </div>
                <hr class="my-5 border-gray-100">
                <div class="space-y-3 text-sm">
                    <div><span class="block text-xs text-gray-400">{{ __('Email address') }}</span><span class="font-medium text-gray-700">johndoe@admin.com</span></div>
                    <div><span class="block text-xs text-gray-400">{{ __('Phone') }}</span><span class="font-medium text-gray-700">(123) 456 7890</span></div>
                    <div><span class="block text-xs text-gray-400">{{ __('Address') }}</span><span class="font-medium text-gray-700">71 Pilgrim Avenue Chevy Chase, MD 20815</span></div>
                </div>
                <div class="mt-4 overflow-hidden rounded-lg">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d248849.886539092!2d77.49085452149588!3d12.953959988118836!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3bae1670c9b44e6d%3A0xf8dfc3e8517e4fe0!2sBengaluru%2C+Karnataka!5e0!3m2!1sen!2sin!4v1542005497600" width="100%" height="220" style="border:0" allowfullscreen></iframe>
                </div>
                <div class="mt-5">
                    <span class="block text-xs text-gray-400">{{ __('Social Profile') }}</span>
                    <div class="mt-2 flex gap-2">
                        <button class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-500 text-white"><i class="fab fa-facebook-f"></i></button>
                        <button class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-400 text-white"><i class="fab fa-twitter"></i></button>
                        <button class="flex h-9 w-9 items-center justify-center rounded-lg bg-accent-500 text-white"><i class="fab fa-instagram"></i></button>
                    </div>
                </div>
            </x-card>
        </div>

        <div class="lg:col-span-2" x-data="{ tab: 'timeline' }">
            <x-card no-padding>
                <div class="flex gap-1 border-b border-gray-100 px-5 pt-4">
                    @foreach (['timeline' => __('Timeline'), 'profile' => __('Profile'), 'setting' => __('Setting')] as $key => $label)
                        <button @click="tab = '{{ $key }}'"
                                :class="tab === '{{ $key }}' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                                class="border-b-2 px-4 py-2 text-sm font-medium transition">{{ $label }}</button>
                    @endforeach
                </div>

                <div class="p-5">
                    <div x-show="tab === 'timeline'" class="space-y-6">
                        @php
                            $timeline = [
                                ['1.jpg', 'assign a new task', 'Design weblayout', ['img2.jpg', 'img3.jpg', 'img4.jpg', 'img5.jpg']],
                            ];
                        @endphp
                        <div class="flex gap-4">
                            <img src="{{ asset('img/users/1.jpg') }}" alt="" class="h-10 w-10 rounded-full object-cover">
                            <div class="flex-1">
                                <a href="javascript:void(0)" class="font-medium text-gray-700">John Doe</a> <span class="text-xs text-gray-400">5 minutes ago</span>
                                <p class="text-sm text-gray-600">assign a new task <a href="javascript:void(0)" class="text-primary-600">Design weblayout</a></p>
                                <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                                    @foreach (['img2.jpg', 'img3.jpg', 'img4.jpg', 'img5.jpg'] as $img)
                                        <img src="{{ asset('img/big/'.$img) }}" class="rounded-lg" alt="">
                                    @endforeach
                                </div>
                                <div class="mt-3 flex gap-4 text-sm">
                                    <a href="javascript:void(0)" class="text-primary-600">2 comment</a>
                                    <a href="javascript:void(0)" class="text-primary-600"><i class="fa fa-heart text-accent-500"></i> 5 Love</a>
                                </div>
                            </div>
                        </div>
                        <hr class="border-gray-100">
                        <div class="flex gap-4">
                            <img src="{{ asset('img/users/2.jpg') }}" alt="" class="h-10 w-10 rounded-full object-cover">
                            <div class="flex-1">
                                <a href="javascript:void(0)" class="font-medium text-gray-700">John Doe</a> <span class="text-xs text-gray-400">5 minutes ago</span>
                                <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-4">
                                    <img src="{{ asset('img/big/img6.jpg') }}" alt="" class="rounded-lg md:col-span-1">
                                    <div class="md:col-span-3">
                                        <p class="text-sm text-gray-600">{{ __('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer nec odio. Praesent libero. Sed cursus ante dapibus diam.') }}</p>
                                        <x-button variant="success" size="sm" href="javascript:void(0)" class="mt-2">Design weblayout</x-button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr class="border-gray-100">
                        <div class="flex gap-4">
                            <img src="{{ asset('img/users/3.jpg') }}" alt="" class="h-10 w-10 rounded-full object-cover">
                            <div class="flex-1">
                                <a href="javascript:void(0)" class="font-medium text-gray-700">John Doe</a> <span class="text-xs text-gray-400">5 minutes ago</span>
                                <p class="mt-2 text-sm text-gray-600">{{ __(' Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer nec odio. Praesent libero. Sed cursus ante dapibus diam. Sed nisi. Nulla quis sem at nibh elementum imperdiet. Duis sagittis ipsum. Praesent mauris. Fusce nec tellus sed augue semper') }}</p>
                            </div>
                        </div>
                        <hr class="border-gray-100">
                        <div class="flex gap-4">
                            <img src="{{ asset('img/users/4.jpg') }}" alt="" class="h-10 w-10 rounded-full object-cover">
                            <div class="flex-1">
                                <a href="javascript:void(0)" class="font-medium text-gray-700">John Doe</a> <span class="text-xs text-gray-400">5 minutes ago</span>
                                <blockquote class="mt-2 border-l-2 border-gray-200 pl-3 text-sm italic text-gray-600">{{ __('Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt') }}</blockquote>
                            </div>
                        </div>
                    </div>

                    <div x-show="tab === 'profile'" x-cloak style="display:none;">
                        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                            <div><strong class="text-sm text-gray-700">{{ __('Full Name') }}</strong><p class="text-sm text-gray-400">Johnathan Deo</p></div>
                            <div><strong class="text-sm text-gray-700">{{ __('Mobile') }}</strong><p class="text-sm text-gray-400">(123) 456 7890</p></div>
                            <div><strong class="text-sm text-gray-700">{{ __('Email') }}</strong><p class="text-sm text-gray-400">johnathan@admin.com</p></div>
                            <div><strong class="text-sm text-gray-700">{{ __('Location') }}</strong><p class="text-sm text-gray-400">London</p></div>
                        </div>
                        <hr class="my-5 border-gray-100">
                        <p class="text-sm text-gray-600">{{ __('Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt.Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim.') }}</p>
                        <p class="mt-3 text-sm text-gray-600">{{ __('Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industrys standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries') }}</p>
                        <p class="mt-3 text-sm text-gray-600">{{ __('It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.') }}</p>
                        <h4 class="mt-6 font-semibold text-gray-700">{{ __('Skill Set') }}</h4>
                        <hr class="my-4 border-gray-100">
                        @foreach ([[__('Wordpress'), 80, 'bg-green-500'], [__('HTML 5'), 90, 'bg-primary-500'], [__('jQuery'), 50, 'bg-accent-500'], [__('Photoshop'), 70, 'bg-amber-500']] as [$skill, $pct, $bar])
                            <h6 class="mt-4 flex justify-between text-sm font-medium text-gray-700">{{ $skill }} <span>{{ $pct }}%</span></h6>
                            <div class="mt-1 h-2 rounded-full bg-gray-100">
                                <div class="h-full rounded-full {{ $bar }}" style="width: {{ $pct }}%"></div>
                            </div>
                        @endforeach
                    </div>

                    <div x-show="tab === 'setting'" x-cloak style="display:none;">
                        <form class="space-y-4">
                            <div>
                                <label for="example-name" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Full Name') }}</label>
                                <input type="text" id="example-name" placeholder="Johnathan Doe" class="{{ $selectClass }}">
                            </div>
                            <div>
                                <label for="example-email" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Email') }}</label>
                                <input type="email" id="example-email" placeholder="johnathan@admin.com" class="{{ $selectClass }}">
                            </div>
                            <div>
                                <label for="example-password" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Password') }}</label>
                                <input type="password" id="example-password" value="password" class="{{ $selectClass }}">
                            </div>
                            <div>
                                <label for="example-phone" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Phone No') }}</label>
                                <input type="text" id="example-phone" placeholder="123 456 7890" class="{{ $selectClass }}">
                            </div>
                            <div>
                                <label for="example-message" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Message') }}</label>
                                <textarea id="example-message" rows="5" class="{{ $selectClass }}"></textarea>
                            </div>
                            <div>
                                <label for="example-country" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Select Country') }}</label>
                                <select id="example-country" class="{{ $selectClass }}">
                                    <option>{{ __('London') }}</option>
                                    <option>{{ __('India') }}</option>
                                    <option>{{ __('Usa') }}</option>
                                    <option>{{ __('Canada') }}</option>
                                    <option>{{ __('Thailand') }}</option>
                                </select>
                            </div>
                            <x-button variant="success" type="submit">Update Profile</x-button>
                        </form>
                    </div>
                </div>
            </x-card>
        </div>
    </div>
@endsection
