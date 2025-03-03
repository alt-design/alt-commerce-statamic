<?php

namespace AltDesign\AltCommerceStatamic\OrderProcessor\Pipelines;

use AltDesign\AltCommerce\Commerce\Order\Order;
use AltDesign\AltCommerce\Enum\OrderStatus;

class CreateOrderPipeline extends BasePipeline
{
    public const TAG = 'alt-commerce.pipeline.create-order';

    public function handle()
    {
        $this->after(fn(Order $order) => $order->status = OrderStatus::PROCESSING)
            ->finally(fn(Order $order) => ProcessOrderPipeline::dispatch($order->orderNumber))
            ->start();

    }
}