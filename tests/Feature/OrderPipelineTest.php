<?php

namespace AltDesign\AltCommerceStatamic\Tests\Feature;

use AltDesign\AltCommerce\Enum\OrderStatus;
use AltDesign\AltCommerceStatamic\Commerce\Order\StatamicOrder;
use AltDesign\AltCommerceStatamic\Commerce\Order\StatamicOrderRepository;
use AltDesign\AltCommerceStatamic\OrderProcessor\OrderProcessor;
use AltDesign\AltCommerceStatamic\OrderProcessor\Tasks\UpdateStatusToProcessed;
use AltDesign\AltCommerceStatamic\ServiceProvider;
use DateTimeImmutable;
use Illuminate\Support\Facades\Queue;
use Statamic\Facades\Collection;
use Statamic\Testing\AddonTestCase;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

class OrderPipelineTest extends AddonTestCase
{
    use PreventsSavingStacheItemsToDisk;

    protected string $addonServiceProvider = ServiceProvider::class;

    protected function setUp(): void
    {
        parent::setUp();

        Collection::make('orders')->save();
    }

    public function test_the_shipped_pipeline_completes_an_order_on_a_default_laravel_queue(): void
    {
        app(OrderProcessor::class)->process($this->createOrder());

        $this->assertSame(OrderStatus::COMPLETE, app(StatamicOrderRepository::class)->find('order-1')->status);
    }

    public function test_a_stage_without_a_connection_uses_the_one_the_site_is_configured_with(): void
    {
        config(['queue.default' => 'redis']);
        Queue::fake();

        $order = $this->createOrder(OrderStatus::PROCESSING);

        app(OrderProcessor::class)->process($order, stage: 'process');

        Queue::assertPushedOn('process-order', UpdateStatusToProcessed::class, fn ($job) => $job->connection === null);
    }

    protected function createOrder(OrderStatus $status = OrderStatus::DRAFT): StatamicOrder
    {
        $order = new StatamicOrder(
            id: 'order-1',
            customerId: 'customer-1',
            customerName: 'Test Customer',
            customerEmail: 'customer@example.com',
            status: $status,
            currency: 'GBP',
            orderNumber: 'ORD-1',
            lineItems: [],
            taxItems: [],
            discountItems: [],
            deliveryItems: [],
            feeItems: [],
            billingItems: [],
            subTotal: 1000,
            taxTotal: 0,
            deliveryTotal: 0,
            discountTotal: 0,
            feeTotal: 0,
            total: 1000,
            outstanding: 0,
            orderDate: new DateTimeImmutable(),
            createdAt: new DateTimeImmutable(),
        );

        app(StatamicOrderRepository::class)->save($order);

        return $order;
    }
}
