# Batch 43b: the shop on its own web address

`shop.affinityhealthcareltd.co.uk` now serves the shop directly, with clean addresses:

| On the shop address | Was |
|---|---|
| `/` | `care…/store/affinity` |
| `/product/economy-blue-powder-free-nitrile-gloves` | `care…/store/affinity/product/…` |
| `/products?q=gloves`, `/category/…`, `/basket`, `/checkout`, `/order/…`, `/account` | same, under `/store/affinity` |

## Set it up

1. Copy the files in, then run:
   ```
   php artisan migrate
   php artisan optimize:clear
   ```
2. **Shop → Settings & payments → Shop web address:** enter `shop.affinityhealthcareltd.co.uk` and save.
3. **Hosting (cPanel):**
   - Set the `shop.` subdomain's document root to the **same `public` folder** as `care.`.
   - Make sure it has an SSL certificate (AutoSSL).
   - Keep `APP_URL` as the care address. Leave `SESSION_DOMAIN` empty.
4. **Optional:** in Stripe → Developers → Webhooks, nothing needs changing. The webhook URL still works on either address.

## What it does

- **On the shop address:**
  - Only the shop is reachable. `/login`, `/dashboard` and every staff page are "not found" there.
  - Customer sign-in (`/account/sign-in`) and basket, checkout and payments all work, Livewire included.
- **Every link to a shop page** uses the shop address automatically, wherever it's made:
  - order confirmation emails and password-reset emails
  - Stripe and PayPal return pages
  - the staff "View shop" / "View in shop" buttons
  - product cards
- **Old links** like `care…/store/affinity/…` get a permanent (301) redirect to the same page on the shop address, so shared links and Google results follow.
- **Typing the long form** on the shop address (`shop…/store/affinity/basket`) tidies to `shop…/basket`.
- **Search engines:** `shop…/sitemap.xml` lists the home page, categories and every product, and each page has a canonical link.
- **While the shop isn't live yet,** links stay on the care site (`/store/affinity`), where your staff are signed in and can preview it.
- **Shops with no web address** behave exactly as before.
- **Validation:**
  - The address can't be the care app's own address.
  - `https://`, capitals and trailing slashes are tidied.
  - Two shops can't share an address.

The `Route::domain('shop…')` redirect you added to `routes/web.php` is no longer needed. It's harmless, so leave it or delete it.

## Files

**New:**
- `database/migrations/2026_11_24_100000_add_domain_to_shop_stores.php`
- `app/Http/Middleware/ServeShopDomain.php`
- `app/Routing/ShopAwareUrlGenerator.php`
- `app/Support/Shop/ShopDomains.php`

**Changed:**
- `app/Http/Kernel.php`: global middleware
- `app/Providers/AppServiceProvider.php`: the URL generator, in `register()`
- `app/Models/Shop/ShopStore.php`: `domain`, cache refresh
- `app/Exceptions/Handler.php`: an expired form on the shop address goes back to the shop
- `app/Livewire/Shop/Admin/ShopSettingsComponent.php` and `resources/views/livewire/shop/admin/settings.blade.php`: the new field
- `routes/shop.php` and `StorefrontController.php`: sitemap
- `resources/views/layouts/shop.blade.php`: canonical link

## Tested

Tested on the test copy using two host names. **24 checks (18 in the browser, 6 on link building), all passing, no JavaScript errors.**

- **The shop address:**
  - home, product, search and sign-in pages load on clean addresses
  - there are no `/store/` links on the page
  - adding to the basket (a Livewire request) works
  - the care login and dashboard are 404
  - the long form tidies itself
  - the sitemap lists clean addresses
- **The care address:**
  - old links redirect (301)
  - the care login still works
  - "View shop" points at the shop address
- **Settings:** the care app's own address is refused, and addresses are tidied on save.
- **Links built behind the scenes** (order emails, named parameters, admin pages, the Stripe webhook): each uses the right address.
- **Shops that aren't live, or have no web address,** keep `/store/…` links.
- **Other workspaces:** Care, Shop and Finance pages still load.
