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

    Batch 28 fix: originally styled with Tailwind utility classes
    (bg-[#1e3a5f], bg-white/15, text-white/70) — but bg-[#1e3a5f] was an
    arbitrary-value class that appeared NOWHERE else in the app, so
    Tailwind's build-time scan never had a reason to generate it before
    this file existed, and it stayed invisible (transparent background,
    white text, on a white page) until the next full asset rebuild picked
    it up. Rewritten with a small scoped <style> block and plain CSS below
    instead, so the banner looks right the moment this file is deployed —
    no dependency on when/whether `npm run build` next runs.
--}}
<style>
    #pwa-install-banner {
        display: none;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        background-color: #1e3a5f;
        color: #ffffff;
        padding: 0.625rem 1rem;
        font-size: 0.875rem;
        line-height: 1.4;
    }
    #pwa-install-banner .pwa-install-left {
        display: flex;
        align-items: center;
        gap: 0.625rem;
        min-width: 0;
    }
    #pwa-install-banner img {
        height: 1.5rem;
        width: 1.5rem;
        flex-shrink: 0;
        border-radius: 0.375rem;
    }
    #pwa-install-banner span {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    #pwa-install-banner .pwa-install-actions {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-shrink: 0;
    }
    #pwa-install-button {
        background-color: rgba(255, 255, 255, 0.18);
        color: #ffffff;
        border: 0;
        border-radius: 0.375rem;
        padding: 0.25rem 0.75rem;
        font-weight: 600;
        font-size: 0.875rem;
        cursor: pointer;
        transition: background-color 0.15s ease;
    }
    #pwa-install-button:hover {
        background-color: rgba(255, 255, 255, 0.3);
    }
    #pwa-install-dismiss {
        background: none;
        border: 0;
        color: rgba(255, 255, 255, 0.75);
        font-size: 1.25rem;
        line-height: 1;
        padding: 0 0.25rem;
        cursor: pointer;
        transition: color 0.15s ease;
    }
    #pwa-install-dismiss:hover {
        color: #ffffff;
    }
    @media (min-width: 640px) {
        #pwa-install-banner {
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }
    }
</style>
<div id="pwa-install-banner">
    <div class="pwa-install-left">
        <img src="{{ asset('images/brand/pwa/icon-192.png') }}" alt="">
        <span id="pwa-install-banner-text-default">
            {{ __('Install :app for quicker access.', ['app' => config('app.name')]) }}
        </span>
        <span id="pwa-install-banner-text-ios" style="display:none;">
            {{ __('Install :app: tap Share, then "Add to Home Screen".', ['app' => config('app.name')]) }}
        </span>
    </div>
    <div class="pwa-install-actions">
        <button type="button" id="pwa-install-button">{{ __('Install') }}</button>
        <button type="button" id="pwa-install-dismiss" aria-label="{{ __('Dismiss') }}">&times;</button>
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
