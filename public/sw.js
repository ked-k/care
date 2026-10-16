/**
 * Batch 24: minimal "fast-load caching" service worker — NOT an offline/
 * data-caching layer. It only ever touches same-origin GET requests for
 * static, non-personal assets (Vite build output, images, icon fonts, the
 * manifests/icons themselves) and leaves every HTML page, every Livewire
 * request (wire:*, /livewire/*), and every POST completely alone, so there
 * is no path by which this can show stale care/medication/safeguarding
 * data. Full offline page viewing was deliberately scoped out when this
 * was asked for — see this batch's CHANGES doc and the "Offline access"
 * item already sitting on the roadmap as its own, separate piece of work.
 *
 * Strategy for anything it does cache: cache-first, with a background
 * re-fetch to keep the cache warm for next time (stale-while-revalidate) —
 * repeat visits feel instant without ever blocking on the network, and the
 * cache self-heals within one extra load if an asset changes.
 */

const CACHE_NAME = 'caretrust-static-v1';

const CACHEABLE_PATH_PATTERNS = [
    /^\/build\//,       // Vite-built, content-hashed CSS/JS — safe to cache aggressively
    /^\/images\//,
    /^\/img\//,
    /^\/plugins\//,      // fontawesome / iconkit / ionicons css+fonts
    /^\/favicon/,
    /^\/manifest(-[a-z]+)?\.json$/,
];

function isCacheable(url) {
    if (url.origin !== self.location.origin) return false;
    return CACHEABLE_PATH_PATTERNS.some((re) => re.test(url.pathname));
}

self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((names) => Promise.all(names.filter((n) => n !== CACHE_NAME).map((n) => caches.delete(n))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.method !== 'GET') return; // never touch a Livewire action / form submit
    if (request.headers.get('X-Livewire')) return; // extra guard: never intercept a Livewire request

    const url = new URL(request.url);
    if (!isCacheable(url)) return; // let the browser handle everything else exactly as normal

    event.respondWith(
        caches.open(CACHE_NAME).then(async (cache) => {
            const cached = await cache.match(request);

            const refresh = fetch(request)
                .then((response) => {
                    if (response && response.ok) cache.put(request, response.clone());
                    return response;
                })
                .catch(() => null);

            if (cached) {
                event.waitUntil(refresh);
                return cached;
            }

            return (await refresh) || fetch(request);
        })
    );
});
