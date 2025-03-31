<?php

namespace AltDesign\AltCommerceStatamic\OrderProcessor\Tasks;

use AltDesign\AltCommerceStatamic\Commerce\Order\StatamicOrderRepository;
use AltDesign\AltCommerceStatamic\OrderProcessor\OrderProcessor;
use Illuminate\Contracts\Queue\ShouldQueue;

class DispatchNextStage implements ShouldQueue
{
    public function __construct(
        protected string $orderId,
        protected string $profile,
        protected string $stage
    )
    {

    }

    public function handle(OrderProcessor $processor, StatamicOrderRepository $orderRepository)
    {

        $processor->process(
            order: $orderRepository->find($this->orderId),
            profile: $this->profile,
            stage: $this->stage,
        );
    }

}