<?php

namespace AltDesign\AltCommerceStatamic\CP\Actions;

use AltDesign\AltCommerceStatamic\Commerce\Order\StatamicOrder;

class DeleteOrderNote extends OrderAction
{

    public $fields = [
        'note' => []
    ];

    protected bool $allowMultiple = false;

    protected function runOnOrder(StatamicOrder $order, $values): void
    {
        $order->removeNote($values['note']['id']);
        $this->callbackData['actions'][] = [
            'type' => 'note-deleted',
            'id' => $values['note']['id'],
            'orderId' => $order->id,
        ];
        $this->orderRepository->save($order);
    }

    protected function visibleToOrder(StatamicOrder $order): bool
    {
        return false;
    }

    protected function result(): array
    {
        return [
            'message' => 'Note has been deleted'
        ];
    }
}