<?php

namespace AltDesign\AltCommerceStatamic\OrderProcessor\Tasks;

use Illuminate\Support\Facades\Log;

trait CanDebug
{
    protected function debug(string $message, array $context = [])
    {
        $name = class_basename(static::class);
        Log::info("Debug pipe $name: $message", [
            'order_id' => $this->orderId,
            ...$context,
        ]);
    }
}