<?php

namespace AltDesign\AltCommerceStatamic\Listeners;

use AltDesign\AltCommerce\Actions\SaveProductToGatewayAction;
use AltDesign\AltCommerceStatamic\Commerce\Product\ProductFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Statamic\Events\EntrySaved;

class SaveProduct implements ShouldQueue
{

    public function __construct(
        protected ProductFactory $productFactory,
        protected SaveProductToGatewayAction $action
    )
    {

    }


    public function handle(EntrySaved $event)
    {
        $collection = $event->entry->collection()->handle();
        if ($collection !== 'products') {
            return;
        }

        $this->action->handle(
            $this->productFactory->make($event->entry)
        );
    }
}