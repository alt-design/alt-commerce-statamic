<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Stock;

use AltDesign\AltCommerce\Contracts\StockRepository;
use AltDesign\AltCommerceStatamic\Models\StockLevel;
use AltDesign\AltCommerceStatamic\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Statamic\Facades\Entry;
use Statamic\Facades\User;

class StatamicStockRepository implements StockRepository
{
    public function available(string $productId): ?int
    {
        return StockLevel::query()->where('product_id', $productId)->value('on_hand');
    }

    public function adjust(string $productId, int $quantity, ?string $reason = null, ?string $reference = null, ?string $note = null): void
    {
        DB::transaction(function () use ($productId, $quantity, $reason, $reference, $note) {
            $level = StockLevel::query()
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first() ?? new StockLevel(['product_id' => $productId, 'on_hand' => 0]);

            $level->on_hand += $quantity;
            $level->save();

            StockMovement::query()->create([
                'product_id' => $productId,
                'sku' => Entry::find($productId)?->value('sku'),
                'quantity' => $quantity,
                'reason' => $reason,
                'reference' => $reference,
                'note' => $note,
                'user_id' => User::current()?->id(),
            ]);
        });
    }

    public function hasMovementsForReference(string $reference): bool
    {
        return StockMovement::query()->where('reference', $reference)->exists();
    }
}
