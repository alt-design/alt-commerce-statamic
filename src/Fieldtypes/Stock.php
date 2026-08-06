<?php

namespace AltDesign\AltCommerceStatamic\Fieldtypes;

use AltDesign\AltCommerce\Contracts\StockRepository;
use AltDesign\AltCommerce\Enum\StockPolicy;
use AltDesign\AltCommerceStatamic\Models\StockMovement;
use Statamic\Contracts\Entries\Entry;

class Stock extends BaseFieldType
{
    protected $keywords = ['stock', 'inventory'];

    /**
     * The live count lives in the ledger, never on the entry, so this field
     * stores nothing. It only renders the card + drives adjustments.
     */
    public function process($data)
    {
        return null;
    }

    public function preProcess($data)
    {
        return null;
    }

    public function preload()
    {
        // On the "create" screen the field's parent is the collection, not an
        // entry, so there's no product to read stock for yet.
        $parent = $this->field?->parent();
        $entry = $parent instanceof Entry ? $parent : null;
        $productId = $entry?->id();
        $stock = app(StockRepository::class);

        return [
            'product_id' => $productId,
            'policy' => (string) ($entry?->value('stock_policy') ?? StockPolicy::UNTRACKED->value),
            'level' => $productId ? $stock->available($productId) : null,
            'show_url' => $productId ? cp_route('alt-commerce::stock.show', ['productId' => $productId]) : null,
            'adjust_url' => $productId ? cp_route('alt-commerce::stock.adjust', ['productId' => $productId]) : null,
            'reasons' => [
                ['value' => 'restock', 'label' => 'Restock'],
                ['value' => 'stocktake', 'label' => 'Stock-take correction'],
                ['value' => 'damage', 'label' => 'Damage / loss'],
            ],
            'movements' => $productId ? $this->recentMovements($productId) : [],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function recentMovements(string $productId): array
    {
        return StockMovement::query()
            ->where('product_id', $productId)
            ->latest()
            ->limit(5)
            ->get(['quantity', 'reason', 'note', 'created_at'])
            ->toArray();
    }
}
