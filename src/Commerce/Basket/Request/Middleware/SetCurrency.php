<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Basket\Request\Middleware;

use AltDesign\AltCommerce\Commerce\Basket\BasketContext;

class SetCurrency
{
    public function handle(BasketContext $context, \Closure $next)
    {

        $validated = request()->validate([
            'currency' => 'required|string',
        ]);

        $context->updateBasketCurrency($validated['currency']);
        return $next($context);
    }
}