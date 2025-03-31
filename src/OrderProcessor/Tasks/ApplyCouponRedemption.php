<?php

namespace AltDesign\AltCommerceStatamic\OrderProcessor\Tasks;


use AltDesign\AltCommerce\Commerce\Order\Order;
use AltDesign\AltCommerce\Enum\DiscountType;
use AltDesign\AltCommerceStatamic\Commerce\Order\StatamicOrderRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Statamic\Facades\Entry;


class ApplyCouponRedemption implements ShouldQueue
{
    use Queueable;

    public function __construct(protected string $orderId)
    {

    }

    public function handle(StatamicOrderRepository $orderRepository): void
    {
        $order = $orderRepository->find($this->orderId);

        foreach ($order->discountItems as $discountItem) {
            if ($discountItem->type !== DiscountType::PRODUCT_COUPON) {
                continue;
            }

            Entry::make()
                ->collection('coupon_redemptions')
                ->data([
                    'title' => $discountItem->name,
                    'code' => $discountItem->couponCode,
                    'amount' => $discountItem->amount,
                    'currency' => $order->currency,
                    'customer_id' => $order->customer->customerId(),
                    'customer_email' => $order->customer->customerEmail(),
                    'order_id' => $order->id,
                ])
                ->save();
        }
    }
}