<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Basket\Request\Middleware;

use AltDesign\AltCommerce\Commerce\Basket\BasketContext;
use League\ISO3166\ISO3166;

class SetCountryCode
{
    public function handle(BasketContext $context, \Closure $next)
    {

        $validated = request()->validate([
            'billing_country_code' => 'required|string',
        ]);

        $countryCode = (new ISO3166)->alpha3($validated['billing_country_code'])['alpha2'];
        $context->updateBasketCountry($countryCode);
        return $next($context);
    }
}