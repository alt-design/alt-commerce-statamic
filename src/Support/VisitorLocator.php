<?php

namespace AltDesign\AltCommerceStatamic\Support;

use AltDesign\AltCommerce\Support\Location;
use Illuminate\Support\Facades\Log;

class VisitorLocator implements \AltDesign\AltCommerce\Contracts\VisitorLocator
{

    public function retrieve(): Location|null
    {
        try {
            if ($position = \Stevebauman\Location\Facades\Location::get()) {
                return new Location(countryCode: $position->countryCode, currency: $position->currencyCode);
            }
        } catch (\Throwable $e) {
            Log::error($e);
        }

        return null;
    }
}