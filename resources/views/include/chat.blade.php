<!-- Chat drawer (toggled by header message icon via the shared `chatOpen` state) -->
<div x-data="chatDrawer()" x-show="chatOpen" x-transition.opacity
     @keydown.escape.window="active ? (active = null) : (chatOpen = false)"
     x-effect="if (! chatOpen) active = null"
     class="fixed inset-0 z-50 bg-black/40" style="display:none"
     @click.self="chatOpen = false">

    {{-- ===================== CHAT LIST DRAWER ===================== --}}
    <aside class="absolute inset-y-0 right-0 flex w-80 max-w-full flex-col bg-white shadow-xl"
           x-transition:enter="transition ease-out duration-200"
           x-transition:enter-start="translate-x-full"
           x-transition:enter-end="translate-x-0">

        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
            <h6 class="font-semibold text-gray-700">{{ __('Chat List') }}</h6>
            <button @click="chatOpen = false" class="text-gray-400 hover:text-gray-600"><i class="ik ik-x"></i></button>
        </div>

        <div class="border-b border-gray-100 px-4 py-3">
            <div class="relative">
                <i class="ik ik-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" x-model="search" placeholder="{{ __('Search for friends ...') }}"
                       class="w-full rounded-lg border border-gray-200 bg-gray-50 py-2 pl-9 pr-3 text-sm outline-none focus:border-primary-400 focus:bg-white focus:ring-2 focus:ring-primary-100">
            </div>
        </div>

        <div class="flex-1 overflow-y-auto">
            <template x-for="c in filtered" :key="c.handle">
                <button type="button" @click="open(c)"
                        class="flex w-full items-center gap-3 px-4 py-2.5 text-left transition hover:bg-gray-50"
                        :class="active && active.handle === c.handle ? 'bg-primary-50' : ''">
                    <span class="relative shrink-0">
                        <img :src="c.img" class="h-10 w-10 rounded-full object-cover" alt="">
                        <span class="absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-white" :class="c.status"></span>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-center justify-between gap-2">
                            <span class="truncate text-sm font-semibold text-gray-700" x-text="c.name"></span>
                            <span class="shrink-0 text-[11px] text-gray-400" x-text="c.time"></span>
                        </span>
                        <span class="flex items-center justify-between gap-2">
                            <span class="truncate text-xs text-gray-400" x-text="c.preview"></span>
                            <span x-show="c.unread" x-cloak class="flex h-4 min-w-4 shrink-0 items-center justify-center rounded-full bg-primary-500 px-1 text-[10px] font-semibold text-white" x-text="c.unread"></span>
                        </span>
                    </span>
                </button>
            </template>
            <p x-show="filtered.length === 0" x-cloak class="px-4 py-10 text-center text-sm text-gray-400">{{ __('No conversations found') }}</p>
        </div>
    </aside>

    {{-- ===================== SMALL FLOATING CHAT PANEL ===================== --}}
    <div x-show="active" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-3"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="absolute inset-x-3 bottom-3 flex h-[26rem] max-h-[calc(100%-1.5rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5
                sm:inset-x-auto sm:bottom-4 sm:right-[21rem] sm:w-72">

        <div class="flex items-center gap-2.5 border-b border-gray-100 bg-white px-3 py-2.5">
            <span class="relative shrink-0">
                <img :src="active?.img" class="h-8 w-8 rounded-full object-cover" alt="">
                <span class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full border-2 border-white" :class="active?.status"></span>
            </span>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-gray-700" x-text="active?.name"></p>
                <p class="truncate text-[11px]" :class="active?.online ? 'text-green-500' : 'text-gray-400'" x-text="active?.online ? '{{ __('Online') }}' : '{{ __('Offline') }}'"></p>
            </div>
            <button @click="active = null" class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600" title="{{ __('Close chat') }}"><i class="ik ik-x text-sm"></i></button>
        </div>

        <div x-ref="thread" class="flex-1 space-y-2.5 overflow-y-auto bg-gray-50 px-3 py-3">
            <template x-for="(m, i) in (active?.messages || [])" :key="i">
                <div class="flex" :class="m.from === 'me' ? 'justify-end' : 'justify-start'">
                    <div class="max-w-[80%]">
                        <div class="rounded-2xl px-3 py-1.5 text-[13px] leading-snug"
                             :class="m.from === 'me' ? 'rounded-br-sm bg-primary-500 text-white' : 'rounded-bl-sm bg-white text-gray-700 shadow-sm'"
                             x-text="m.text"></div>
                        <p class="mt-0.5 text-[10px] text-gray-400" :class="m.from === 'me' ? 'text-right' : ''" x-text="m.time"></p>
                    </div>
                </div>
            </template>
        </div>

        <form @submit.prevent="send()" class="flex items-center gap-2 border-t border-gray-100 px-2.5 py-2.5">
            <input type="text" x-model="draft" placeholder="{{ __('Type a message...') }}"
                   class="flex-1 rounded-full border border-gray-200 bg-gray-50 px-3.5 py-1.5 text-sm outline-none focus:border-primary-400 focus:bg-white focus:ring-2 focus:ring-primary-100">
            <button type="submit" :disabled="! draft.trim()"
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-500 text-white transition hover:bg-primary-600 disabled:opacity-40">
                <i class="ik ik-send text-sm"></i>
            </button>
        </form>
    </div>
</div>

<script>
    function chatDrawer() {
        return {
            search: '',
            draft: '',
            active: null,
            contacts: [
                { name: 'Gene Newman', handle: '@gene_newman', img: '{{ asset('img/users/1.jpg') }}', status: 'bg-green-500', online: true, time: '2m', unread: 2, preview: 'Sounds good — see you then!', messages: [
                    { from: 'them', text: 'Hey! Are we still on for the demo today?', time: '09:02' },
                    { from: 'me', text: 'Absolutely, 3pm works for me.', time: '09:05' },
                    { from: 'them', text: 'Sounds good — see you then!', time: '09:06' },
                ]},
                { name: 'Billy Black', handle: '@billyblack', img: '{{ asset('img/users/2.jpg') }}', status: 'bg-green-500', online: true, time: '18m', unread: 0, preview: 'I pushed the latest changes.', messages: [
                    { from: 'them', text: 'I pushed the latest changes.', time: '08:40' },
                    { from: 'me', text: 'Great, reviewing now 👍', time: '08:42' },
                ]},
                { name: 'Herbert Diaz', handle: '@herbert', img: '{{ asset('img/users/3.jpg') }}', status: 'bg-green-500', online: true, time: '1h', unread: 0, preview: 'Thanks for the help!', messages: [
                    { from: 'me', text: 'Fixed that bug you reported.', time: 'Yesterday' },
                    { from: 'them', text: 'Thanks for the help!', time: 'Yesterday' },
                ]},
                { name: 'Sylvia Harvey', handle: '@sylvia', img: '{{ asset('img/users/4.jpg') }}', status: 'bg-amber-500', online: false, time: '3h', unread: 1, preview: 'Can you send the invoice?', messages: [
                    { from: 'them', text: 'Can you send the invoice?', time: '06:15' },
                ]},
                { name: 'Marsha Hoffman', handle: '@m_hoffman', img: '{{ asset('img/users/5.jpg') }}', status: 'bg-amber-500', online: false, time: '5h', unread: 0, preview: 'Perfect, talk soon.', messages: [
                    { from: 'me', text: 'Scheduled the meeting for Friday.', time: 'Mon' },
                    { from: 'them', text: 'Perfect, talk soon.', time: 'Mon' },
                ]},
                { name: 'Mason Grant', handle: '@masongrant', img: '{{ asset('img/users/1.jpg') }}', status: 'bg-gray-300', online: false, time: '1d', unread: 0, preview: 'See you at the standup.', messages: [
                    { from: 'them', text: 'See you at the standup.', time: 'Sun' },
                ]},
                { name: 'Shelly Sullivan', handle: '@shelly', img: '{{ asset('img/users/2.jpg') }}', status: 'bg-gray-300', online: false, time: '2d', unread: 0, preview: 'Happy to help anytime.', messages: [
                    { from: 'them', text: 'Happy to help anytime.', time: 'Sat' },
                ]},
            ],
            get filtered() {
                const q = this.search.toLowerCase();
                return this.contacts.filter(c => c.name.toLowerCase().includes(q) || c.handle.toLowerCase().includes(q));
            },
            open(c) {
                this.active = c;
                c.unread = 0;
                this.draft = '';
                this.$nextTick(() => this.scrollDown());
            },
            send() {
                const text = this.draft.trim();
                if (! text || ! this.active) return;
                this.active.messages.push({ from: 'me', text, time: 'now' });
                this.active.preview = text;
                this.active.time = 'now';
                this.draft = '';
                this.$nextTick(() => this.scrollDown());
            },
            scrollDown() {
                const el = this.$refs.thread;
                if (el) el.scrollTop = el.scrollHeight;
            },
        };
    }
</script>
