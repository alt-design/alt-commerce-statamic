<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Order;

use Carbon\Carbon;

class StatamicOrderLog
{

    public function __construct(
        public string $id,
        public string $content,
        public Carbon $createdAt,
    )
    {

    }

}