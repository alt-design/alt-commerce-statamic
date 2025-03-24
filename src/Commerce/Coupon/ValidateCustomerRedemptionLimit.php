<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Coupon;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Contracts\Coupon;
use AltDesign\AltCommerce\Contracts\Customer;
use AltDesign\AltCommerce\Enum\CouponNotValidReason;
use AltDesign\AltCommerce\Exceptions\CouponNotValidException;
use Statamic\Facades\Entry;

class ValidateCustomerRedemptionLimit
{
    public function handle(StatamicProductCoupon $coupon, Basket $basket, Customer|null $customer = null): void
    {
        if ($coupon->customerRedemptionLimit > 0 && $customer) {
            $redemptionCount = Entry::query()
                ->where('collection', 'coupon_redemptions')
                ->where('status', 'published')
                ->where('currency', $coupon->currency())
                ->where('code', $coupon->code())
                ->where('customer_id', $customer->customerId())
                ->count();

            if ($redemptionCount >= $coupon->customerRedemptionLimit) {
                throw new CouponNotValidException(
                    reason: CouponNotValidReason::NOT_ELIGIBLE
                );
            }
        }
    }
}