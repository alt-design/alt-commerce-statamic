<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Basket\Request\Middleware;

use AltDesign\AltCommerce\Commerce\Basket\BasketContext;

class SetCountryCode
{
    public function handle(BasketContext $context, \Closure $next)
    {

        $validated = request()->validate([
            'billing_country_code' => 'required|string',
        ]);

        $context->updateBasketCountry($validated['billing_country_code']);
        return $next($context);
    }
}