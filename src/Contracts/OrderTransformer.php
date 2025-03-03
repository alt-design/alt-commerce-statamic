<?php

namespace AltDesign\AltCommerceStatamic\Contracts;

use AltDesign\AltCommerceStatamic\Commerce\Order\StatamicOrder;

interface OrderTransformer
{
    public function toEntryData(StatamicOrder $order): array;
}