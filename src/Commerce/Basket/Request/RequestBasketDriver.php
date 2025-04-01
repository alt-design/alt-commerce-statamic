<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Basket\Request;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Basket\BasketFactory;
use AltDesign\AltCommerce\Contracts\BasketDriver;

class RequestBasketDriver implements BasketDriver
{

    public function __construct(protected BasketFactory $basketFactory)
    {

    }

    public function save(Basket $basket): void
    {
        // do nothing
    }

    public function delete(): void
    {
        // do nothing
    }

    public function get(): Basket
    {
        return $this->basketFactory->create();
    }
}