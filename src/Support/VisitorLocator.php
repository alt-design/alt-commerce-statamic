<?php

namespace AltDesign\AltCommerceStatamic\Support;

use AltDesign\AltCommerce\Support\Location;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VisitorLocator implements \AltDesign\AltCommerce\Contracts\VisitorLocator
{

    public function retrieve(): Location|null
    {
        $currency = request('visitor-currency');
        $country = request('visitor-country');

        if (empty($currency) || empty($country)) {
            return null;
        }
        return new Location(
            countryCode: $country,
            currency: $currency,
        );
    }
}
