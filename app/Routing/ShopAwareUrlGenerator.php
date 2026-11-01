<?php

namespace App\Routing;

use App\Support\Shop\ShopDomains;
use Illuminate\Routing\UrlGenerator;

/**
 * Batch 43b: makes every link to a public shop page (route('shop.…'))
 * point at that shop's own web address when it has one —
 * route('shop.product', ['affinity', 'gloves']) gives
 * https://shop.affinityhealthcareltd.co.uk/product/gloves instead of
 * https://care…/store/affinity/product/gloves. That covers links in pages,
 * order emails, Stripe/PayPal return URLs and the staff "View shop" buttons
 * without touching any of them. Shops without a web address keep
 * /store/{slug} exactly as before.
 */
class ShopAwareUrlGenerator extends UrlGenerator
{
    /** Re-create Laravel's configured generator as this class, keeping all its settings. */
    public static function from(UrlGenerator $url): static
    {
        $new = (new \ReflectionClass(static::class))->newInstanceWithoutConstructor();
        foreach (get_object_vars($url) as $key => $value) {
            $new->{$key} = $value;
        }

        return $new;
    }

    public function toRoute($route, $parameters, $absolute)
    {
        $name = (string) $route->getName();
        if (! str_starts_with($route->uri(), 'store/{storeSlug}') || ! str_starts_with($name, 'shop.')) {
            return parent::toRoute($route, $parameters, $absolute);
        }

        $slug = $this->slugFrom($parameters);
        $domain = ShopDomains::domainForSlug($slug);
        if (! $domain) {
            return parent::toRoute($route, $parameters, $absolute);
        }

        $path = parent::toRoute($route, $parameters, false); // "/store/affinity/product/gloves?x=1"
        $clean = preg_replace('#^/store/'.preg_quote($slug, '#').'(?=/|\?|$)#', '', $path);
        $clean = $clean === '' || $clean[0] === '?' ? '/'.$clean : $clean;

        $onShopHost = $this->request && strtolower($this->request->getHost()) === $domain;
        if (! $absolute && $onShopHost) {
            return $clean;
        }
        $root = $onShopHost ? $this->request->getSchemeAndHttpHost() : 'https://'.$domain;

        return $root.($clean === '/' ? '' : $clean);
    }

    /**
     * On the shop's own host the request has been mapped to /store/{slug}/…
     * (see ServeShopDomain), so hide that again in url()->current() / full().
     */
    public function current()
    {
        return $this->cleanCurrent(parent::current());
    }

    public function full()
    {
        return $this->cleanCurrent(parent::full());
    }

    private function cleanCurrent(string $url): string
    {
        if (! app()->bound('shop.domain_slug')) {
            return $url;
        }
        $slug = preg_quote(app('shop.domain_slug'), '#');

        return preg_replace('#^(https?://[^/?]+)/store/'.$slug.'(?=/|\?|$)#', '$1', $url);
    }

    private function slugFrom($parameters): ?string
    {
        if ($parameters instanceof \App\Models\Shop\ShopStore) {
            return $parameters->slug;
        }
        if (! is_array($parameters)) {
            return is_scalar($parameters) ? (string) $parameters : null;
        }
        if (isset($parameters['storeSlug'])) {
            $p = $parameters['storeSlug'];

            return $p instanceof \App\Models\Shop\ShopStore ? $p->slug : (string) $p;
        }
        foreach ($parameters as $k => $p) {
            if (is_int($k)) {
                return $p instanceof \App\Models\Shop\ShopStore ? $p->slug : (string) $p;
            }
        }

        return null;
    }
}
