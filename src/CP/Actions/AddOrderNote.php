<?php

namespace AltDesign\AltCommerceStatamic\CP\Actions;

use AltDesign\AltCommerceStatamic\Commerce\Order\StatamicOrder;

class AddOrderNote extends OrderAction
{
    public $fields = [
        'note' => [
            'type' => 'textarea',
            'validate' => 'required|string',
        ]
    ];

    protected bool $allowMultiple = true;

    protected function visibleToOrder(StatamicOrder $order): bool
    {
        return true;
    }

    protected function runOnOrder(StatamicOrder $order, $values): void
    {
        $note = $order->addNote($values['note']);

        $this->callbackData['actions'][] = [
            'type' => 'note-added',
            'note' => json_encode($note),
            'order_id' => $order->id,
        ];

        $this->orderRepository->save($order);
    }

    protected function result(): array
    {
        return [
            'message' => 'Note has been added',
        ];
    }
}