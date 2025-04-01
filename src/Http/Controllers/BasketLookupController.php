<?php

namespace AltDesign\AltCommerceStatamic\Http\Controllers;

use AltDesign\AltCommerceStatamic\Facades\Basket;

class BasketLookupController
{

    public function __construct()
    {
    }

    public function __invoke()
    {
        $basket = Basket::context('manual-order')->current();
        return [
            'discountTotal' => $basket->discountTotal,
            'subTotal' => $basket->subTotal,
            'taxTotal' => $basket->taxTotal,
            'taxItems' => $basket->taxItems,
            'total' => $basket->total,
        ];
    }
}