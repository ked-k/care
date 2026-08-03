<aside class="fixed inset-y-0 left-0 z-40 flex w-60 flex-col bg-sidebar transition-transform duration-300 lg:translate-x-0"
       :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">

    <div class="flex h-16 shrink-0 items-center justify-between bg-sidebar-header px-5">
        <a href="{{ route('dashboard') }}" class="flex items-center text-white" title="{{ config('app.name') }}">
            <x-brand-logo markClass="h-8 w-8" textClass="text-lg" />
        </a>
        <button @click="sidebarOpen = false" class="text-sidebar-text hover:text-white lg:hidden"><i class="ik ik-x"></i></button>
    </div>

    <nav class="scrollbar-thin flex-1 overflow-y-auto py-2">
        <x-nav.menu :items="config('menu.inventory')" />
    </nav>
</aside>
