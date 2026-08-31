{{--
    Superseded — no longer included from layouts/admin-layout.blade.php.

    The chat drawer (trigger button + panel) is now App\Livewire\Messaging\
    ChatDrawerComponent, embedded directly from include/header.blade.php so
    the toggle button and the drawer are the same Livewire instance sharing
    real data (App\Models\Message) instead of this file's old hardcoded
    demo contacts/messages. See resources/views/livewire/messaging/chat-drawer.blade.php.

    Left in place rather than deleted, since this session has no way to
    delete files on your machine — safe to remove by hand if you'd like.
--}}
