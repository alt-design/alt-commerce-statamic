<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Basket\Session;

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

    protected ?Basket $basket = null;

    public function save(Basket $basket): void
    {
        // Serialized manually so the basket survives Laravel's `json` session
        // serialization (the default since Laravel 13), which would otherwise
        // round-trip the object into an array.
        $this->basket = $basket;
        Session::put($this->sessionKey, serialize($basket));
    }

    public function delete(): void
    {
        $this->basket = null;
        Session::remove($this->sessionKey);
    }

    public function get(): Basket
    {
        // Actions mutate the basket returned here and rely on RecalculateBasketAction
        // saving that same instance, so the unserialized basket must be memoized for
        // the remainder of the request.
        if ($this->basket) {
            return $this->basket;
        }

        try {
            $data = Session::get($this->sessionKey);

            if (is_string($data)) {
                $basket = unserialize($data);
                if ($basket instanceof Basket) {
                    return $this->basket = $basket;
                }
            }

            return $this->create();
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