<?php

namespace App\Http\Middleware;

use App\Support\Shop\ShopDomains;
use Closure;
use Illuminate\Http\Request;

/**
 * Batch 43b: lets a shop live on its own web address.
 *
 * On the shop's host (say shop.affinityhealthcareltd.co.uk) every request
 * is quietly mapped onto the storefront routes — "/" becomes
 * "/store/affinity", "/product/gloves" becomes "/store/affinity/product/
 * gloves" — so the existing routes, middleware and Livewire components all
 * work unchanged, and nothing else in CareAffinity (login, dashboard, staff
 * pages) can be reached from that host: it simply isn't a shop page, so
 * it's a 404.
 *
 * On any other host, a public GET to /store/{slug}/… for a live shop that
 * has its own address is sent there with a permanent (301) redirect, so old
 * links and search results land on the right place.
 *
 * Registered as global middleware (app/Http/Kernel.php), so it runs before
 * the router matches anything.
 */
class ServeShopDomain
{
    /** Paths that stay as they are on the shop host. */
    private const PASS_THROUGH = '#^/(livewire[^/]*/|store-webhooks/|up$)#';

    public function handle(Request $request, Closure $next)
    {
        $slug = ShopDomains::slugForHost($request->getHost());
        $path = '/'.ltrim($request->getPathInfo(), '/');

        if ($slug) {
            app()->instance('shop.domain_slug', $slug);

            // Someone typed the long form on the shop host — tidy it.
            if (preg_match('#^/store/'.preg_quote($slug, '#').'(/.*)?$#', $path, $m) && $request->isMethod('GET')) {
                return redirect()->to(($m[1] ?? '/') ?: '/', 301);
            }
            if (preg_match(self::PASS_THROUGH, $path)) {
                return $next($request);
            }

            $mapped = '/store/'.$slug.($path === '/' ? '' : $path);
            $qs = $request->getQueryString();
            $server = $request->server->all();
            $server['REQUEST_URI'] = $mapped.($qs ? '?'.$qs : '');
            $server['PATH_INFO'] = $mapped;
            $mappedRequest = $request->duplicate(null, null, null, null, null, $server);

            return $next($mappedRequest);
        }

        // Old /store/{slug} links on the main site → the shop's own address.
        if ($request->isMethod('GET') && preg_match('#^/store/([^/]+)(/.*)?$#', $path, $m)
            && ($domain = ShopDomains::domainForSlug($m[1]))) {
            $qs = $request->getQueryString();

            return redirect()->away('https://'.$domain.($m[2] ?? '').($qs ? '?'.$qs : ''), 301);
        }

        return $next($request);
    }
}
