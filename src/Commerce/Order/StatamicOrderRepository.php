<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Order;

use AltDesign\AltCommerce\Commerce\Order\Order;
use AltDesign\AltCommerce\Contracts\OrderRepository;
use AltDesign\AltCommerce\Enum\DiscountType;
use AltDesign\AltCommerceStatamic\Contracts\OrderTransformer;
use AltDesign\AltCommerceStatamic\Support\SequenceOrderNumberGenerator;
use Statamic\Facades\Entry;
use Statamic\Support\Arr;

class StatamicOrderRepository implements OrderRepository
{
    public function __construct(
        protected StatamicOrderFactory         $orderFactory,
        protected OrderTransformer             $orderTransformer,
        protected SequenceOrderNumberGenerator $sequenceOrderNumberGenerator,
    ) {}

    public function save(Order $order): void
    {
        $this->saveWithEntryData($order, []);
    }

    public function saveWithEntryData(Order $order, array $data): void
    {
        $entry = Entry::query()
            ->where('collection', 'orders')
            ->where('order_number', $order->orderNumber)
            ->first();

        if (! $entry) {
            $entry = Entry::make()->collection('orders');
            $entry->id($order->id);
        }

        $entryData = [
            ...$this->orderTransformer->toEntryData($order),
            ...Arr::except($data, StatamicOrder::RESERVED_FIELDS)
        ];

        $entry->published(true)->data($entryData);
        $entry->save();
    }

    public function findByBasketId(string $basketId): ?StatamicOrder
    {
        $entry = $this->query()
            ->where('basket_id', $basketId)
            ->first();

        return $entry ? $this->orderFactory->fromEntry($entry) : null;
    }

    public function find(string $id): ?StatamicOrder
    {
        $entry = $this->query()
            ->where('id', $id)
            ->first();

        return $entry ? $this->orderFactory->fromEntry($entry) : null;
    }

    public function findByOrderNumber(string $orderNumber): ?StatamicOrder
    {
        $entry = $this->query()
            ->where('order_number', $orderNumber)
            ->first();

        return $entry ? $this->orderFactory->fromEntry($entry) : null;
    }

    protected function query()
    {
        return Entry::query()
            ->where('collection', 'orders');
    }

    public function reserveOrderNumber(): string
    {
        return $this->sequenceOrderNumberGenerator->reserve();
    }
}
