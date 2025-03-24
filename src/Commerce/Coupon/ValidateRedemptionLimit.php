<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Coupon;

use AltDesign\AltCommerce\Enum\CouponNotValidReason;
use AltDesign\AltCommerce\Exceptions\CouponNotValidException;
use Statamic\Facades\Entry;

class ValidateRedemptionLimit
{
    public function handle(StatamicProductCoupon $coupon): void
    {

        if ($coupon->redemptionLimit > 0) {
            $redemptionCount = Entry::query()
                ->where('collection', 'coupon_redemptions')
                ->where('currency', $coupon->currency())
                ->where('code', $coupon->code())
                ->count();

            if ($redemptionCount >= $coupon->redemptionLimit) {
                throw new CouponNotValidException(
                    reason: CouponNotValidReason::NOT_ELIGIBLE
                );
            }
        }
    }
}