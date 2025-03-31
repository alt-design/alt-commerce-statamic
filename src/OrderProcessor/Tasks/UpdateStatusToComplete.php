<?php

namespace AltDesign\AltCommerceStatamic\OrderProcessor\Tasks;

use AltDesign\AltCommerce\Enum\OrderStatus;
use AltDesign\AltCommerceStatamic\Commerce\Order\StatamicOrderRepository;
use Illuminate\Contracts\Queue\ShouldQueue;;

class UpdateStatusToComplete implements ShouldQueue
{
    public function __construct(protected string $orderId)
    {

    }

    public function handle(StatamicOrderRepository $orderRepository): void
    {

        $order = $orderRepository->find($this->orderId);

        $order->status = OrderStatus::COMPLETE;

        $orderRepository->save($order);
    }
}