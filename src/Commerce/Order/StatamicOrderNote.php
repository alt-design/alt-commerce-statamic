<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Order;

use Carbon\Carbon;

class StatamicOrderNote
{
    public function __construct(
        public string $id,
        public string $content,
        public string|int $userId,
        public string $userName,
        public Carbon $createdAt,
    ) {

    }
}