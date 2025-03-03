<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Rules;

use AltDesign\AltCommerce\Contracts\Coupon;
use AltDesign\AltCommerce\Contracts\Customer;
use AltDesign\AltCommerce\RuleEngine\Rules\BaseRule;
use Statamic\Facades\Entry;

class CouponRedemptionCustomerLimitRule extends BaseRule
{
    public function __construct(protected int $limit)
    {

    }

    protected function handle(): void
    {
        /**
         * @var Coupon $coupon
         * @var Customer $customer
         */
        $coupon = $this->resolve('coupon');
        $customer = auth()->user();

        if (!$customer) {
            return;
        }

        $redemptionCount = Entry::query()
            ->where('collection', 'coupon_redemptions')
            ->where('status', 'published')
            ->where('currency', $coupon->currency())
            ->where('code', $coupon->code())
            ->where('customer_id', $customer->customerId())
            ->count();

        if ($redemptionCount >= $this->limit) {
            $this->fail('Redemption limit exceeded for customer');
        }
    }


}