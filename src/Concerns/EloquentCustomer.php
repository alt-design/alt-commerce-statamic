<?php

namespace AltDesign\AltCommerceStatamic\Concerns;

trait EloquentCustomer
{
    public function customerId(): string
    {
        return $this->id;
    }

    public function customerName(): string
    {
        return $this->name;
    }

    public function customerEmail(): string
    {
        return $this->email;
    }

}