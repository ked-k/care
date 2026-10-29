# Batch 42 — Goods received notes, batches & expiry, FEFO / FIFO

Stock now comes in on a **goods received note (GRN)**: one delivery from a supplier, with as many products as came in. Each line records its quantity, unit cost, lot/batch number and expiry date, and becomes its own **batch** on the shelf. Stock then goes out soonest-expiry first (**FEFO**) or oldest first (**FIFO**), and every batch can be traced to the orders it went out on.

> **Batch 41 (sizes & options) is included.** It hadn't been copied onto your computer yet, so this delivery writes both batches straight into `C:\wamp64\www\ked\care`. Read CHANGES41.md as well.

## Run these, in order

```
php artisan migrate
php artisan shop:group-variants --dry-run      # Batch 41 — see what would merge
php artisan shop:group-variants                # Batch 41 — merge sizes into one product
```

- The Batch 42 migration adds suppliers, GRNs, batches (lots) and lot allocations. It also creates:
  - one **opening balance** batch for the stock you already have, so the books balance from day one;
  - a supplier entry for each supplier name already on your products (e.g. *www.medisupplies.co.uk*).
- Scheduler: `shop:expire-lots` runs every night at 00:10 and takes expired batches off sale. It uses your existing Task Scheduler entry (`php artisan schedule:run` every minute).
- No packages and no `npm run build`.

## Receiving a delivery (Shop → Stock → Goods received)

1. Click **Receive a delivery**. You can also get there from:
   - **Create → Stock delivery** in the top bar;
   - the **Receive** button next to any item in Inventory, which opens a GRN with that item already on it;
   - **Suppliers → Receive delivery**.
2. Fill in the **delivery details**:
   - Supplier (pick one, or **+ New supplier**);
   - the delivery note or invoice number;
   - your PO number;
   - the date received;
   - notes (e.g. "1 carton damaged").
3. **Add the items:**
   - **Scan barcodes.** The box takes a barcode scanner's Enter. An exact product or size code or barcode goes straight onto the list, and scanning the same item again adds one more.
   - **Or type a name.** "economy blue large" lists only the Large sizes; click one to add it.
   - For each line, enter the **quantity**, **unit cost**, **lot / batch** and **expiry**. Line totals and the delivery total update as you type.
   - The copy icon adds a second line for the same item when one delivery holds two batches with different expiry dates.
4. **Save draft** keeps it without touching stock, so you can finish it later.
5. **Receive stock** posts it. Each line becomes a batch. The GRN is then locked and shows who received it and when.
   - A product that wasn't tracking stock **starts tracking automatically**. This is the easy way to switch on the 2,000 imported products: receive what you actually have.
   - The product's **cost price becomes the average cost** of what is on the shelf, so the stock value stays right.
   - Products set to track expiry can't be received without an expiry date, and an expiry date that has already passed is refused.
6. **Print** gives a GRN sheet with a "checked by" signature line.
7. **Reverse** undoes a delivery entered by mistake. You give a reason, and it's allowed only while none of the delivery has been sold or adjusted. Otherwise, correct it with a stock adjustment.

## Batches & expiry (Inventory → Batches & expiry)

- **Every batch on the shelf**, showing: lot number, expiry (with a "45 days" or "Expired" badge), date received, the GRN it came on, how many are left of how many received, and its value at cost.
- **Filters:** batches in stock, expiring soon, expired, or everything. You can set the warning period to 30, 60, 90 or 180 days.
- A yellow **"N batches expire within 60 days"** banner sits at the top of Inventory and takes you to them.
- **Details** on a batch shows its supplier and cost, plus **where it went**: every order (with the customer) and every adjustment that took stock from it. This is what you need for a supplier recall.
- **Write off** part or all of a batch (damaged, recalled…). The reason is required.
- **Expired batches come off sale automatically.** It happens every night, and again just before checkout takes stock, so expired stock is never sold. Each write-off appears in Stock history as "Expired" with the lot number.

## FEFO and FIFO

- A product page now has two new settings in **Stock**:
  - **Track batches and expiry dates**: deliveries must then have an expiry date.
  - **Which stock goes out first**: *Soonest expiry first (FEFO)* or *Oldest delivery first (FIFO)*.
- **When stock goes out** (a sale, an adjustment or a write-off), it is taken from the batches in that order. One order can be split across two batches when the first runs out.
- **If an order is cancelled** (or an online payment is abandoned), its stock goes **back into the same batches** it came from.
- **Packing slips** have a **"Pick from batch"** column, e.g. "3 × LOT-U1 (exp. 17/05/2027)", so whoever packs takes the right box.
- **The admin order page** shows the batch under each line.
- **Inventory → Adjust**: when adding stock, you can give a lot number and expiry. When removing stock, you can choose which batch it comes from, or leave it to FEFO/FIFO. Deliveries now go through a GRN, not the Adjust drawer.

