<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Shop\ShopGrn;
use App\Models\Shop\ShopOrder;
use App\Models\Shop\ShopStore;
use Illuminate\Support\Facades\Auth;

class ShopAdminController extends Controller
{
    private function store(): ShopStore
    {
        abort_unless(Auth::user()->agency, 403);

        return ShopStore::forAgency(Auth::user()->agency);
    }

    /** Sidebar "View storefront" — opens this agency's public shop (preview if not live). */
    public function storefront()
    {
        return redirect()->route('shop.home', $this->store()->slug);
    }

    /** Printable packing slip / delivery note for one order. */
    public function packingSlip(string $orderId)
    {
        $store = $this->store();
        $order = ShopOrder::where('store_id', $store->id)->with('items.lotAllocations.lot')->findOrFail($orderId);

        return view('shop.admin.packing-slip', compact('store', 'order'));
    }

    /** Batch 42: printable goods received note. */
    public function grnPrint(string $grnId)
    {
        $store = $this->store();
        $grn = ShopGrn::where('store_id', $store->id)->with(['lines', 'supplier', 'receiver', 'creator'])->findOrFail($grnId);

        return view('shop.admin.grn-print', compact('store', 'grn'));
    }
}
