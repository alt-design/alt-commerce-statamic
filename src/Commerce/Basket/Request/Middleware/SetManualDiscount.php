<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Basket\Request\Middleware;

use AltDesign\AltCommerce\Commerce\Basket\BasketContext;


class SetManualDiscount
{
    public function handle(BasketContext $context, \Closure $next)
    {
        request()->validate([
            'manual_discount_amount' => 'nullable|numeric'
        ]);

        if ($val = request('manual_discount_amount')) {
            $context->applyManualDiscount(amount: $val * 100);
        }

        return $next($context);
    }
}
