<!-- App launcher modal (opened via header grid icon dispatching `open-apps-modal`) -->
<div x-data="{ open: false }" @open-apps-modal.window="open = true" @keydown.escape.window="open = false"
     x-show="open" x-transition.opacity
     class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-white/95 p-6 backdrop-blur" style="display:none">

    <button @click="open = false" class="absolute right-5 top-5 text-2xl text-gray-400 hover:text-gray-600"><i class="ik ik-x-circle"></i></button>

    <div class="w-full max-w-3xl py-12" @click.outside="open = false">
        <div class="mx-auto mb-8 max-w-sm">
            <div class="relative">
                <i class="ik ik-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" placeholder="{{ __('Search...') }}"
                       class="w-full rounded-lg border border-gray-200 bg-white py-2.5 pl-9 pr-3 text-sm outline-none focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
            </div>
        </div>

        @php
            $apps = [
                ['ik ik-bar-chart-2', 'Dashboard'], ['ik ik-command', 'Ui'], ['ik ik-mail', 'Message'],
                ['ik ik-users', 'Accounts'], ['ik ik-shopping-cart', 'Sales'], ['ik ik-briefcase', 'Purchase'],
                ['ik ik-server', 'Menus'], ['ik ik-clipboard', 'Pages'], ['ik ik-message-square', 'Chats'],
                ['ik ik-map-pin', 'Contacts'], ['ik ik-box', 'Blocks'], ['ik ik-calendar', 'Events'],
                ['ik ik-bell', 'Notifications'], ['ik ik-pie-chart', 'Reports'], ['ik ik-layers', 'Tasks'],
                ['ik ik-edit', 'Blogs'], ['ik ik-settings', 'Settings'], ['ik ik-more-horizontal', 'More'],
            ];
        @endphp
        <div class="grid grid-cols-3 gap-4 sm:grid-cols-4 md:grid-cols-6">
            @foreach ($apps as [$icon, $label])
                <a href="#" class="flex flex-col items-center gap-2 rounded-xl p-4 text-gray-500 transition hover:bg-gray-50 hover:text-primary-600">
                    <i class="{{ $icon }} text-2xl"></i>
                    <span class="text-xs font-medium">{{ __($label) }}</span>
                </a>
            @endforeach
        </div>
    </div>
</div>
