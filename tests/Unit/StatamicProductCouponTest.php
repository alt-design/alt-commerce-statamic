<?php

namespace AltDesign\AltCommerceStatamic\Tests\Unit;

use AltDesign\AltCommerceStatamic\Commerce\Coupon\StatamicProductCoupon;
use PHPUnit\Framework\TestCase;

class StatamicProductCouponTest extends TestCase
{
    /**
     * @param array<string> $eligibleProducts
     * @param array<string> $excludedProducts
     */
    protected function createCoupon(array $eligibleProducts = [], array $excludedProducts = []): StatamicProductCoupon
    {
        return new StatamicProductCoupon(
            id: 'coupon-1',
            name: 'Test Coupon',
            code: 'TEST',
            currency: 'GBP',
            startDate: null,
            endDate: null,
            discountAmount: 10,
            isPercentage: true,
            eligibleProducts: $eligibleProducts,
            redemptionLimit: 0,
            customerRedemptionLimit: 0,
            excludedProducts: $excludedProducts,
        );
    }

    public function test_all_products_are_eligible_when_no_products_are_specified(): void
    {
        $coupon = $this->createCoupon();

        $this->assertTrue($coupon->isProductEligible('product-1'));
        $this->assertTrue($coupon->isProductEligible('product-2'));
    }

    public function test_only_included_products_are_eligible(): void
    {
        $coupon = $this->createCoupon(eligibleProducts: ['product-1']);

        $this->assertTrue($coupon->isProductEligible('product-1'));
        $this->assertFalse($coupon->isProductEligible('product-2'));
    }

    public function test_excluded_products_are_not_eligible(): void
    {
        $coupon = $this->createCoupon(excludedProducts: ['product-1']);

        $this->assertFalse($coupon->isProductEligible('product-1'));
        $this->assertTrue($coupon->isProductEligible('product-2'));
    }

    public function test_exclusion_wins_when_a_product_is_both_included_and_excluded(): void
    {
        $coupon = $this->createCoupon(eligibleProducts: ['product-1'], excludedProducts: ['product-1']);

        $this->assertFalse($coupon->isProductEligible('product-1'));
    }
}
