<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Order;

use AltDesign\AltCommerce\Commerce\Order\Order;
use Illuminate\Support\Str;

class StatamicOrder extends Order
{

    public const RESERVED_FIELDS = [
        'id',
        'title',
        'order_number',
        'order_status',
        'currency',
        'order_date',
        'customer_id',
        'customer_name',
        'customer_email',
        'billing_company',
        'billing_full_name',
        'billing_country_code',
        'billing_postcode',
        'billing_region',
        'billing_locality',
        'billing_street',
        'billing_phone_number',
        'items',
        'sub_total',
        'tax_total',
        'discount_total',
        'delivery_total',
        'outstanding',
        'fee_total',
        'total',
        'slug',
        'created_at',
        'basket_id',
        'customer_email',
        'line_items',
        'tax_items',
        'discount_items',
        'coupon_code',
        'items',
        'transactions',
        'notes',
        'logs'
    ];

    /**
     * @var StatamicOrderNote[]
     */
    public array $notes = [];

    /**
     * @var StatamicOrderLog[]
     */
    public array $logs = [];


    public function addLog(string $content): StatamicOrderLog
    {
        $log = new StatamicOrderLog(
            id: Str::uuid()->toString(),
            content: $content,
            createdAt: now(),
        );

        array_unshift($this->logs, $log);

        return $log;
    }

    public function addNote(string $content): StatamicOrderNote
    {
        $note = new StatamicOrderNote(
            id: Str::uuid()->toString(),
            content: $content,
            userId: auth()->id(),
            userName: auth()->user()->name,
            createdAt: now()
        );

        array_unshift($this->notes, $note);

        return $note;
    }

    public function removeNote(string $id): void
    {
        $this->notes = array_filter($this->notes, fn($note) => $note->id !== $id);
    }
}