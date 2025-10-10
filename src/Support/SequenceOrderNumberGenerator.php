<?php

namespace AltDesign\AltCommerceStatamic\Support;

use Illuminate\Support\Facades\Storage;

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
        $payload = json_encode(['current_order_number' => $number], JSON_PRETTY_PRINT);
        Storage::put('alt-commerce/cache.json', $payload);
    }

    protected function nextOrderNumber(): int
    {
        $start =  intval($this->settings->orderNumberStartSequence());
        $current = $this->currentOrderNumber();

        if ($start > $current) {
            $current = $start;
        }

        return $current + 1;
    }

    protected function currentOrderNumber(): int
    {
        try {
            if (! Storage::exists('alt-commerce/cache.json')) {
                return 0;
            }
            $json = Storage::get('alt-commerce/cache.json');
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            return isset($data['current_order_number']) ? (int) $data['current_order_number'] : 0;
        } catch (\Throwable) {
            return 0;
        }
    }


    protected function addPadding(int $number): string
    {
        return str_pad($number, strlen($this->settings->orderNumberStartSequence()), '0', STR_PAD_LEFT);
    }

}