## Switching tracking on in bulk (Products list)

Tick products, then use **Stock tracking ▾**:
- **Track stock — sell only what's in stock**: you're warned that items on sale with 0 in stock will show "Sold out" until a delivery is received.
- **Track stock — allow back orders**
- **Track batches & expiry (FEFO)**
- **Stop tracking — always available**

## Suppliers (Shop → Stock → Suppliers)

- **What's recorded:** name, contact, phone, email, website, your account number with them, address and notes.
- **Each row shows** the number of deliveries and the last delivery date, with **Receive delivery** and **History** links.
- **Deleting** a supplier that has deliveries hides it instead.

## Files

**New**
- `database/migrations/2026_11_22_100000_add_shop_receiving_and_lots.php`
- **Models:** `app/Models/Shop/ShopSupplier.php`, `ShopGrn.php`, `ShopGrnLine.php`, `ShopStockLot.php`, `ShopLotAllocation.php`
- **Services:**
  - `app/Services/Shop/StockLedger.php`: the one place stock changes. Handles lots, FEFO/FIFO, put-back on cancel, expiry write-off and average cost.
  - `app/Services/Shop/GoodsReceiving.php`: receive and reverse a GRN.
- **Admin components:** `app/Livewire/Shop/Admin/GoodsReceivedIndexComponent.php`, `GoodsReceivedFormComponent.php`, `SupplierIndexComponent.php`
- **Command:** `app/Console/Commands/ShopExpireLots.php`
- **Views:**
  - `resources/views/livewire/shop/admin/receiving.blade.php`, `receiving-form.blade.php`, `suppliers.blade.php`, `partials/inventory-batches.blade.php`
  - `resources/views/shop/admin/grn-print.blade.php`

**Changed**
- **Models:** `ShopProduct` and `ShopProductVariant` (`adjustStock()` now goes through StockLedger), `ShopStockMovement` (new "Expired" type; links to lots and GRN), `ShopOrderItem` (`lotsUsed()`), `ShopOrder` (cancel returns stock to the same batches).
- **Services:** `CheckoutService` (writes off expired stock before taking stock; records which batches each line used).
- **Admin components:** `InventoryComponent` (batches tab, lot fields, Receive → GRN), `ProductFormComponent` (expiry and rotation settings), `ProductIndexComponent` (bulk tracking), `OrderShowComponent`.
- **Controller:** `ShopAdminController` (GRN print; packing slip loads batches).
- **Views:** `inventory`, `product-form`, `products`, `order-show`, `shop/admin/packing-slip`, `include/header` (Create → Stock delivery opens a GRN).
- **Config and routes:** `config/menu.php` (new **Stock** section: Inventory, Goods received, Suppliers), `routes/shop-admin.php`, `app/Console/Kernel.php`.

## How it was tested

- **Ledger tests: 13 checks, all passing.**
  - A GRN adds 15 and creates 2 batches; average cost comes out at £2.33.
  - An expired batch is written off before a sale.
  - FEFO takes the soonest-expiring batch first, then the next.
  - A cancellation goes back into the same batches.
  - FIFO takes the oldest delivery.
  - Batches always add up to stock.
  - A used GRN refuses to reverse; an untouched GRN reverses.
  - A missing expiry date is refused.
  - A size's sale comes out of its own batch.
- **Browser test: 23 checks, all passing, no JavaScript errors.**
  - Scanning a code adds the line, and scanning again adds +1.
  - Searching "large" lists only Large sizes.
  - The delivery was received: 50 units, £173.00, and the GRN prints.
  - An untracked product started tracking with 30.
  - The batches tab and the expiry banner appear.
  - A customer order of 3 shows its lot on the order page, the packing slip and the batch trace.
  - Write-off: 30 − 3 − 2 = 25.
  - Stock history names the lot.
  - Bulk tracking, the product settings and the suppliers page work.
  - Inventory → Receive opens a GRN.
  - Expiry is enforced.
- **Batch 41 walk-through rerun: 36 of 36 pass** (its Receive step now uses a GRN). Every tracked item's batches equal its stock afterwards.
- **Migration** rolls back and re-runs cleanly.

## Not in this batch

- **Purchase orders** (raise a PO, then receive against it with "ordered vs received"). The GRN has a PO number field for now.
- **Pack conversion.** Receiving a case doesn't put 10 boxes into stock; the case is its own size.
