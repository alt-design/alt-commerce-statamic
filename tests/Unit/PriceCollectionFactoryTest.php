<?php

namespace AltDesign\AltCommerceStatamic\Tests\Unit;

use AltDesign\AltCommerceStatamic\Commerce\Product\PriceCollectionFactory;
use AltDesign\AltCommerceStatamic\Support\CurrencyConvertor;
use Mockery;
use PHPUnit\Framework\TestCase;

class PriceCollectionFactoryTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    /**
     * Each of these lands just below the whole penny once multiplied as a float.
     */
    public function test_prices_round_to_the_nearest_penny(): void
    {
        $factory = new PriceCollectionFactory(Mockery::mock(CurrencyConvertor::class));

        foreach (['0.29' => 29, '37.62' => 3762, '567.17' => 56717] as $amount => $pence) {
            $prices = $factory->create([['currency' => 'GBP', 'amount' => $amount]]);

            $this->assertSame($pence, $prices->getAmount('GBP'), "£{$amount}");
        }
    }

    public function test_a_zero_price_is_left_out(): void
    {
        $factory = new PriceCollectionFactory(Mockery::mock(CurrencyConvertor::class));

        $prices = $factory->create([
            ['currency' => 'USD', 'amount' => '0'],
            ['currency' => 'GBP', 'amount' => '12.50'],
        ]);

        $this->assertFalse($prices->isCurrencySupported('USD'));
        $this->assertCount(1, $prices);
    }
}
