<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Basket;

use AltDesign\AltCommerce\Commerce\Basket\BasketContext;
use Illuminate\Support\Facades\Pipeline;

class ContextMiddlewareRunner
{
    public function __construct(protected BasketContext $context, protected array $config)
    {

    }

    public function run(): void
    {
        Pipeline::send($this->context)
            ->through($this->config['middlewares'])
            ->thenReturn();
    }
}