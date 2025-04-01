<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Basket;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Basket\BasketFactory;
use AltDesign\AltCommerce\Contracts\BasketDriver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class SessionBasketDriver implements BasketDriver
{

    public function __construct(
        protected BasketFactory $factory,
        protected string $sessionKey = 'alt-commerce-basket',
    )
    {

    }

    public function save(Basket $basket): void
    {
        Session::put($this->sessionKey, $basket);
    }

    public function delete(): void
    {
        Session::remove($this->sessionKey);
    }

    public function get(): Basket
    {
        try {
            return Session::get($this->sessionKey, fn() => $this->create());
        }
        catch (\Throwable $e) {
            Log::error($e);
            return $this->create();
        }
    }

    protected function create(): Basket
    {
        $basket = $this->factory->create();
        $this->save($basket);
        return $basket;
    }

}