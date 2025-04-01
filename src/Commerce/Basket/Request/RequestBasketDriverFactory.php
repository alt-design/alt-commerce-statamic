<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Basket\Request;


use AltDesign\AltCommerce\Commerce\Basket\BasketFactory;
use AltDesign\AltCommerce\Contracts\BasketDriverFactory;
use AltDesign\AltCommerce\Contracts\Resolver;

class RequestBasketDriverFactory implements BasketDriverFactory
{
    public function create(Resolver $resolver, array $config): RequestBasketDriver
    {
        return new RequestBasketDriver(
            basketFactory: $resolver->resolve(BasketFactory::class)
        );
    }
}