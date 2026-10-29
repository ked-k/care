# Batch 40 — Shop workspace (online medical-supplies shop)

Each agency can now run a public online shop from CareAffinity. **Switch workspace → Shop** manages it (orders, products, stock, customers, discounts, delivery, payments). Customers buy at **/store/{shop-address}**, e.g. `https://care.affinityhealthcareltd.co.uk/store/affinity`.

## Run these, in order

```
php artisan migrate
php artisan db:seed --class=Database\Seeders\ShopSeeder
php artisan storage:link            # only if public/storage isn't already linked (product photos)
php artisan shop:import-botble "C:\path\to\affiwspg_ecommerce.sql" --dry-run
php artisan shop:import-botble "C:\path\to\affiwspg_ecommerce.sql"
```

- `ShopSeeder` creates the `manage_shop` permission and gives it to **Admin**. Give it to Manager (or anyone else who'll run the shop) from the Roles screen. Super Admin already has everything.
- Run the import with `--dry-run` first: it prints what it would do and saves nothing.
- No `composer require` and no `npm run build` are needed. The storefront has its own stylesheet (`public/css/shop.css`), and the admin screens only use Tailwind classes already in your compiled `public/build` CSS (checked class by class).
- Scheduler: `shop:release-unpaid` now runs hourly. It uses the same Windows Task Scheduler entry from Batch 30 (`php artisan schedule:run` every minute).

## The catalogue import (`shop:import-botble`)

What it brings in from the old Botble database:

- **4,137 real products and 21 categories.** These are everything created on or after 1 Jan 2026.
- **Left out:**
  - The demo electronics, demo customers, vendors, reviews and orders.
  - The **54 Pharmaceuticals** products. Selling medicines to the public online needs MHRA registration and the distance-selling logo first. Add `--include-pharmacy` once that's done.
- **Cleaned up:**
  - The "Reference/Source prices… checked 28 October 2026" text and every supplier link are removed from the public descriptions. Zero links remain in public text.
  - Each product keeps its supplier link in a **staff-only** field (Inventory → external-link icon, for reordering).
  - The quantity prices written in that text became real **bulk-price rows**: 4,872 of them.
- **Two quirks in the source data, handled:**
  - About 40 items showed "Was £10.74; 1+: £9.42", and Botble had stored the *was* price as the selling price. They now sell at the 1+ price, with the old price shown crossed out.
  - 17 items had a step where buying more cost more per item. Those steps are dropped.
- **Defaults:**
  - Products arrive as **drafts**. Publish them in bulk from Shop → Products (tick → Put on sale), or re-run with `--publish`.
  - VAT is 20% (`--vat=`).
  - Stock is **untracked**, meaning always available (`--stock=backorder` or `--stock=tracked` to change).
- **Re-running is safe.** It updates the same products instead of duplicating them.

**Prices are the supplier's own retail prices, VAT included.** Selling at those prices leaves no margin. Set your own prices (and your cost price, which is staff-only) before going live.

## Storefront (public)

- **Home:** a search-led hero, category tiles with live product counts, and popular products.
- **Catalogue:** search (every word must match, so "nitrile medium" works), category filter, in-stock and bulk-price filters, sorting and pagination.
- **Product page:** the bulk-price ladder (tap a step to set the quantity; it tells you how many more boxes reach the next price), quantity stepper, stock state and similar items.
- **Product pictures:** none of the imported products have photos, so each one shows a printed "carton label" tile built from its own data until you upload photos in the product drawer.
- **Basket:** a slide-in basket drawer opens when something is added, plus a full basket page with discount codes and a "£x more for free delivery" bar.
- **Checkout:**
  - One page in three steps: details, delivery, payment.
  - Guest checkout, with optional "save my details" account creation.
  - Saved addresses for signed-in customers, UK postcode validation, and click & collect.
  - Mandatory terms tick box linking to your terms and returns pages.
- **Order page:** a thank-you screen, then progress tracking, bank details when paying by transfer, a "pay now" retry if an online payment was abandoned, and tracking numbers. Guests reach it from the email link; account holders from Your account.
- **Customer accounts:** sign in, create an account, forgot/reset password, order history, saved addresses and details. These use a **separate login** (`shop` guard, `shop_customers` table). A member of the public can never become a staff or family user.
- **Info pages** in the footer: About, Delivery, Returns, Terms, Privacy. They appear once you write them in Settings.
- **Emails:** order received or confirmed to the customer, a new-order alert to your orders email, and dispatched / ready to collect / cancelled updates. A mail failure never breaks checkout; it's noted on the order's timeline.
- **Unknown or closed shops:** an unknown shop address is a 404. A shop that isn't open yet is visible only to your own staff, as a "Preview" with a yellow bar.

## Ways to pay

| Method | How it works | Setup |
|---|---|---|
| **Card, Apple Pay, Google Pay** (Stripe Checkout) | Customer pays on Stripe's page and comes straight back. Confirmed by the return *and* by Stripe's webhook, so a closed tab still gets marked paid. | Shop → Settings → Payments: secret key + webhook signing secret. Add the webhook URL shown there in Stripe (event `checkout.session.completed`). Wallets are switched on in Stripe's own settings. |
| **PayPal** | Customer approves on PayPal; payment is captured when they return. | Client ID + secret, Live or Sandbox. |
| **Bank transfer** | Order waits as "Awaiting payment" with your bank details and the order number as the reference. You click **Record payment** when it arrives. | Account name, sort code, account number. |
| **Pay on delivery / collection** | Same as bank transfer, paid in person. | Just switch it on. |

- Keys are stored **encrypted** in the database.
- No new packages are needed: Stripe and PayPal are called through Laravel's HTTP client.
- Card and PayPal orders still unpaid after 24 hours are cancelled automatically and their stock goes back on sale (`shop:release-unpaid`).

## Shop workspace (staff)

- **Dashboard:** paid sales (with a sparkline and comparison against the previous period), orders, average order, a "waiting for payment" / "ready to dispatch" pair, low stock, best sellers, recent orders, and a **Get your shop ready** checklist.
- **Orders:**
  - Status tabs with counts, search and CSV export.
  - Each order page has: Record payment, Mark dispatched (courier + tracking, optional customer email), Ready to collect, Mark delivered/collected, Cancel (stock is returned automatically), Mark refunded.
  - Also: notes (optionally visible to the customer), resend confirmation, a printable **packing slip**, and a full timeline.
- **Products:** search, filters and bulk publish. Publishing never puts a £0 item on sale.
- **Product drawer:** details, price / was price / VAT rate / staff-only cost, bulk-price breaks, stock settings, photos (upload, choose main, remove), supplier info, and status/featured.
- **Categories:** order, icon, image, show/hide and featured.
- **Inventory:** stock levels, receive deliveries, corrections (a reason is required), the full stock history ledger, and stock value at cost.
- **Customers:** account vs guest, orders, amount spent, saved addresses, staff notes, and block/unblock.
- **Discount codes:** % off, fixed amount off or free delivery, with minimum spend, dates, usage limit and once per customer.
- **Delivery options:** price, "free over £x", delivery times and click & collect. A one-click button adds Standard, Next day and Click & collect.
- **Settings:** shop name and web address, contact and orders email, company and VAT numbers, order number prefix, VAT-inclusive or -exclusive pricing, logo, brand and button colours (with a live preview), payments, policy pages, and **Open shop / Close shop**. You can't open the shop until it has a product, a way to pay and a delivery option.

## Changes to existing files

- `config/menu.php`:
  - New **Shop** entry in the workspace switcher, and a new `shop` sidebar.
  - The **Care Management** switcher entry now goes to the Dashboard instead of the old `care-management/plan` page, so switching back from Shop lands somewhere useful.
- `resources/views/include/topmenu.blade.php`: switcher entries can now have a `'can'` permission.
- `resources/views/layouts/sidebar.blade.php`, `include/modalmenu.blade.php`: show the Shop menu on `/shop-admin` pages.
- `resources/views/include/header.blade.php`: "Create" menu gets Product / Discount code / Stock delivery while in the Shop.
- `routes/web.php`: includes `routes/shop.php` (public, top level) and `routes/shop-admin.php` (inside the staff group).
- `config/auth.php`: `shop` guard, `shop_customers` provider and password broker.
- `app/Http/Kernel.php`: `shop.store` middleware alias.
- `app/Http/Middleware/VerifyCsrfToken.php`: `store-webhooks/*` exempt (Stripe; verified by signature instead).
- `app/Exceptions/Handler.php`: a stale form on the public shop goes back to that shop, not the staff login.
- `app/Models/Agency.php` (`shopStore()`) and `app/Models/User.php` (`canManageShop()`).
- `app/Console/Kernel.php`: hourly `shop:release-unpaid`.
- `database/seeders/DatabaseSeeder.php`: calls `ShopSeeder`.

## New files

- **Migrations:** 5 (`2026_11_20_100000`–`100004`), 16 tables, all prefixed `shop_`.
- **Models:** `app/Models/Shop/*` (16).
- **Services:** `app/Services/Shop/*`: `CartService`, `CheckoutService`, `ShopMailer`, `Vat`, and `Payments/*` (gateway interface, manager, and the Stripe, PayPal, bank transfer and pay-on-delivery gateways).
- **Livewire:**
  - Storefront: `app/Livewire/Shop/Storefront/*` (12 pages + basket drawer).
  - Admin: `app/Livewire/Shop/Admin/*` (10 pages).
- **Controllers and middleware:** `app/Http/Controllers/Shop/StorefrontController.php` and `ShopAdminController.php`, `app/Http/Middleware/ResolveShopStore.php`.
- **Commands:** `app/Console/Commands/ShopImportBotble.php`, `ShopReleaseUnpaidOrders.php`, and `app/Support/Shop/SqlDumpReader.php`.
- **Mail:** `app/Mail/Shop/OrderMail.php` and `CustomerPasswordResetMail.php`.
- **Views:**
  - Layout: `resources/views/layouts/shop.blade.php`.
  - `resources/views/shop/*` (partials, packing slip, shop directory).
  - `resources/views/livewire/shop/**`.
  - `resources/views/emails/shop/*`.
- **Stylesheet:** `public/css/shop.css`.
- **Routes:** `routes/shop.php`, `routes/shop-admin.php`.
- **Seeder:** `database/seeders/ShopSeeder.php`.

## How it was tested

The batch was run inside a copy of this app built from your exact `composer.lock` (Laravel 12, Livewire 4.3, the same Spatie packages), using SQLite in place of MySQL.

- **Migrations and import:** all migrations run and roll back cleanly. The real import ran against `affiwspg_ecommerce.sql`, twice, to prove re-running updates rather than duplicates.
- **Browser test (Playwright):** a real browser ran **28 checks, all passing, with no JavaScript errors**:
  - Customer side: search; the bulk-price ladder (20 boxes → £3.84); the basket drawer; the WELCOME10 discount; checkout validation; a bank-transfer order with bank details shown; and no sideways scrolling on a phone.
  - Staff side: Admin opens the Shop workspace and the sidebar switches; the order appears; Record payment → Processing; Mark dispatched with tracking, which the customer then sees; stock falls 250 → 230; every admin page loads; a **Carer is kept out**.
- **Payment logic:** Stripe webhook signatures are tested (valid accepted; tampered, wrong-secret and replayed rejected).
- **Not tested here:** real Stripe and PayPal payments, which need your live or test keys. Use Stripe test keys (`sk_test_…`) and PayPal **Sandbox** first.

## Before opening to the public

1. Set your own prices. The imported ones are the supplier's retail prices.
2. Write Terms, Returns (including the 14-day cancellation right), Delivery and Privacy in Settings → Policy pages.
3. Confirm the VAT treatment. Some care products (e.g. certain incontinence or disability aids) can be zero-rated, and you can set that per product.
4. Rewrite the About text. The old site's About page described a different company (Poole, 150 staff); don't carry it over.
5. Put Stripe/PayPal in test mode, place a test order end to end, then switch to live keys.
