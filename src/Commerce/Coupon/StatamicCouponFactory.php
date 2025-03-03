<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Coupon;

use AltDesign\AltCommerce\Commerce\Coupon\FixedDiscountCoupon;
use AltDesign\AltCommerce\Commerce\Coupon\PercentageDiscountCoupon;
use AltDesign\AltCommerce\Contracts\Coupon;
use AltDesign\AltCommerce\RuleEngine\RuleGroup;
use AltDesign\AltCommerce\RuleEngine\Rules\BasketSubTotalConstraintRule;
use AltDesign\AltCommerce\RuleEngine\Rules\DateConstraintRule;
use AltDesign\AltCommerce\RuleEngine\Rules\BasketHasProductRule;
use AltDesign\AltCommerce\RuleEngine\Rules\BasketDoesNotHaveProductRule;
use AltDesign\AltCommerceStatamic\Commerce\Rules\CouponRedemptionCustomerLimitRule;
use AltDesign\AltCommerceStatamic\Commerce\Rules\CouponRedemptionLimitRule;
use Carbon\Carbon;
use Statamic\Entries\Entry;

class StatamicCouponFactory
{
    public function fromEntry(Entry $entry, string $currency): Coupon
    {
        $type = match ($entry->get('type')) {
            'fixed' => FixedDiscountCoupon::class,
            'percentage' => PercentageDiscountCoupon::class,
        };

        $startDate = $entry->get('start_date');
        $endDate = $entry->get('end_date');

        $rules = [];

        if ($startDate || $endDate) {
            $startDate = $startDate ? Carbon::parse($startDate)->startOfDay()->toDateTimeImmutable() : null;
            $endDate = $endDate ? Carbon::parse($endDate)->endOfDay()->toDateTimeImmutable() : null;
            $rules[] = new DateConstraintRule($startDate, $endDate);
        }

        if ($minimumSpend = $entry->get('minimum_spend')) {
            $rules[] = new BasketSubTotalConstraintRule($entry->get('currency'), $minimumSpend*100);
        }

        if ($includedProducts = $entry->get('included_products')) {
            $rules[] = new BasketHasProductRule($includedProducts);
        }

        if ($excludeProducts = $entry->get('excluded_products')) {
            $rules[] = new BasketDoesNotHaveProductRule($excludeProducts);
        }

        if ($redemptionLimit = $entry->get('redemption_limit')) {
            $rules[] = new CouponRedemptionLimitRule($redemptionLimit);
        }

        if ($customerRedemptionLimit = $entry->get('customer_redemption_limit')) {
            $rules[] = new CouponRedemptionCustomerLimitRule($customerRedemptionLimit);
        }

        $discountAmount = $this->findDiscountAmount($entry, $type, $currency);

        return new $type(
            name: $entry->get('title'),
            code: $entry->get('code'),
            discountAmount: $discountAmount,
            currency: $currency,
            ruleGroup: new RuleGroup(rules: $rules),
        );
    }

    protected function findDiscountAmount(Entry $entry, string $type, string $currency): int
    {
        if ($type === PercentageDiscountCoupon::class) {
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
