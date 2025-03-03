<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Product;

use AltDesign\AltCommerce\Commerce\Billing\BillingPlan;
use AltDesign\AltCommerce\Contracts\Product;
use AltDesign\AltCommerce\Contracts\ProductRepository;
use AltDesign\AltCommerceStatamic\Concerns\HasGatewayEntities;
use Statamic\Facades\Entry;

class StatamicProductRepository implements ProductRepository
{
    use HasGatewayEntities;

    public function find(string $productId): ?Product
    {
        return $this->query()->find($productId);
    }

    public function query(): ProductQueryBuilder
    {
        return app(ProductQueryBuilder::class);
    }

    public function saveBillingPlan(string $productId, BillingPlan $billingPlan): void
    {
        /**
         * @var \Statamic\Entries\Entry $entry
         */
        $entry = Entry::query()
            ->where('collection', 'products')
            ->where('id', $productId)
            ->first();

        if (empty($entry)) {
            throw new \Exception('Unable to find product '.$productId);
        }

        //foreach ($billingPlan->prices as $price) {
        $this->storeGatewayEntities(
            entry: $entry,
            type: 'billing_plan',
            id: $billingPlan->id,
            gatewayEntities: $billingPlan->gatewayEntities,
        );
       // }

        $entry->saveQuietly();

    }

}