<?php

namespace App\Console\Commands;

use App\Models\Agency;
use App\Models\Shop\ShopCategory;
use App\Models\Shop\ShopProduct;
use App\Models\Shop\ShopStore;
use App\Support\Shop\SqlDumpReader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Batch 40: bring the medical-supplies catalogue across from the old Botble
 * ecommerce site's database dump (affiwspg_ecommerce.sql).
 *
 * What it takes:  only the real catalogue — products and categories created
 *                 on or after --since (the Botble demo seed was 26 Dec 2025).
 * What it skips:  the demo electronics, demo customers/vendors/reviews/
 *                 orders, and (unless --include-pharmacy) the Pharmaceuticals
 *                 category, because selling medicines to the public online
 *                 needs MHRA registration first.
 * What it cleans: the "Reference/Source prices … checked 28 October 2026"
 *                 and "Supplier reference: https://…" paragraphs come out of
 *                 the public description; the supplier link is kept in the
 *                 staff-only supplier_url field, and the quantity prices in
 *                 that text become real bulk-price rows.
 *
 * Products arrive as drafts unless --publish. Re-running updates the same
 * products (matched on the Botble product id) instead of duplicating them.
 *
 * Batch 41: afterwards, products that are sizes/options of one item ("…
 * Gloves - Small - 100 gloves", "… - Large - …") are merged into one product
 * with variants (see VariantGrouper), unless --no-group. Items already merged
 * by an earlier run are left as they are.
 */
class ShopImportBotble extends Command
{
    protected $signature = 'shop:import-botble
        {path : Path to the Botble SQL dump}
        {--agency= : Agency id (defaults to the only agency, or asks)}
        {--since=2026-01-01 : Only import rows created on/after this date}
        {--include-pharmacy : Also import the Pharmaceuticals category}
        {--publish : Put imported products on sale straight away (otherwise drafts)}
        {--stock=untracked : untracked (always available) | backorder (track, allow back orders) | tracked (track, zero stock)}
        {--vat=20 : VAT rate for imported products}
        {--no-group : Don’t merge sizes/options of the same item into one product}
        {--dry-run : Show what would happen without saving}';

    protected $description = 'Import the real product catalogue from the old Botble ecommerce database dump into this agency’s shop';

    public function handle(): int
    {
        $path = $this->argument('path');
        if (! is_readable($path)) {
            $this->error("Can't read {$path}");

            return self::FAILURE;
        }
        $stockMode = $this->option('stock');
        if (! in_array($stockMode, ['untracked', 'backorder', 'tracked'], true)) {
            $this->error('--stock must be untracked, backorder or tracked');

            return self::FAILURE;
        }

        $agency = $this->resolveAgency();
        if (! $agency) {
            return self::FAILURE;
        }
        $store = ShopStore::forAgency($agency);
        $since = $this->option('since');

        $this->info("Reading {$path}…");
        $reader = new SqlDumpReader(file_get_contents($path));
        $botbleCategories = collect($reader->rows('ec_product_categories'))->filter(fn ($r) => ($r['created_at'] ?? '') >= $since);
        $botbleProducts = collect($reader->rows('ec_products'))->filter(fn ($r) => ($r['created_at'] ?? '') >= $since && ($r['is_variation'] ?? '0') === '0');
        $links = collect($reader->rows('ec_product_category_product'))->groupBy('product_id');

        $this->line(sprintf('Found %d categories and %d products created since %s.', $botbleCategories->count(), $botbleProducts->count(), $since));

        $pharmacyIds = $botbleCategories->filter(fn ($c) => Str::contains(Str::lower($c['name']), 'pharma'))->pluck('id')->all();
        $dry = (bool) $this->option('dry-run');
        $stats = ['categories' => 0, 'created' => 0, 'updated' => 0, 'skipped_pharmacy' => 0, 'tiers' => 0, 'no_price' => 0, 'already_variants' => 0, 'grouped' => 0, 'grouped_into' => 0];
        $mergedRefs = \App\Models\Shop\ShopProductVariant::whereNotNull('source_ref')
            ->whereHas('product', fn ($q) => $q->withTrashed()->where('store_id', $store->id)->where('source', 'botble-import'))
            ->pluck('source_ref')->flip();

        DB::beginTransaction();
        try {
            // Categories
            $catMap = [];
            foreach ($botbleCategories as $c) {
                if (in_array($c['id'], $pharmacyIds, true) && ! $this->option('include-pharmacy')) {
                    continue;
                }
                $name = html_entity_decode($c['name'], ENT_QUOTES | ENT_HTML5);
                $category = ShopCategory::firstOrNew(['store_id' => $store->id, 'slug' => Str::slug($c['slug'] ?: $name)]);
                $category->fill([
                    'name' => $name,
                    'sort_order' => (int) ($c['order'] ?? 0),
                    'is_featured' => ($c['is_featured'] ?? '0') === '1',
                    'is_active' => true,
                    'icon' => $category->icon ?: ShopCategory::guessIcon($name),
                ]);
                if (! $dry) {
                    $category->save();
                }
                $catMap[$c['id']] = $category->id;
                $stats['categories']++;
            }

            // Products
            $bar = $this->output->createProgressBar($botbleProducts->count());
            foreach ($botbleProducts as $p) {
                $bar->advance();
                $categoryIds = $links->get($p['id'], collect())->pluck('category_id')->all();
                if (! $this->option('include-pharmacy') && array_intersect($categoryIds, $pharmacyIds)) {
                    $stats['skipped_pharmacy']++;
                    continue;
                }
                // Already a size/option of a merged product (an earlier run): leave it.
                if ($mergedRefs->has((string) $p['id'])) {
                    $stats['already_variants']++;
                    continue;
                }
                $categoryId = collect($categoryIds)->map(fn ($id) => $catMap[$id] ?? null)->filter()->first();

                $parsed = self::parseContent((string) ($p['content'] ?? ''));
                // The single-unit price from the supplier's own price list wins:
                // for "Was £X; 1+: £Y" items Botble stored the *was* price.
                $price = (float) ($p['price'] ?? 0);
                if ($parsed['tiers'] && $parsed['tiers'][0]['min'] === 1) {
                    $price = $parsed['tiers'][0]['price'];
                }
                $was = $parsed['was'] && $parsed['was'] > $price ? $parsed['was'] : null;
                // Keep only steps that are cheaper than the one before — a few
                // source lists go up again at some quantities.
                $steps = [];
                $last = $price;
                foreach ($parsed['tiers'] as $tier) {
                    if ($tier['min'] > 1 && $tier['price'] < $last) {
                        $steps[] = $tier;
                        $last = $tier['price'];
                    }
                }
                if ($price <= 0) {
                    $stats['no_price']++;
                }

                $product = ShopProduct::withTrashed()->firstOrNew(['store_id' => $store->id, 'source' => 'botble-import', 'source_ref' => (string) $p['id']]);
                $name = html_entity_decode($p['name'], ENT_QUOTES | ENT_HTML5);
                $isNew = ! $product->exists;

                $product->fill([
                    'category_id' => $categoryId,
                    'name' => $name,
                    'sku' => $p['sku'] ?: null,
                    'barcode' => ($p['barcode'] ?? null) ?: null,
                    'pack_size' => $parsed['pack_size'],
                    'summary' => Str::limit(html_entity_decode(strip_tags((string) ($p['description'] ?? '')), ENT_QUOTES | ENT_HTML5), 480, ''),
                    'description' => $parsed['description'],
                    'price' => $price,
                    'compare_at_price' => $was,
                    'vat_rate' => (float) $this->option('vat'),
                    'supplier_name' => $parsed['supplier_url'] ? parse_url($parsed['supplier_url'], PHP_URL_HOST) : null,
                    'supplier_sku' => $p['sku'] ?: null,
                    'supplier_url' => $parsed['supplier_url'],
                    'weight_grams' => (int) ($p['weight'] ?? 0) ?: null,
                    'min_order_qty' => max(1, (int) ($p['minimum_order_quantity'] ?? 1)),
                ]);

                if ($isNew) {
                    $product->slug = ShopProduct::uniqueSlug($store->id, $p['slug'] ?: $name);
                    $product->status = $this->option('publish') && $price > 0 ? 'active' : 'draft';
                    $product->track_stock = $stockMode !== 'untracked';
                    $product->allow_backorder = $stockMode === 'backorder';
                    $product->stock_qty = 0;
                } elseif ($this->option('publish') && $price > 0 && $product->status === 'draft') {
                    $product->status = 'active';
                }

                if (! $dry) {
                    $product->save();
                    if ($product->trashed()) {
                        $product->restore();
                    }
                    $product->tiers()->delete();
                    foreach ($steps as $tier) {
                        $product->tiers()->create(['min_qty' => $tier['min'], 'unit_price' => $tier['price']]);
                    }
                }
                $stats['tiers'] += count($steps);
                $isNew ? $stats['created']++ : $stats['updated']++;
            }
            $bar->finish();
            $this->newLine(2);

            if (! $this->option('no-group')) {
                $grouper = new \App\Services\Shop\VariantGrouper($store);
                $plan = $grouper->plan('botble-import');
                if ($dry) {
                    // Inside the (rolled-back) transaction, so the counts include this run's products.
                    $ready = $plan->whereNull('reason');
                    $stats['grouped'] = $ready->count();
                    $stats['grouped_into'] = $ready->sum(fn ($g) => $g['products']->count());
                } else {
                    $result = $grouper->apply($plan);
                    $stats['grouped'] = $result['groups'];
                    $stats['grouped_into'] = $result['variants'];
                }
            }

            $dry ? DB::rollBack() : DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Import failed, nothing was saved: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->table(['', 'Count'], [
            ['Categories', $stats['categories']],
            ['Products created', $stats['created']],
            ['Products updated', $stats['updated']],
            ['Bulk-price rows', $stats['tiers']],
            ['Skipped (pharmacy)', $stats['skipped_pharmacy']],
            ['With no price (left as draft)', $stats['no_price']],
            ['Already sizes/options of a merged product', $stats['already_variants']],
            ['Merged into products with sizes/options', $stats['grouped_into'] ? "{$stats['grouped_into']} → {$stats['grouped']} products" : ($this->option('no-group') ? 'off (--no-group)' : 0)],
        ]);
        $this->line($dry ? '<comment>Dry run — nothing saved.</comment>' : "<info>Imported into “{$store->name}”.</info> ".($this->option('publish') ? 'Priced products are on sale.' : 'Products are drafts — publish them from Shop → Products.'));

        return self::SUCCESS;
    }

    private function resolveAgency(): ?Agency
    {
        if ($id = $this->option('agency')) {
            $agency = Agency::find($id);
            if (! $agency) {
                $this->error("No agency with id {$id}");
            }

            return $agency;
        }
        $agencies = Agency::orderBy('name')->get();
        if ($agencies->count() === 1) {
            return $agencies->first();
        }
        if ($agencies->isEmpty()) {
            $this->error('There are no agencies yet.');

            return null;
        }
        $name = $this->choice('Which agency’s shop should get the products?', $agencies->pluck('name')->all());

        return $agencies->firstWhere('name', $name);
    }

    /**
     * Pull the useful parts out of a Botble product's HTML content.
     *
     * @return array{description: ?string, pack_size: ?string, supplier_url: ?string, tiers: array<int, array{min: int, price: float}>, was: ?float}
     */
    public static function parseContent(string $html): array
    {
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5);
        $supplierUrl = preg_match('~(?:Supplier reference|Source):\s*(https?://[^\s<"]+)~i', $html, $m) ? rtrim($m[1], '.') : null;

        // Quantity prices: "1–19 boxes: £3.90 each; 20–39: £3.84 each; 40 or more: £3.78 each"
        //                  "1 - 4: £58.08; 5 - 9: £56.10; 10+: £54.12"
        $tiers = [];
        if (preg_match('~(?:Reference|Source) prices[^<]*~i', $html, $pm)) {
            // Drop "checked 28 October 2026." first — otherwise the 28 reads as a quantity.
            $priceText = preg_replace('~checked\s+\d{1,2}\s+[A-Za-z]+\s+\d{4}\.?~i', '', $pm[0]);
            preg_match_all('~(\d+)\s*(?:[–—-]\s*\d+|\+|\s*or more)?[^:;£\d]*:\s*£\s*([\d,]+(?:\.\d+)?)~u', $priceText, $tm, PREG_SET_ORDER);
            foreach ($tm as $t) {
                $tiers[] = ['min' => (int) $t[1], 'price' => (float) str_replace(',', '', $t[2])];
            }
            usort($tiers, fn ($a, $b) => $a['min'] <=> $b['min']);
        }

        // Pack size from "Pack quantity: 100 gloves" / "Pack Qty: 36 Lozenges" / "Pack: 100 gloves"
        $pack = preg_match('~Pack(?: quantity| Qty)?:\s*([^.;<]+)~i', $html, $km) ? trim($km[1]) : null;

        // Public description: drop the price/supplier paragraphs and empty ones.
        $clean = preg_replace([
            '~<p>[^<]*(?:Reference|Source) prices.*?</p>~is',
            '~<p>[^<]*Supplier reference:.*?</p>~is',
            '~<p>[^<]*Source:\s*https?://.*?</p>~is',
            // belt and braces: any remaining paragraph that links anywhere
            '~<p>(?:(?!</p>).)*https?://(?:(?!</p>).)*</p>~is',
            '~<p>\s*</p>~',
        ], '', $html);
        $clean = trim((string) $clean);

        // "Was: £10.74; 1+: £9.42" — the supplier's crossed-out price.
        $was = preg_match('~Was:\s*£\s*([\d,]+(?:\.\d+)?)~i', $pm[0] ?? '', $wm) ? (float) str_replace(',', '', $wm[1]) : null;

        return [
            'description' => $clean !== '' ? $clean : null,
            'pack_size' => $pack ? Str::limit($pack, 120, '') : null,
            'supplier_url' => $supplierUrl,
            'tiers' => $tiers,
            'was' => $was,
        ];
    }
}
