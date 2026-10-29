<?php

use App\Http\Controllers\Shop\ShopAdminController;
use App\Livewire\Shop\Admin\CategoryIndexComponent;
use App\Livewire\Shop\Admin\CustomerIndexComponent;
use App\Livewire\Shop\Admin\DeliveryMethodIndexComponent;
use App\Livewire\Shop\Admin\DiscountIndexComponent;
use App\Livewire\Shop\Admin\GoodsReceivedFormComponent;
use App\Livewire\Shop\Admin\GoodsReceivedIndexComponent;
use App\Livewire\Shop\Admin\InventoryComponent;
use App\Livewire\Shop\Admin\OrderIndexComponent;
use App\Livewire\Shop\Admin\OrderShowComponent;
use App\Livewire\Shop\Admin\ProductFormComponent;
use App\Livewire\Shop\Admin\ProductIndexComponent;
use App\Livewire\Shop\Admin\ShopDashboardComponent;
use App\Livewire\Shop\Admin\ShopSettingsComponent;
use App\Livewire\Shop\Admin\SupplierIndexComponent;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Shop workspace — staff side (Batch 40)
|--------------------------------------------------------------------------
| Included from routes/web.php inside the main staff ['auth', 'not-family']
| group. Everything under /shop-admin shows the Shop sidebar
| (config/menu.php 'shop') instead of the care one — see
| layouts/sidebar.blade.php. Each component also re-checks manage_shop on
| every Livewire request (ShopAdminComponent::boot()).
*/

Route::prefix('shop-admin')->name('shop.admin.')->middleware('can:manage_shop')->group(function () {
    Route::get('/', ShopDashboardComponent::class)->name('dashboard');
    Route::get('/orders', OrderIndexComponent::class)->name('orders');
    Route::get('/orders/{orderId}', OrderShowComponent::class)->name('orders.show');
    Route::get('/orders/{orderId}/packing-slip', [ShopAdminController::class, 'packingSlip'])->name('orders.packing-slip');
    Route::get('/products', ProductIndexComponent::class)->name('products');
    Route::get('/products/new', ProductFormComponent::class)->name('products.create');
    Route::get('/products/{productId}/edit', ProductFormComponent::class)->name('products.edit');
    Route::get('/categories', CategoryIndexComponent::class)->name('categories');
    Route::get('/inventory', InventoryComponent::class)->name('inventory');
    Route::get('/receiving', GoodsReceivedIndexComponent::class)->name('receiving');
    Route::get('/receiving/new', GoodsReceivedFormComponent::class)->name('receiving.create');
    Route::get('/receiving/{grnId}', GoodsReceivedFormComponent::class)->name('receiving.show');
    Route::get('/receiving/{grnId}/print', [ShopAdminController::class, 'grnPrint'])->name('receiving.print');
    Route::get('/suppliers', SupplierIndexComponent::class)->name('suppliers');
    Route::get('/customers', CustomerIndexComponent::class)->name('customers');
    Route::get('/discounts', DiscountIndexComponent::class)->name('discounts');
    Route::get('/delivery', DeliveryMethodIndexComponent::class)->name('delivery');
    Route::get('/settings', ShopSettingsComponent::class)->name('settings');
    Route::get('/view-storefront', [ShopAdminController::class, 'storefront'])->name('storefront');
});
