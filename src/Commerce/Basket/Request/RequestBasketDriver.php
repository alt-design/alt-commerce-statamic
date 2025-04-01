<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Basket\Request;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Basket\BasketFactory;
use AltDesign\AltCommerce\Contracts\BasketDriver;

class RequestBasketDriver implements BasketDriver
{

    protected Basket $basket;

    public function __construct(protected BasketFactory $basketFactory)
    {
        $this->basket = $this->basketFactory->create();
    }

    public function save(Basket $basket): void
    {
        $this->basket = $basket;
    }

    public function delete(): void
    {
        $this->basket = $this->basketFactory->create();
    }

    public function get(): Basket
    {
        return $this->basket;
    }
}