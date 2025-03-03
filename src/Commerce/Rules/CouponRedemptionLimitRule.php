<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Rules;

use AltDesign\AltCommerce\Contracts\Coupon;
use AltDesign\AltCommerce\RuleEngine\Rules\BaseRule;
use Statamic\Facades\Entry;

class CouponRedemptionLimitRule extends BaseRule
{
    public function __construct(protected int $limit)
    {

    }

    protected function handle(): void
    {
        /**
         * @var Coupon $coupon
         */
        $coupon = $this->resolve('coupon');
        $redemptionCount = Entry::query()
            ->where('collection', 'coupon_redemptions')
            ->where('currency', $coupon->currency())
            ->where('code', $coupon->code())
            ->count();

        if ($redemptionCount >= $this->limit) {
            $this->fail('Redemption limit exceeded');
        }
    }
}