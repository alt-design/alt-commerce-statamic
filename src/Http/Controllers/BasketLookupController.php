<?php

namespace AltDesign\AltCommerceStatamic\Http\Controllers;

use AltDesign\AltCommerceStatamic\Facades\Basket;

class BasketLookupController
{
    public function __invoke()
    {
        $basket = Basket::context('cp-order')->current();
        return [
            'discountTotal' => number_format($basket->discountTotal / 100, 2),
            'subTotal' => number_format($basket->subTotal/ 100, 2),
            'taxTotal' => number_format($basket->taxTotal/ 100, 2),
            'taxItems' => $basket->taxItems,
            'lineItems' => $basket->lineItems,
            'total' => number_format($basket->total/ 100, 2),
        ];
    }
}