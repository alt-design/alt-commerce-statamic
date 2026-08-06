<?php

namespace AltDesign\AltCommerceStatamic\Widgets;

use AltDesign\AltCommerce\Enum\StockPolicy;
use AltDesign\AltCommerceStatamic\Models\StockLevel;
use AltDesign\AltCommerceStatamic\Support\Settings;
use Statamic\Facades\Entry;
use Statamic\Widgets\Widget;

class LowStock extends Widget
{
    public function html()
    {
        $threshold = app(Settings::class)->lowStockThreshold();

        $products = StockLevel::query()
            ->where('on_hand', '<=', $threshold)
            ->orderBy('on_hand')
            ->get()
            ->map(function (StockLevel $level) {
                $entry = Entry::find($level->product_id);

                // Only tracked products are "low"; backorder/untracked never run out.
                if (! $entry || (string) $entry->value('stock_policy') !== StockPolicy::TRACKED->value) {
                    return null;
                }

                return [
                    'title' => $entry->value('title'),
                    'edit_url' => $entry->editUrl(),
                    'on_hand' => $level->on_hand,
                ];
            })
            ->filter()
            ->values();

        return view('alt-commerce::widgets.low-stock', [
            'threshold' => $threshold,
            'products' => $products,
        ]);
    }
}
