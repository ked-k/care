<?php

namespace App\Console\Commands;

use App\Models\Agency;
use App\Models\Shop\ShopStore;
use App\Services\Shop\VariantGrouper;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Batch 41: merge products that are sizes/options of one thing into a single
 * product with variants. See App\Services\Shop\VariantGrouper for the rules.
 *
 *   php artisan shop:group-variants --dry-run          # see what would merge
 *   php artisan shop:group-variants --dry-run --show=all
 *   php artisan shop:group-variants                    # do it
 */
class ShopGroupVariants extends Command
{
    protected $signature = 'shop:group-variants
        {--agency= : Agency id (defaults to the only agency, or asks)}
        {--source= : Only products from this source, e.g. botble-import}
        {--only= : Only groups whose name contains this text}
        {--show=15 : How many groups to list (a number, or "all")}
        {--dry-run : Show what would happen without saving}';

    protected $description = 'Merge products that are sizes or options of the same item (e.g. gloves S/M/L/XL) into one product with variants';

    public function handle(): int
    {
        $agency = $this->option('agency') ? Agency::find($this->option('agency')) : $this->pickAgency();
        if (! $agency) {
            $this->error('Agency not found.');

            return self::FAILURE;
        }
        $store = ShopStore::forAgency($agency);

        $grouper = new VariantGrouper($store);
        $plan = $grouper->plan($this->option('source') ?: null);
        if ($only = $this->option('only')) {
            $plan = $plan->filter(fn ($g) => Str::contains(Str::lower($g['base']), Str::lower($only)))->values();
        }

        $ready = $plan->whereNull('reason');
        $this->info(sprintf('%d groups found: %d can be merged (%d products → %d), %d left as they are.',
            $plan->count(), $ready->count(), $ready->sum(fn ($g) => $g['products']->count()), $ready->count(), $plan->count() - $ready->count()));

        $show = $this->option('show') === 'all' ? PHP_INT_MAX : (int) $this->option('show');
        foreach ($plan->sortByDesc(fn ($g) => $g['products']->count())->take($show) as $group) {
            $this->newLine();
            $this->line(sprintf('<options=bold>%s</> — %d items%s', $group['base'], $group['products']->count(), $group['reason'] ? " <comment>(skipped: {$group['reason']})</comment>" : ''));
            foreach ($group['options'] as $option) {
                $this->line(sprintf('   %s: %s', $option['name'], Str::limit(implode(', ', $option['values']), 110)));
            }
        }
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->line('<comment>Dry run — nothing saved.</comment>');

            return self::SUCCESS;
        }
        if ($ready->isEmpty()) {
            return self::SUCCESS;
        }
        if ($this->input->isInteractive() && ! $this->confirm("Merge {$ready->count()} groups now?", true)) {
            return self::SUCCESS;
        }

        $stats = $grouper->apply($plan);
        $this->info("Done: {$stats['groups']} products now have sizes/options ({$stats['variants']} options in total).");

        return self::SUCCESS;
    }

    private function pickAgency(): ?Agency
    {
        $agencies = Agency::orderBy('name')->get();
        if ($agencies->count() <= 1) {
            return $agencies->first();
        }
        $name = $this->choice('Which agency’s shop?', $agencies->pluck('name')->all());

        return $agencies->firstWhere('name', $name);
    }
}
