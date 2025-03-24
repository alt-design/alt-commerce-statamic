<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Coupon;

use AltDesign\AltCommerce\Contracts\Coupon;
use Carbon\Carbon;
use Statamic\Entries\Entry;

class StatamicCouponFactory
{
    public function fromEntry(Entry $entry, string $currency): Coupon
    {

        $startDate = $entry->get('start_date') ? Carbon::parse($entry->get('start_date'))->startOfDay()->toDateTimeImmutable() : null;
        $endDate = $entry->get('end_date') ? Carbon::parse($entry->get('end_date'))->endOfDay()->toDateTimeImmutable() : null;

        $discountAmount = $this->findDiscountAmount($entry, $currency);

        return new StatamicProductCoupon(
            id: $entry->id(),
            name: $entry->get('title'),
            code: $entry->get('code'),
            currency: $currency,
            startDate: $startDate,
            endDate: $endDate,
            discountAmount: $discountAmount,
            isPercentage: true,
            eligibleProducts: $entry->get('included_products'),
            redemptionLimit: $entry->get('redemption_limit'),
            customerRedemptionLimit: $entry->get('customer_redemption_limit'),
        );

    }

    protected function findDiscountAmount(Entry $entry, string $currency): int
    {
        if ($entry->get('type') === 'percentage') {
            return $entry->get('percentage') ?? 0;
        }

        foreach ($entry->get('pricing') as $price) {
            if ($price['currency'] === $currency) {
                return $price['amount'] * 100;
            }
        }

        throw new \Exception('Unable to find discount amount');
    }
}
