<?php

namespace AltDesign\AltCommerceStatamic\Contracts;

interface CurrencyConvertor
{
    public function convert(string $from, string $to, float $amount): float;
}