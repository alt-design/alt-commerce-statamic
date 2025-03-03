<?php

namespace AltDesign\AltCommerceStatamic\Support;

class SequenceOrderNumberGenerator
{
    public function __construct(protected Settings $settings)
    {

    }

    public function reserve(): string
    {
        $next = $this->nextOrderNumber();

        $this->save($next);

        $formatted = $this->addPadding($next);

        if ($prefix = $this->settings->orderNumberPrefix()) {
            $formatted = $prefix.'-'.$formatted;
        }
        return $formatted;
    }

    protected function save(int $number): void
    {
        $this->settings->set('current_order_number', $number);
    }

    protected function nextOrderNumber(): int
    {
        $start =  intval($this->settings->orderNumberStartSequence());
        $current = $this->settings->currentOrderNumber();

        if ($start > $current) {
            $current = $start;
        }

        if ($current) {
            return $current + 1;
        }
    }

    protected function addPadding(int $number): string
    {
        return str_pad($number, strlen($this->settings->orderNumberStartSequence()), '0', STR_PAD_LEFT);
    }

}