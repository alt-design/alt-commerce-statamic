<?php

namespace AltDesign\AltCommerceStatamic\Facades;

use AltDesign\AltCommerce\Commerce\Payment\PaymentManager;
use Illuminate\Support\Facades\Facade;

class Payment extends Facade
{
    protected static function getFacadeAccessor(): string {
        return PaymentManager::class;
    }
}