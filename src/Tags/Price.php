<?php

namespace AltDesign\AltCommerceStatamic\Tags;

use AltDesign\AltCommerce\Contracts\VisitorLocator;
use AltDesign\AltCommerceStatamic\Commerce\Product\PriceCollectionFactory;
use Statamic\Tags\Tags;

class Price extends Tags
{
    public function __construct(
        protected PriceCollectionFactory $priceCollectionFactory,
        protected VisitorLocator         $visitorLocator,
    )
    {
    }



    public function format(string|null $currency = null, int|null $amount = null): string
    {
        $currency = $this->params->get('currency', $currency);
        $amount = (int)$this->params->get('amount', $amount);

        // todo pull in user locale from visitor locator
        // $locale = $this->visitorLocator->locale()
        $formatter = new \NumberFormatter('en-gb', \NumberFormatter::CURRENCY);
        return $formatter->formatCurrency($amount/100, $currency);
    }
}