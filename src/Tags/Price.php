<?php

namespace AltDesign\AltCommerceStatamic\Tags;

use AltDesign\AltCommerce\Contracts\BasketRepository;
use AltDesign\AltCommerce\Contracts\VisitorLocator;
use AltDesign\AltCommerce\Exceptions\PriceNotAvailableException;
use AltDesign\AltCommerceStatamic\Commerce\Product\PriceCollectionFactory;
use Statamic\Tags\Tags;

class Price extends Tags
{
    public function __construct(
        protected BasketRepository       $basketRepository,
        protected PriceCollectionFactory $priceCollectionFactory,
        protected VisitorLocator         $visitorLocator,
    )
    {
    }

    public function index()
    {
        try {
            $currency = $this->basketRepository->get()->currency;

            //$collection = $this->priceCollectionFactory->create($this->context->get('pricing'));

            //$amount = $collection->currency($currency);

            return [
                'currency' => $currency,
                'amount' => 60,
                'formatted' => $this->format(
                    currency: $currency,
                    amount: 60,
                )
            ];

        } catch(PriceNotAvailableException) {
            return [];
        }
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