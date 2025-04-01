<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Basket\Session;

use AltDesign\AltCommerce\Commerce\Basket\BasketFactory;
use AltDesign\AltCommerce\Contracts\BasketDriver;
use AltDesign\AltCommerce\Contracts\BasketDriverFactory;
use AltDesign\AltCommerce\Contracts\Resolver;

class SessionBasketDriverFactory implements BasketDriverFactory
{

    public function create(Resolver $resolver, array $config): BasketDriver
    {
        return new SessionBasketDriver(
            factory: $resolver->resolve(BasketFactory::class),
            sessionKey: $config['key'] ?? 'alt-commerce-basket',
        );
    }
}