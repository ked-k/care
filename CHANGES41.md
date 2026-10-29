# Batch 41 — Products with sizes and options (variants)

One product can now come in several sizes, colours or pack sizes. Each option has its own code, barcode, stock and, if you want, its own price. Customers order from a grid on the product page, like the MediSupplies price list: one row per option and a quantity box on every row.

## Run these, in order

```
php artisan migrate
php artisan shop:group-variants --dry-run
php artisan shop:group-variants
```

- The migration adds one table (`shop_product_variants`) and a few columns. Existing products, baskets and orders are untouched.
- `shop:group-variants` merges the products you already imported into products with options. Run it with `--dry-run` first.
  - It lists the groups it found and the options it worked out (e.g. *Size: Small, Medium, Large, X-Large · Pack: 100 gloves, 10 x 100 gloves*). Nothing is saved.
  - Add `--show=all` to see every group, or `--only="nitrile"` to look at just some.
- If you haven't run the Batch 40 import yet, just run `shop:import-botble` as before. It now does the merging at the end (`--no-group` turns that off). Re-running the import is still safe: options already merged are left as they are.
- Nothing needs building. No `composer require`, no `npm run build`.

## What the merge does to the imported catalogue

- On the test copy, **4,137 products became 2,078**: 2,804 separate items were merged into 745 products with options.
  - For example, *Economy Blue Powder Free Nitrile Gloves* is now one product with Size × Pack = 8 options.
  - *Terumo Sterile Needles* has Length × Colour/Gauge × Pack = 18 options, with filter buttons above the grid.
