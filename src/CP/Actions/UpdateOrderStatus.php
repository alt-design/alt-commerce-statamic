<?php

namespace AltDesign\AltCommerceStatamic\CP\Actions;

use AltDesign\AltCommerce\Enum\OrderStatus;
use AltDesign\AltCommerceStatamic\Commerce\Order\StatamicOrder;

class UpdateOrderStatus extends OrderAction
{
    public $fields = [
        'order_status' => [
            'type' => 'select',
            'display' => 'Order status',
            'options' => [
                'processing' => 'Processing (payment received)',
                'complete' => 'Complete',
                'cancelled' => 'Cancelled',
            ],
            'validate' => 'required|in:processing,complete,cancelled',
        ],
        'note' => [
            'type' => 'text',
            'display' => 'Note',
            'validate' => 'sometimes|string',
        ],
    ];

    protected bool $allowMultiple = true;

    public static function title()
    {
        return 'Update order status';
    }

    protected function runOnOrder(StatamicOrder $order, $values): void
    {
        $status = OrderStatus::from($values['order_status']);

        if ($order->status === $status) {
            return;
        }

        $order->status = $status;

        $message = 'Order status set to '.$status->value;
        if (! empty($values['note'])) {
            $message .= ': '.$values['note'];
        }
        $log = $order->addLog($message);

        $this->callbackData['actions'][] = [
            'type' => 'status-updated',
            'status' => $status->value,
            'order_id' => $order->id,
        ];

        $this->callbackData['actions'][] = [
            'type' => 'log-added',
            'log' => json_encode($log),
            'order_id' => $order->id,
        ];

        $this->orderRepository->save($order);
    }

    protected function result(): array
    {
        return [
            'message' => 'Order status updated',
        ];
    }

    protected function visibleToOrder(StatamicOrder $order): bool
    {
        // Drafts haven't been placed; refunded is terminal (owned by Set to Refunded).
        return ! in_array($order->status, [OrderStatus::DRAFT, OrderStatus::REFUNDED], true);
    }
}
