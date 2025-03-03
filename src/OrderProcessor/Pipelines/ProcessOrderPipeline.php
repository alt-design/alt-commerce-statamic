<?php

namespace AltDesign\AltCommerceStatamic\OrderProcessor\Pipelines;

use AltDesign\AltCommerce\Commerce\Order\Order;
use AltDesign\AltCommerce\Enum\OrderStatus;

class ProcessOrderPipeline extends BasePipeline
{

    public const TAG = 'alt-commerce.pipeline.process-order';

    public $queue = 'process-order';

    public function handle()
    {
        $this->after(fn(Order $order) => $order->status = OrderStatus::PROCESSED)
            ->finally(fn(Order $order) => CompleteOrderPipeline::dispatch($order->orderNumber))
            ->start();
    }
}
