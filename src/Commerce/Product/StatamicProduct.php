<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Product;


use AltDesign\AltCommerce\Contracts\PricingSchema;
use AltDesign\AltCommerce\Contracts\Product;

class StatamicProduct implements Product
{
    public function __construct(
        protected string            $id,
        protected string            $name,
        protected array             $data,
        protected bool              $taxable,
        protected array $taxRules,
        protected PricingSchema $price,
    )
    {

    }

    public function name(): string
    {
        return $this->name;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function data(): array
    {
        return $this->data;
    }

    public function taxable(): bool
    {
        return $this->taxable;
    }

    public function taxRules(): array
    {
        return $this->taxRules;
    }

    public function price(): PricingSchema
    {
        return $this->price;
    }

    public function __get($key): mixed
    {
        return $this->data()[$key] ?? null;
    }

}