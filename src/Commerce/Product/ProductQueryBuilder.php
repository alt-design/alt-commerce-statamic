<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Product;

use Statamic\Entries\EntryCollection;
use Statamic\Stache\Query\EntryQueryBuilder;
use Statamic\Stache\Stores\Store;

class ProductQueryBuilder extends EntryQueryBuilder
{
    public function __construct(Store $store, protected ProductFactory $factory)
    {
        parent::__construct($store);
        $this->where('collection', 'products');
    }

    protected function collect($items = [])
    {
        return EntryCollection::make($items->map(fn($item) => $this->factory->make($item)));
    }

}