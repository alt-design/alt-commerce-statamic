<?php

namespace AltDesign\AltCommerceStatamic\Tests\Unit;

use AltDesign\AltCommerceStatamic\Commerce\Coupon\StatamicCouponFactory;
use AltDesign\AltCommerceStatamic\Commerce\Coupon\StatamicProductCoupon;
use Mockery;
use PHPUnit\Framework\TestCase;
use Statamic\Entries\Entry;

class StatamicCouponFactoryTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function createEntry(array $data): Entry
    {
        $entry = Mockery::mock(Entry::class);
        $entry->allows()->id()->andReturn('coupon-1');
        $entry->allows('get')->andReturnUsing(fn (string $key) => $data[$key] ?? null);

        return $entry;
    }

    public function test_percentage_coupon(): void
    {
        $coupon = (new StatamicCouponFactory())->fromEntry($this->createEntry([
            'title' => '10% off',
            'code' => '10OFF',
            'type' => 'percentage',
            'percentage' => 10,
        ]), 'GBP');

        $this->assertTrue($coupon->isPercentage());
        $this->assertSame(10, $coupon->discountAmount());
    }

    public function test_fixed_coupon_uses_pricing_for_currency(): void
    {
        $coupon = (new StatamicCouponFactory())->fromEntry($this->createEntry([
            'title' => '£5 off',
            'code' => '5OFF',
            'type' => 'fixed',
            'pricing' => [
                ['currency' => 'USD', 'amount' => 10],
                ['currency' => 'GBP', 'amount' => 5],
            ],
        ]), 'GBP');

        $this->assertFalse($coupon->isPercentage());
        $this->assertSame(500, $coupon->discountAmount());
    }

    public function test_fixed_coupon_rounds_to_the_nearest_penny(): void
    {
        $coupon = (new StatamicCouponFactory())->fromEntry($this->createEntry([
            'title' => '£37.62 off',
            'code' => 'ODD',
            'type' => 'fixed',
            'pricing' => [
                ['currency' => 'GBP', 'amount' => '37.62'],
            ],
        ]), 'GBP');

        $this->assertSame(3762, $coupon->discountAmount());
    }

    public function test_minimum_spend_uses_amount_for_currency(): void
    {
        $coupon = (new StatamicCouponFactory())->fromEntry($this->createEntry([
            'title' => '10% off',
            'code' => '10OFF',
            'type' => 'percentage',
            'percentage' => 10,
            'minimum_spend' => [
                ['currency' => 'USD', 'amount' => 100],
                ['currency' => 'GBP', 'amount' => 50],
            ],
        ]), 'GBP');

        $this->assertSame(5000, $coupon->minimumSpend());
    }

    public function test_minimum_spend_defaults_to_zero_when_currency_is_missing(): void
    {
        $coupon = (new StatamicCouponFactory())->fromEntry($this->createEntry([
            'title' => '10% off',
            'code' => '10OFF',
            'type' => 'percentage',
            'percentage' => 10,
            'minimum_spend' => [
                ['currency' => 'USD', 'amount' => 100],
            ],
        ]), 'GBP');

        $this->assertSame(0, $coupon->minimumSpend());
    }

    public function test_minimum_spend_ignores_legacy_float_values(): void
    {
        $coupon = (new StatamicCouponFactory())->fromEntry($this->createEntry([
            'title' => '10% off',
            'code' => '10OFF',
            'type' => 'percentage',
            'percentage' => 10,
            'minimum_spend' => 50.0,
        ]), 'GBP');

        $this->assertSame(0, $coupon->minimumSpend());
    }

    public function test_included_and_excluded_products_are_passed_through(): void
    {
        $coupon = (new StatamicCouponFactory())->fromEntry($this->createEntry([
            'title' => '10% off',
            'code' => '10OFF',
            'type' => 'percentage',
            'percentage' => 10,
            'included_products' => ['product-1'],
            'excluded_products' => ['product-2'],
        ]), 'GBP');

        $this->assertInstanceOf(StatamicProductCoupon::class, $coupon);
        $this->assertTrue($coupon->isProductEligible('product-1'));
        $this->assertFalse($coupon->isProductEligible('product-2'));
    }
}
