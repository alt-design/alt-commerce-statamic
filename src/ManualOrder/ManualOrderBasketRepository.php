<?php

namespace AltDesign\AltCommerceStatamic\ManualOrder;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Contracts\BasketRepository;

class ManualOrderBasketRepository implements BasketRepository
{

    protected Basket $basket;

    public function setBasket(Basket $basket): void
    {
        $this->basket = $basket;
    }

    public function save(Basket $basket): void
    {
        // does nothing
    }

    public function delete(): void
    {
        // does nothing
    }

    public function get(): Basket
    {
        return $this->basket;
    }
}