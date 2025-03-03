<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Order;

use AltDesign\AltCommerce\Commerce\Order\Order;
use Illuminate\Support\Str;

class StatamicOrder extends Order
{
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