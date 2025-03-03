<?php

namespace AltDesign\AltCommerceStatamic\Support;

use AltDesign\AltCommerce\Support\Location;

class VisitorLocator implements \AltDesign\AltCommerce\Contracts\VisitorLocator
{

    public function retrieve(): Location|null
    {
        if ($position = \Stevebauman\Location\Facades\Location::get()) {
            return new Location(countryCode: $position->countryCode, currency: $position->currencyCode);
        }

        return null;
    }
}