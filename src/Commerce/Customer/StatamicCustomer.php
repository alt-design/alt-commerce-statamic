<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Customer;

use AltDesign\AltCommerce\Contracts\Customer;
use Statamic\Contracts\Auth\User;

class StatamicCustomer implements Customer
{
    public function __construct(protected User $user)
    {
    }

    public function customerId(): string
    {
        return $this->user->id();
    }

    public function customerName(): string
    {
        return $this->user->name() ?? $this->user->email();
    }

    public function customerEmail(): string
    {
        return $this->user->email();
    }
}
