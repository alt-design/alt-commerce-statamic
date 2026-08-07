<?php

namespace AltDesign\AltCommerceStatamic\Tags;

use AltDesign\AltCommerce\Contracts\ProductRepository;
use AltDesign\AltCommerce\Enum\StockPolicy;
use AltDesign\AltCommerce\Services\StockService;
use Statamic\Tags\Tags;

class Stock extends Tags
{
    /**
     * {{ stock }} ... {{ /stock }} exposes availability for the current product:
     * `purchasable` (can add to basket now), `show_lead_time` (backorder at/below
     * zero → pre-order UI), `available` (buyable qty, null = unlimited), `level`
     * (raw on-hand) and `tracked`.
     *
     * @return array{tracked: bool, purchasable: bool, show_lead_time: bool, available: int|null, level: int|null}
     */
    public function index(): array
    {
        $productId = $this->context->get('id');
        $product = $productId ? app(ProductRepository::class)->find((string) $productId) : null;

        if (! $product) {
            return [
                'tracked' => false,
                'purchasable' => true,
                'show_lead_time' => false,
                'available' => null,
                'level' => null,
            ];
        }

        $stock = app(StockService::class);

        return [
            'tracked' => $product->stockPolicy() !== StockPolicy::UNTRACKED,
            'purchasable' => $stock->isPurchasable($product),
            'show_lead_time' => $stock->isOnBackorder($product),
            'available' => $stock->purchasableQuantity($product),
            'level' => $stock->level($product),
        ];
    }
}