- **How items are grouped:** by the supplier's product page link — one page on the old supplier site is one item, one product per size — and by the name before the first " - " only when there's no link. (This idea came from your own family-page version; it's more accurate than names alone, which wrongly lumped together different "Bristol Maid" items.)
- **59 groups are left as separate products.** These are ones where two items would have ended up with identical options (some drug cabinets and storage trays). You can merge them by hand later if you want.
- **How options are named.** The old descriptions carry lines like "Colour: Blue. Size: Small. Pack quantity: 100 gloves.", and anything that changes between items becomes an option.
  - Values that always go together are combined into one, e.g. "A3 (297 x 420mm)".
  - Sizes are put in size order (S, M, L, XL…), and pack sizes in quantity order.
- **What each option keeps:** its code, barcode, price, bulk prices, stock, cost and supplier link.
- **Pricing.**
  - The price shared by most of the options becomes the product's default price, and those options share one set of bulk prices.
  - The others keep their own price and bulk prices. For gloves, the boxes use the shared price and the 10-box cases have their own.
- **Nothing is lost.**
  - Old order lines, stock history and basket items are moved to the merged product and the right option.
  - The old separate products are archived (soft-deleted), not wiped.
  - Their old web addresses now show "not found", so update any links you shared to individual sizes.

## Storefront

- **Product page with options:**
  - The buy panel shows "From £3.90", the options it comes in, and a "Choose sizes and quantities" button.
  - Below it is the order grid. Columns: code, each option (Size, Pack…), price, bulk prices, stock and a quantity box with − / +.
  - The live total updates as quantities are typed, and one **Add to basket** button adds every row with a quantity.
  - Each row shows how many are already in the basket.
- **Mix sizes for bulk prices.** Options on the shared price count together towards a bulk price. 12 Small + 10 Large boxes = 22 boxes, which reaches the 20+ price of £3.84 for both lines.
  - The grid says "Add 18 more (any size) to pay £3.78 each".
  - The basket says "Bulk price (sizes counted together)".
- **Bulk-price columns.** When every option has the same bulk steps, each step gets its own column (1+, 10+, 50+). When they differ (boxes break at 20+, cases at 2+), each row lists its own steps, and the one that applies is ticked.
- **Filter buttons** above the grid when a product has more than 6 options, e.g. "Size: All | A3 | A2".
- **On phones** each grid row becomes a small card. No sideways scrolling.
- **Product cards** show "8 options" / "4 sizes" and "From £x". The round button goes to the grid instead of adding a random size.
- **Search** also looks at option values and option codes, so "nitrile gloves medium" or "PMC5234" still find the gloves.
- **Basket, checkout, order page, emails and packing slip** show the option: "Economy Blue Powder Free Nitrile Gloves — Large · 100 gloves", with the option's own code.

## Your own product-page changes, kept

Your machine had its own "family page" version of sizes (each size still a separate product, grouped on the page). You chose this Batch 41 approach instead, so your ShopProduct/ProductComponent changes are replaced. These parts of your design are carried over into the new product page:

- **Best seller** badge on the photo (featured products).
- **"As low as £x"** headline price.
- **Description / Specifications tabs** in the buy panel. For products with options, the Specifications tab lists the options.
- **One price table per set of price bands**, like MediSupplies: boxes 1 – 19 / 20 – 39 / 40+, cases 1 / 2 – 3 / 4+.
  - The first price is in coral.
  - The band a row's quantity falls into lights up in the brand gradient.
- **Delivery and guarantee strip** under the grid, and **"Alternative products"** with the brand mark.
- Your stylesheet is the base (`?v=45` now). The new grid styles use your colours (lilac headings, brand gradient).

## Shop workspace

- **Products → Add product / Edit is now a full page** (the old side drawer had no room for a grid). It's laid out like your other system's item screen:
  - **Details:** name, code, category, barcode, brand, summary, description.
  - **Price and VAT:** price, was price, VAT rate, **Sold by the** (box / pair / case…, shown as "per box"), pack size, your cost and bulk prices.
  - **Sizes and options:**
    - Tick *This product comes in different sizes or options*, then add up to 3 options (Size, Colour, Pack…).
    - Add values: type and press Enter, paste "S, M, L", or use the quick-add buttons (XS–XXL, Small–X-Large, A4/A3/A2, colours, Box/Case).
    - Double-click a value to rename it (its stock stays), use ‹ to reorder, and × to remove it.
    - The grid underneath has one row per option: code, barcode, price (blank = the default price), opening stock (for new options) and on sale.
    - **More** on a row opens its was price, cost, warning level, photo, supplier link and that option's own bulk prices.
    - **Fill blank codes** makes codes like `GLV-NIT-L-BOX`, and **Price for all** sets one price on every row.
    - Adding a value adds rows only for the new value, so a deliberately incomplete set (e.g. no Small in a case) stays that way.
  - **Stock:** tracking, warning level, back orders, and min/max order (per option).
  - **Right-hand column:** visibility, photos, supplier and delete.
- **Products list:** shows "8 options", "from £x" and total stock. The low/out-of-stock filters look at each option.
- **Inventory:**
  - Products with options list each option underneath, with its own **Receive** / **Adjust** buttons and supplier link.
  - Stock history names the option.
  - The totals (units, value at cost, running low, out of stock) count options one by one.
- **Dashboard → Running low** lists individual options, e.g. "… Gloves — Large · 100 gloves".
- **Converting a product to options:** if it already had stock as a single item, the page warns you. You enter opening stock for each option, and the old total is written off in the stock history with a note.

## Fixes to Batch 40 found while testing

- **Typed quantities and checkout errors now update straight away.**
  - Livewire 4 changed `wire:model.blur` so it no longer sends anything to the server on its own. This meant the product quantity box didn't reprice when you typed a number and tabbed away, and checkout errors didn't clear until you pressed Place order.
  - Both now use `wire:model.live.blur`, and both were tested.
- **Photo labels in the old product drawer** used a Tailwind class (`left-1`) that isn't in your compiled CSS. The drawer is gone; the new page uses classes that exist.
- I also found a bug in my class checker: it could mistake `bg-accent-50` for `bg-accent-500`, so it missed some missing classes. It's fixed, and every Batch 41 admin view passes the stricter check.

## Your updated storefront look

Your new `layouts/shop.blade.php`, `home.blade.php`, `product-card.blade.php`, `HomeComponent.php` and `shop.css` (the violet → blue brand gradient) were pulled from your machine first, and this batch is built on top of them. Changes to your versions:

- The stylesheet link is bumped to `?v=42`.
- The hero cards on home show the "from" price.
- Cards get the options hint.
- The new variant styles are added at the end of `shop.css`, using your brand variables (`--brand-grad`, `--lilac`, `--sky`…).

## Files

**New**
- `database/migrations/2026_11_21_100000_add_shop_product_variants.php`
- `app/Models/Shop/ShopProductVariant.php`
- `app/Services/Shop/VariantGrouper.php`
- `app/Console/Commands/ShopGroupVariants.php`
- `app/Livewire/Shop/Admin/ProductFormComponent.php`
- `resources/views/livewire/shop/admin/product-form.blade.php`

**Changed**
- **Models:** `ShopProduct`, `ShopPriceTier`, `ShopCartItem`, `ShopOrderItem`, `ShopOrder` (stock goes back to the right option on cancel), `ShopStockMovement`.
- **Services:** `CartService` (lines are product + option; mixed-size bulk pricing), `CheckoutService` (locks and takes stock per option).
- **Storefront components:** `ProductComponent` (order grid), `CatalogueComponent`, `HomeComponent`, `CartComponent`, `CartDrawerComponent`, `Concerns/AddsToCart`.
- **Admin components:** `ProductIndexComponent` (the list only; editing moved to the new page), `InventoryComponent`, `ShopDashboardComponent`.
- **Command:** `ShopImportBotble` (merges at the end).
- **Views:**
  - Storefront: `product`, `cart`, `cart-drawer`, `checkout`, `home`, `shop/partials/product-card`, `layouts/shop`.
  - Admin: `products`, `inventory`.
  - `include/header` (Create → Product opens the new page).
- **Routes and styles:** `routes/shop-admin.php` (`products/new`, `products/{id}/edit`), `public/css/shop.css`.

## How it was tested

The batch was tested on the same copy of the app as Batch 40 (your exact `composer.lock`, SQLite), with your real catalogue import:

- **Migration:** runs on top of the Batch 40 data and rolls back cleanly.
- **Merge:** `shop:group-variants` merged 2,804 → 745 in about 10 seconds.
- **Re-importing:** a fresh import with merging, then a second run, gave no duplicates.
- **Browser test (Playwright): 36 checks, all passing, no JavaScript errors** (rerun after your page touches were merged in).
  - **Customer side:**
    - Search by size, the card hint, and the grid's 8 rows and headings.
    - Mixed-size bulk pricing: 23 items = £123.48. Lowering the quantities returns the boxes to £3.90.
    - Basket lines with sizes, and a bank-transfer order showing the sizes.
    - The needles filter (18 → fewer rows when you pick a length), and no sideways scrolling on a phone.
  - **Staff side:**
    - Inventory per option: stock 50 → 40 after the sale, then receiving 25 → 65.
    - The edit page: adding XX-Large adds 2 rows, fill codes, save.
    - Creating a new product with 4 colours, which then shows on the storefront.
    - The order page shows sizes, and the dashboard and filters load.
- **Batch 40 behaviour:** a single product still gets the bulk-price ladder; typing 60 reprices it to £9.35.

## Not in this batch

- **Pack sizes that share stock.** A case of 10 boxes is a separate option with its own stock, as it was on the old site. It isn't linked to the box's stock (selling a case doesn't take 10 boxes off). This is worth doing if you split cases into boxes yourself. Tell me if you do.
- **Option names on imported products.** Option names are worked out automatically, and a few are generic ("Type"). Rename them on the product page; it's quick.
