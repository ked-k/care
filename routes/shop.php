<?php

use App\Http\Controllers\Shop\StorefrontController;
use App\Livewire\Shop\Storefront\AccountComponent;
use App\Livewire\Shop\Storefront\CartComponent;
use App\Livewire\Shop\Storefront\CatalogueComponent;
use App\Livewire\Shop\Storefront\CheckoutComponent;
use App\Livewire\Shop\Storefront\ForgotPasswordComponent;
use App\Livewire\Shop\Storefront\HomeComponent;
use App\Livewire\Shop\Storefront\LoginComponent;
use App\Livewire\Shop\Storefront\OrderComponent;
use App\Livewire\Shop\Storefront\PageComponent;
use App\Livewire\Shop\Storefront\ProductComponent;
use App\Livewire\Shop\Storefront\RegisterComponent;
use App\Livewire\Shop\Storefront\ResetPasswordComponent;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public shop storefront (Batch 40)
|--------------------------------------------------------------------------
| Included from routes/web.php at the TOP level — deliberately outside the
| staff 'auth' group, since the whole point is that members of the public
| can browse and buy without a CareAffinity login. Shop customers sign in
| through the separate 'shop' guard (config/auth.php), never 'web'.
|
| Every URL is scoped to one agency's shop by its slug: /store/{storeSlug}.
| 'shop.store' (App\Http\Middleware\ResolveShopStore) 404s an unknown slug
| and hides a shop that isn't live yet from everyone except its own staff.
*/

Route::get('/store', [StorefrontController::class, 'index'])->name('shop.index');

Route::prefix('store/{storeSlug}')->name('shop.')->middleware('shop.store')->group(function () {
    Route::get('/', HomeComponent::class)->name('home');
    Route::get('/products', CatalogueComponent::class)->name('catalogue');
    Route::get('/category/{categorySlug}', CatalogueComponent::class)->name('category');
    Route::get('/product/{productSlug}', ProductComponent::class)->name('product');
    Route::get('/basket', CartComponent::class)->name('cart');
    Route::get('/checkout', CheckoutComponent::class)->name('checkout');
    Route::get('/order/{orderNumber}', OrderComponent::class)->name('order');
    Route::get('/info/{page}', PageComponent::class)->name('page')->where('page', 'about|delivery|returns|terms|privacy');

    // Coming back from Stripe / PayPal.
    Route::get('/checkout/return/{gateway}/{orderId}', [StorefrontController::class, 'paymentReturn'])->name('payment.return');
    Route::get('/checkout/cancel/{orderId}', [StorefrontController::class, 'paymentCancel'])->name('payment.cancel');

    // Customer accounts
    Route::get('/account/sign-in', LoginComponent::class)->name('login');
    Route::get('/account/create', RegisterComponent::class)->name('register');
    Route::get('/account/forgot-password', ForgotPasswordComponent::class)->name('password.request');
    Route::get('/account/reset-password/{token}', ResetPasswordComponent::class)->name('password.reset');
    Route::post('/account/sign-out', [StorefrontController::class, 'logout'])->name('logout');
    Route::get('/account', AccountComponent::class)->name('account');
});

// Stripe calls this server-to-server — no session, no CSRF token (excluded
// in App\Http\Middleware\VerifyCsrfToken), authenticated by the signature.
Route::post('/store-webhooks/stripe/{storeId}', [StorefrontController::class, 'stripeWebhook'])->name('shop.webhooks.stripe');
