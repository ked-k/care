<?php

namespace App\Support\Shop;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Batch 43b: which shop (by slug) owns which web address. Read on every
 * request, so it's cached; ShopStore clears the cache when saved.
 */
class ShopDomains
{
    private static ?array $map = null;

    /** @return array<string, array{0: string, 1: bool}> domain => [slug, is live] */
    public static function map(): array
    {
        if (self::$map !== null) {
            return self::$map;
        }
        try {
            return self::$map = Cache::remember('shop.domains', 600, function () {
                if (! Schema::hasColumn('shop_stores', 'domain')) {
                    return [];
                }

                return \App\Models\Shop\ShopStore::whereNotNull('domain')->get(['slug', 'domain', 'is_live'])
                    ->mapWithKeys(fn ($s) => [strtolower($s->domain) => [$s->slug, (bool) $s->is_live]])->all();
            });
        } catch (Throwable) {
            return self::$map = []; // database not reachable yet (install, artisan) — no shop domains
        }
    }

    public static function slugForHost(?string $host): ?string
    {
        return $host ? (self::map()[strtolower($host)][0] ?? null) : null;
    }

    /**
     * The web address links should use for a shop. Only once the shop is
     * live — while staff are still setting it up they preview it on the
     * main site, where they're signed in.
     */
    public static function domainForSlug(?string $slug, bool $liveOnly = true): ?string
    {
        foreach ($slug ? self::map() : [] as $domain => [$s, $live]) {
            if ($s === $slug && ($live || ! $liveOnly)) {
                return $domain;
            }
        }

        return null;
    }

    public static function forget(): void
    {
        self::$map = null;
        Cache::forget('shop.domains');
    }

    /** "https://Shop.Example.com/" → "shop.example.com" */
    public static function normalise(?string $value): ?string
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('#^https?://#', '', $value);
        $value = trim(explode('/', $value)[0]);

        return $value === '' ? null : $value;
    }
}
