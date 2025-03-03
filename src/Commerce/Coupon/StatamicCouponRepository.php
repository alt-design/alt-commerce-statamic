<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Coupon;

use AltDesign\AltCommerce\Contracts\Coupon;
use AltDesign\AltCommerce\Contracts\CouponRepository;
use Statamic\Facades\Entry;

class StatamicCouponRepository implements CouponRepository
{
    public function __construct(protected StatamicCouponFactory $couponFactory)
    {

    }

    public function find(string $currency, string $code): Coupon|null
    {
        $data = Entry::query()
            ->where('collection', 'coupon_codes')
            ->where('code', $code)
            ->first();

        if (empty($data)) {
            return null;
        }

        return $this->couponFactory->fromEntry($data, $currency);
    }
}