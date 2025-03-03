<?php

namespace AltDesign\AltCommerceStatamic\Support;

class CurrencyConvertor implements \AltDesign\AltCommerceStatamic\Contracts\CurrencyConvertor
{
    public function convert(string $from, string $to, float $amount): float
    {

        // todo
        return $amount;
    }
}