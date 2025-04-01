<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Basket\Request\Middleware;

use AltDesign\AltCommerce\Commerce\Basket\BasketContext;

class AddLineItems
{
    public function handle(BasketContext $context, \Closure $next)
    {
        $validated = request()->validate([
            'line_items' => 'required|array',
            'line_items.*.product' => 'required|array',
            'line_items.*.quantity' => 'required|integer|min:1',
            'line_items.*.price' => 'required|numeric',
        ]);

        foreach ($validated['line_items'] as $lineItem) {
            $context->addToBasket(
                productId: $lineItem['product'][0],
                quantity: $lineItem['quantity'],
                price: $lineItem['price'] * 100,
            );
        }

        return $next($context);
    }
}