<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Basket\Request\Middleware;

use AltDesign\AltCommerce\Commerce\Basket\BasketContext;
use League\ISO3166\ISO3166;

class SetCountryCode
{
    public function handle(BasketContext $context, \Closure $next)
    {
        if ($countryCode = request('billing_country_code')) {
            $countryCode = (new ISO3166)->alpha3($countryCode)['alpha2'];
            $context->updateBasketCountry($countryCode);
        }
        return $next($context);
    }
}