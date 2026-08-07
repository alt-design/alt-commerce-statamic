<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Basket\Request\Middleware;

use AltDesign\AltCommerce\Commerce\Basket\BasketContext;
use Closure;

class RecalculateBasket
{
    public function handle(BasketContext $context, Closure $next): mixed
    {
        $context->recalculateBasket();

        return $next($context);
    }
}
