<?php

namespace AltDesign\AltCommerceStatamic\Console;

use AltDesign\AltCommerce\Contracts\StockRepository;
use Illuminate\Console\Command;
use Statamic\Facades\Entry;

class AdjustStock extends Command
{
    protected $signature = 'alt-commerce:stock
        {product : Product entry id or slug}
        {quantity : Signed delta (e.g. 50 or -3), or the target level with --set}
        {--set : Treat quantity as the absolute target level}
        {--reason= : Movement reason recorded on the ledger}';

    protected $description = 'Adjust or set counted stock for a product.';

    public function handle(StockRepository $stock): int
    {
        $entry = Entry::find($this->argument('product'))
            ?? Entry::query()->where('collection', 'products')->where('slug', $this->argument('product'))->first();

        if (! $entry) {
            $this->error('Product not found: '.$this->argument('product'));

            return self::FAILURE;
        }

        $quantity = (int) $this->argument('quantity');

        if ($this->option('set')) {
            $quantity -= $stock->available($entry->id()) ?? 0;
        }

        $stock->adjust($entry->id(), $quantity, $this->option('reason') ?: 'manual');

        $this->info(sprintf('%s is now at %d', $entry->value('title'), $stock->available($entry->id())));

        return self::SUCCESS;
    }
}
