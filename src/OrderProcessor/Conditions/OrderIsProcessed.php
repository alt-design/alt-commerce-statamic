<?php

namespace AltDesign\AltCommerceStatamic\OrderProcessor\Conditions;

use AltDesign\AltCommerce\Enum\OrderStatus;
use AltDesign\AltCommerceStatamic\Commerce\Order\StatamicOrder;

class OrderIsProcessed
{
    public function validate(StatamicOrder $order): bool
    {
        return $order->status === OrderStatus::PROCESSED;
    }
}