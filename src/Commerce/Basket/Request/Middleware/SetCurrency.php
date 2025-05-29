<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Basket\Request\Middleware;

use AltDesign\AltCommerce\Commerce\Basket\BasketContext;

class SetCurrency
{
    public function handle(BasketContext $context, \Closure $next)
    {
        request()->validate([
            'currency' => 'required|string',
        ]);

        $context->updateBasketCurrency(request('currency'));
        return $next($context);
    }
}