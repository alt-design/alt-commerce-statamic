<?php

namespace AltDesign\AltCommerceStatamic\Facades;

use AltDesign\AltCommerce\Contracts\ProductRepository;
use Illuminate\Support\Facades\Facade;

class Product extends Facade
{
    protected static function getFacadeAccessor(): string {
        return ProductRepository::class;
    }
}
