<?php

namespace AltDesign\AltCommerceStatamic\OrderProcessor\Pipelines;

use AltDesign\AltCommerce\Commerce\Order\Order;
use AltDesign\AltCommerce\Enum\OrderStatus;
use AltDesign\AltCommerceStatamic\Commerce\Order\StatamicOrderRepository;
use Illuminate\Pipeline\Pipeline;

class CompleteOrderPipeline extends BasePipeline
{

    public const TAG = 'alt-commerce.pipeline.complete-order';

    public $queue = 'complete-order';

    public function handle(StatamicOrderRepository $orderRepository)
    {
        $order = $orderRepository->findByOrderNumber($this->orderNumber);

        $jobs = app()->tagged(self::TAG);

        if (!empty($jobs)) {
            app(Pipeline::class)
                ->send($order)
                ->through(...$jobs)
                ->then(function (Order $order) use ($orderRepository) {
                    $order->status = OrderStatus::COMPLETE;
                    $orderRepository->save($order);
                });
        }
    }
}
