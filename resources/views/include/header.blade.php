<header class="fixed inset-x-0 top-0 z-30 h-16 border-b border-black/5 bg-topbar text-topbar-text lg:pl-60">
    <div class="flex h-full items-center justify-between px-4 sm:px-6">

        <!-- Left -->
        <div class="flex items-center gap-2">
            <button type="button" @click="sidebarOpen = ! sidebarOpen"
                    class="flex h-9 w-9 items-center justify-center rounded-lg text-topbar-text/70 hover:bg-gray-500/10 lg:hidden">
                <i class="ik ik-menu"></i>
            </button>

            <!-- Section switcher -->
            @include('include.topmenu')

            <button type="button" @click="$dispatch('open-command')"
                    class="hidden items-center gap-2 rounded-lg border border-gray-500/15 bg-gray-500/5 py-2 pl-3 pr-2 text-sm text-topbar-text/50 transition hover:bg-gray-500/10 md:flex">
                <i class="ik ik-search"></i>
                <span class="w-24 text-left lg:w-32">{{ __('Search...') }}</span>
                <kbd class="rounded border border-gray-500/20 px-1.5 py-0.5 text-[10px] font-medium">⌘K</kbd>
            </button>
            <button type="button" @click="$dispatch('open-command')" title="{{ __('Search') }}"
                    class="flex h-9 w-9 items-center justify-center rounded-lg text-topbar-text/70 hover:bg-gray-500/10 md:hidden">
                <i class="ik ik-search"></i>
            </button>

            <button type="button" onclick="document.fullscreenElement ? document.exitFullscreen() : document.documentElement.requestFullscreen()"
                    class="hidden h-9 w-9 items-center justify-center rounded-lg text-topbar-text/70 hover:bg-gray-500/10 sm:flex">
                <i class="ik ik-maximize"></i>
            </button>
        </div>

        <!-- Right -->
        <div class="flex items-center gap-1">

            <!-- Quick create -->
            <x-dropdown width="w-52">
                <x-slot:trigger>
                    <button class="flex h-9 items-center gap-1.5 rounded-lg bg-primary-500/10 px-2.5 text-sm font-medium text-primary-600 transition hover:bg-primary-500/15" title="{{ __('Quick create') }}">
                        <i class="ik ik-plus"></i><span class="hidden sm:inline">{{ __('Create') }}</span>
                    </button>
                </x-slot:trigger>
                <div class="px-3 py-1.5 text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ __('Quick create') }}</div>
                <a href="{{ url('sales/create') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"><i class="ik ik-shopping-cart text-gray-400"></i> {{ __('New Sale') }}</a>
                <a href="{{ url('income/invoice/create') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"><i class="ik ik-file-text text-gray-400"></i> {{ __('New Invoice') }}</a>
                <a href="{{ url('products/create') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"><i class="ik ik-plus-square text-gray-400"></i> {{ __('New Product') }}</a>
                <div class="my-1 border-t border-gray-100"></div>
                @can('manage_user')
                    <a href="{{ url('user/create') }}" class="flex items-center gap-2.5 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"><i class="ik ik-user-plus text-gray-400"></i> {{ __('New User') }}</a>
                @endcan
            </x-dropdown>

            <!-- Notifications -->
            <x-dropdown width="w-80">
                <x-slot:trigger>
                    <button class="relative flex h-9 w-9 items-center justify-center rounded-lg text-topbar-text/70 hover:bg-gray-500/10">
                        <i class="ik ik-bell"></i>
                        <span class="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-accent-500 px-1 text-[10px] font-semibold text-white">3</span>
                    </button>
                </x-slot:trigger>

                <div class="border-b border-gray-100 px-4 py-2 text-sm font-semibold text-gray-700">{{ __('Notifications') }}</div>
                <div class="max-h-80 overflow-y-auto">
                    <a href="#" class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-600"><i class="ik ik-check"></i></span>
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-gray-700">{{ __('Invitation accepted') }}</span>
                            <span class="block truncate text-xs text-gray-500">{{ __('Your have been Invited ...') }}</span>
                        </span>
                    </a>
                    <a href="#" class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50">
                        <img src="{{ asset('img/users/1.jpg') }}" class="h-9 w-9 shrink-0 rounded-full object-cover" alt="">
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-gray-700">{{ __('Steve Smith') }}</span>
                            <span class="block truncate text-xs text-gray-500">{{ __('I slowly updated projects') }}</span>
                        </span>
                    </a>
                    <a href="#" class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary-100 text-primary-600"><i class="ik ik-calendar"></i></span>
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-gray-700">{{ __('To Do') }}</span>
                            <span class="block truncate text-xs text-gray-500">{{ __('Meeting with Nathan on Friday 8 AM ...') }}</span>
                        </span>
                    </a>
                </div>
                <a href="javascript:void(0);" class="block border-t border-gray-100 px-4 py-2 text-center text-sm font-medium text-primary-600 hover:bg-gray-50">{{ __('See all activity') }}</a>
            </x-dropdown>

            <!-- Chat drawer toggle -->
            <button type="button" @click="chatOpen = true"
                    class="relative flex h-9 w-9 items-center justify-center rounded-lg text-topbar-text/70 hover:bg-gray-500/10">
                <i class="ik ik-message-square"></i>
                <span class="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-green-500 px-1 text-[10px] font-semibold text-white">3</span>
            </button>

            <!-- App grid -->
            <button type="button" @click="$dispatch('open-apps-modal')"
                    class="flex h-9 w-9 items-center justify-center rounded-lg text-topbar-text/70 hover:bg-gray-500/10">
                <i class="ik ik-grid"></i>
            </button>

            <!-- Dark mode toggle -->
            <button type="button" title="{{ __('Toggle dark mode') }}"
                    x-data="{ dark: document.documentElement.classList.contains('dark') }"
                    @click="dark = ! dark; document.documentElement.classList.toggle('dark', dark); localStorage.setItem('radmin-dark', dark ? '1' : '0')"
                    class="flex h-9 w-9 items-center justify-center rounded-lg text-topbar-text/70 hover:bg-gray-500/10">
                <i :class="dark ? 'ik ik-sun' : 'ik ik-moon'"></i>
            </button>

            <!-- Theme customizer -->
            <button type="button" @click="$dispatch('open-theme')" title="{{ __('Customize theme') }}"
                    class="flex h-9 w-9 items-center justify-center rounded-lg text-topbar-text/70 hover:bg-gray-500/10">
                <i class="ik ik-droplet"></i>
            </button>

            <!-- User -->
            <x-dropdown width="w-48">
                <x-slot:trigger>
                    <button class="ml-1 flex items-center">
                        <img class="h-9 w-9 rounded-full object-cover ring-2 ring-gray-500/15" src="{{ asset('img/user.jpg') }}" alt="">
                    </button>
                </x-slot:trigger>

                <a href="{{ url('profile') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"><i class="ik ik-user text-gray-400"></i> {{ __('Profile') }}</a>
                <a href="#" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"><i class="ik ik-navigation text-gray-400"></i> {{ __('Message') }}</a>
                <div class="my-1 border-t border-gray-100"></div>
                <a href="{{ url('clear-cache') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50" title="{{ __('Flush application & view cache') }}"><i class="ik ik-refresh-cw text-gray-400"></i> {{ __('Clear cache') }}</a>
                <div class="my-1 border-t border-gray-100"></div>
                <a href="{{ url('logout') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"><i class="ik ik-power text-gray-400"></i> {{ __('Logout') }}</a>
            </x-dropdown>
        </div>
    </div>
</header>
