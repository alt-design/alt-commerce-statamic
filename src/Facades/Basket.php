<?php

namespace AltDesign\AltCommerceStatamic\Facades;

use AltDesign\AltCommerce\Commerce\Basket\BasketManager;
use Illuminate\Support\Facades\Facade;

class Basket extends Facade
{
    protected static function getFacadeAccessor(): string {
        return BasketManager::class;
    }
}