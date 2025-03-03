<?php

namespace AltDesign\AltCommerceStatamic\CP\Actions;

use AltDesign\AltCommerce\Enum\OrderStatus;
use AltDesign\AltCommerceStatamic\Commerce\Order\StatamicOrder;


class UpdateOrderStatusToRefunded extends OrderAction
{

    public $fields = [
        'refund_note' => [
            'type' => 'text',
            'validate' => 'sometimes|string',
        ]
    ];

    protected $dangerous = true;

    protected bool $allowMultiple = false;

    public function warningText()
    {
        return 'This cannot be reversed';
    }

    public static function title()
    {
        return 'Set to Refunded';
    }

    protected function runOnOrder(StatamicOrder $order, $values): void
    {
        $order->status = OrderStatus::REFUNDED;
        $log = $order->addLog(empty($values['refund_note']) ? 'Order refunded': 'Order refunded: '.$values['refund_note']);

        $this->callbackData['actions'][] = [
            'type' => 'status-updated',
            'status' => OrderStatus::REFUNDED->value,
            'order_id' => $order->id
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
            'message' => 'Order has been set to Refunded'
        ];
    }

    protected function visibleToOrder(StatamicOrder $order): bool
    {
        return $order->status === OrderStatus::COMPLETE;
    }

}