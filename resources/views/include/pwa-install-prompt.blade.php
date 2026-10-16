{{--
    Batch 28: a visible "Install" banner, not just the browser's own address-
    bar icon or buried "Add to Home screen" menu item — ked asked where the
    click-to-install actually was, and the honest answer was "nowhere
    obvious." Rendered as the very first thing in <body> on every layout
    that carries public/manifest.json (auth-split, admin-layout, carer,
    family), so it sits above the sticky header in normal document flow —
    no fixed positioning, no z-index fight, no collision with carer's fixed
    bottom tab bar. Hidden by default; only shown once the browser actually
    confirms the app is installable (or, on iOS, which never fires that
    event, with instructions instead of a button — Safari has no API to
    trigger "Add to Home Screen" programmatically).

    Scrolls away with the page once the user scrolls — deliberate: a nudge
    on arrival, not a persistent nag.
--}}
<div id="pwa-install-banner" style="display:none;"
    class="flex items-center justify-between gap-3 bg-[#1e3a5f] px-4 py-2.5 text-sm text-white sm:px-6">
    <div class="flex min-w-0 items-center gap-2.5">
        <img src="{{ asset('images/brand/pwa/icon-192.png') }}" alt="" class="h-6 w-6 shrink-0 rounded-md">
        <span id="pwa-install-banner-text-default" class="truncate">
            {{ __('Install :app for quicker access.', ['app' => config('app.name')]) }}
        </span>
        <span id="pwa-install-banner-text-ios" class="truncate" style="display:none;">
            {{ __('Install :app: tap Share, then "Add to Home Screen".', ['app' => config('app.name')]) }}
        </span>
    </div>
    <div class="flex shrink-0 items-center gap-2">
        <button type="button" id="pwa-install-button"
            class="rounded-md bg-white/15 px-3 py-1 font-medium transition hover:bg-white/25">{{ __('Install') }}</button>
        <button type="button" id="pwa-install-dismiss" aria-label="{{ __('Dismiss') }}"
            class="px-1 text-lg leading-none text-white/70 transition hover:text-white">&times;</button>
    </div>
</div>
<script>
    (function () {
        var DISMISS_KEY = 'pwaInstallDismissedAt';
        var DISMISS_DAYS = 14;

        var banner = document.getElementById('pwa-install-banner');
        var defaultText = document.getElementById('pwa-install-banner-text-default');
        var iosText = document.getElementById('pwa-install-banner-text-ios');
        var installBtn = document.getElementById('pwa-install-button');
        var dismissBtn = document.getElementById('pwa-install-dismiss');
        var deferredPrompt = null;

        function isStandalone() {
            return (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches)
                || window.navigator.standalone === true;
        }

        function recentlyDismissed() {
            try {
                var t = parseInt(localStorage.getItem(DISMISS_KEY) || '0', 10);
                return t && (Date.now() - t) < DISMISS_DAYS * 24 * 60 * 60 * 1000;
            } catch (e) {
                return false;
            }
        }

        function show() {
            if (isStandalone() || recentlyDismissed()) return;
            banner.style.display = 'flex';
        }

        function hide() {
            banner.style.display = 'none';
        }

        dismissBtn.addEventListener('click', function () {
            hide();
            try { localStorage.setItem(DISMISS_KEY, String(Date.now())); } catch (e) {}
        });

        window.addEventListener('appinstalled', hide);

        if (isStandalone() || recentlyDismissed()) {
            return;
        }

        // Android/desktop Chrome and Edge: Chrome withholds this event until
        // it decides the page is "install-worthy" (a bit of engagement,
        // valid manifest, registered service worker) — until it fires,
        // there's genuinely nothing to prompt yet.
        window.addEventListener('beforeinstallprompt', function (e) {
            e.preventDefault();
            deferredPrompt = e;
            show();
        });

        installBtn.addEventListener('click', function () {
            if (!deferredPrompt) return;
            deferredPrompt.prompt();
            deferredPrompt.userChoice.finally(function () {
                deferredPrompt = null;
                hide();
            });
        });

        // iOS Safari never fires beforeinstallprompt and has no JS API for
        // triggering "Add to Home Screen" — the button can't do anything
        // there, so swap in instructions instead and hide the button.
        var isIos = /iphone|ipad|ipod/.test(window.navigator.userAgent.toLowerCase());
        if (isIos) {
            defaultText.style.display = 'none';
            iosText.style.display = '';
            installBtn.style.display = 'none';
            show();
        }
    })();
</script>
