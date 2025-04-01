<?php

namespace AltDesign\AltCommerceStatamic\Http\Controllers;

use AltDesign\AltCommerceStatamic\Facades\Basket;
use AltDesign\AltCommerceStatamic\ManualOrder\ManualOrderGenerator;

class BasketLookupController
{

    public function __construct(protected ManualOrderGenerator $manualOrderGenerator)
    {
    }

    public function __invoke()
    {


        $context = Basket::context('manual-order')->current();

        Basket::driver('request', ['key' => ''])->namespace('custom')->applyAction();
        Basket::context('customer')->applyAction();

        $basket = Basket::namespace('basket-lookup')->current();



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