<?php

namespace App\Http\Middleware;

use App\Models\Shop\ShopStore;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

/**
 * Batch 40: resolves /store/{storeSlug} to a ShopStore.
 *
 * A shop that isn't switched live yet is a 404 for the public, but its own
 * agency's staff (anyone who can manage the shop) can preview it while
 * they set it up — they see a "preview" bar instead.
 */
class ResolveShopStore
{
    public function handle(Request $request, Closure $next)
    {
        $store = ShopStore::where('slug', $request->route('storeSlug'))->first();
        abort_unless($store && self::canView($store), 404);

        app()->instance('shop.store', $store);
        View::share('shopStore', $store);

        return $next($request);
    }

    public static function canView(ShopStore $store): bool
    {
        return $store->is_live || self::isStaffPreview($store);
    }

    public static function isStaffPreview(ShopStore $store): bool
    {
        $user = Auth::guard('web')->user();

        return $user
            && ($user->hasRole('Super Admin') || ($user->agency_id === $store->agency_id && $user->can('manage_shop')));
    }
}
