<?php

namespace AltDesign\AltCommerceStatamic\OrderProcessor\Tasks;

use AltDesign\AltCommerce\Commerce\Basket\CouponDiscountItem;
use AltDesign\AltCommerce\Commerce\Order\Order;
use Statamic\Facades\Entry;


class ApplyCouponRedemption
{
    public function handle(Order $order, $next)
    {
        foreach ($order->discountItems as $discountItem) {
            if (! $discountItem instanceof CouponDiscountItem) {
                continue;
            }

            Entry::make()
                ->collection('coupon_redemptions')
                ->data([
                    'title' => $discountItem->name(),
                    'code' => $discountItem->coupon()->code(),
                    'amount' => $discountItem->amount() / 100,
                    'currency' => $discountItem->coupon()->currency(),
                    'customer_id' => $order->customer->customerId(),
                    'customer_email' => $order->customer->customerEmail(),
                    'order_id' => $order->id,
                ])
                ->save();
        }
        return $next($order);
    }
}