<?php

namespace AltDesign\AltCommerceStatamic\Tags;


use AltDesign\AltCommerce\Contracts\Customer;
use Statamic\Facades\Entry;
use Statamic\Tags\Tags;

class Order extends Tags
{
    public function all(): array
    {
       $data = $this->query()->get()->toArray();
       return $data;
    }

    protected function query()
    {
        return Entry::query()
            ->where('collection', 'orders')
            ->when(auth()->user(), fn($query, Customer $value) => $query->where('customer_id', $value->customerId()))
            ->when($this->params->get('status'), fn($query, $value) => $query->whereIn('order_status', explode('|', $value)))
            ->when($this->params->get('order_number'), fn($query, $value) => $query->where('order_number', $value))
            ->when($this->params->get('limit'), fn($query, $value) => $query->limit($value))
            ->orderBy('created_at', 'desc');
    }
}