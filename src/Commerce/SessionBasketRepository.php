<?php

namespace AltDesign\AltCommerceStatamic\Commerce;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Basket\BasketFactory;
use AltDesign\AltCommerce\Contracts\BasketRepository;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;


class SessionBasketRepository implements BasketRepository
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