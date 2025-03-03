<?php

namespace AltDesign\AltCommerceStatamic\Http\Controllers;

use AltDesign\AltCommerceStatamic\ManualOrder\ManualOrderGenerator;

class BasketLookupController
{

    public function __construct(protected ManualOrderGenerator $manualOrderGenerator)
    {
    }

    public function __invoke()
    {
        $basket = $this->manualOrderGenerator->createBasketFromRequest(request());
        return [
            'discountTotal' => $basket->discountTotal,
            'subTotal' => $basket->subTotal,
            'taxTotal' => $basket->taxTotal,
            'taxItems' => $basket->taxItems,
            'total' => $basket->total,
        ];
    }
}