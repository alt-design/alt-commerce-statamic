<?php

namespace AltDesign\AltCommerceStatamic\CP\Actions;

use AltDesign\AltCommerce\Contracts\StockRepository;
use Statamic\Actions\Action;
use Statamic\Entries\Entry;

class AdjustStock extends Action
{
    public $fields = [
        'quantity' => [
            'type' => 'integer',
            'display' => 'Adjustment',
            'instructions' => 'Positive to restock, negative to reduce.',
            'validate' => 'required|integer',
        ],
        'reason' => [
            'type' => 'select',
            'display' => 'Reason',
            'options' => [
                'restock' => 'Restock',
                'stocktake' => 'Stock-take correction',
                'damage' => 'Damage / loss',
            ],
            'validate' => 'nullable',
        ],
        'note' => [
            'type' => 'text',
            'validate' => 'nullable|string',
        ],
    ];

    protected bool $allowMultiple = false;

    public static function title()
    {
        return 'Adjust stock';
    }

    public function visibleTo($item)
    {
        return $item instanceof Entry && $item->collection()?->handle() === 'products';
    }

    public function run($items, $values)
    {
        $stock = app(StockRepository::class);

        foreach ($items as $item) {
            $stock->adjust(
                productId: $item->id(),
                quantity: (int) $values['quantity'],
                reason: $values['reason'] ?? 'manual',
                note: $values['note'] ?? null,
            );
        }

        return ['message' => 'Stock adjusted.'];
    }
}
