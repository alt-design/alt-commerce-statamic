<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Product;

use AltDesign\AltCommerce\Support\Money;
use AltDesign\AltCommerce\Support\PriceCollection;
use AltDesign\AltCommerceStatamic\Support\CurrencyConvertor;
use Statamic\Fields\Value;

class PriceCollectionFactory
{
    public function __construct(
        protected CurrencyConvertor $currencyConvertor,
    )
    {}

    public function create($prices): PriceCollection
    {
        if ($prices instanceof Value) {
            $prices = $prices->value();
        }


        if (!is_array($prices) || empty($prices)) {
            return new PriceCollection(prices: []);
        }

        $ar = [];
        foreach ($prices as $price) {
            $amount = $price['amount'] * 100;
            if ($amount === 0) {
                continue;
            }

            $ar[] = new Money(
                amount: $price['amount'] * 100,
                currency: $price['currency'],
            );
        }
        return new PriceCollection(prices: $ar);

    }

}