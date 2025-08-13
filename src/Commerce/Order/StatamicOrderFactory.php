<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Order;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Basket\BillingItem;
use AltDesign\AltCommerce\Commerce\Basket\DiscountItem;
use AltDesign\AltCommerce\Commerce\Basket\LineDiscount;
use AltDesign\AltCommerce\Commerce\Basket\LineItem;
use AltDesign\AltCommerce\Commerce\Basket\TaxItem;
use AltDesign\AltCommerce\Commerce\Customer\Address;
use AltDesign\AltCommerce\Commerce\Payment\Transaction;
use AltDesign\AltCommerce\Commerce\Billing\Subscription;
use AltDesign\AltCommerce\Contracts\Customer;
use AltDesign\AltCommerce\Contracts\OrderFactory;
use AltDesign\AltCommerce\Enum\DiscountType;
use AltDesign\AltCommerce\Enum\DurationUnit;
use AltDesign\AltCommerce\Enum\OrderStatus;
use AltDesign\AltCommerce\Enum\TransactionStatus;
use AltDesign\AltCommerce\Enum\TransactionType;
use AltDesign\AltCommerce\Enum\SubscriptionStatus;
use AltDesign\AltCommerce\Support\Duration;
use AltDesign\AltCommerceStatamic\Concerns\HasGatewayEntities;
use Carbon\Carbon;
use DateTimeImmutable;
use Illuminate\Support\Arr;
use Ramsey\Uuid\Uuid;
use Statamic\Entries\Entry;

class StatamicOrderFactory implements OrderFactory
{
    use HasGatewayEntities;

    /**
     * @param array<string, mixed> $additional
     */
    public function createFromBasket(
        string $orderNumber,
        Basket $basket,
        Customer $customer,
        Address|null $billingAddress = null,
        Address|null $shippingAddress = null,
        array $additional = [],
        string|null $orderId = null,
        \DateTimeImmutable|null $orderDate = null,
    ): StatamicOrder
    {

        return new StatamicOrder(
            id: $orderId ?? Uuid::uuid4(),
            customerId: $customer->customerId(),
            customerName: $customer->customerName(),
            customerEmail: $customer->customerEmail(),
            status: OrderStatus::DRAFT,
            currency: $basket->currency,
            orderNumber: $orderNumber,
            lineItems: $basket->lineItems,
            taxItems: $basket->taxItems,
            discountItems: $basket->discountItems,
            deliveryItems: $basket->deliveryItems,
            feeItems: $basket->feeItems,
            billingItems: $basket->billingItems,
            subTotal: $basket->subTotal,
            taxTotal: $basket->taxTotal,
            deliveryTotal: $basket->deliveryTotal,
            discountTotal: $basket->discountTotal,
            feeTotal: $basket->feeTotal,
            total: $basket->total,
            outstanding: $basket->total,
            orderDate: $orderDate ?? new \DateTimeImmutable(),
            createdAt: new \DateTimeImmutable(),
            basketId: $basket->id,
            billingAddress: $billingAddress,
            shippingAddress: $shippingAddress,
            transactions: [],
            additional: $additional,
        );
    }

    public function fromEntry(Entry $entry): StatamicOrder
    {

        $altOrder = $entry;

        $transactions = [];
        foreach ($altOrder['transactions'] ?? [] as $transaction) {
            $transactions[] = new Transaction(
                id: $transaction['id'],
                type: TransactionType::from($transaction['type']),
                status: TransactionStatus::from($transaction['status']),
                currency: $transaction['currency'],
                amount: $transaction['amount'],
                createdAt: new DateTimeImmutable($transaction['created_at']),
                rejectionReason: $transaction['rejection_reason'],
                additional: $transaction['additional'],
                gateway: $transaction['gateway'],
                gatewayId: $transaction['gateway_id'],
            );
        }

        $subscriptions = [];
        foreach ($altOrder['subscriptions'] ?? [] as $subscription) {
            $subscriptions[] = new Subscription(
                id: $subscription['id'],
                status: SubscriptionStatus::from($subscription['status']),
                createdAt: new DateTimeImmutable($subscription['created_at']),
                additional: $subscription['additional'],
                gateway: $subscription['gateway'],
                gatewayId: $subscription['gateway_id'],
            );

        }

        $lineItems = [];
        foreach ($altOrder['line_items'] ?? [] as $lineItem) {

            $discounts = [];
            foreach ($lineItem['discounts'] ?? [] as $discount) {
                $discounts[] = new LineDiscount(
                    id: $discount['id'],
                    discountItemId: $discount['discount_item_id'],
                    name: $discount['name'],
                    amount: $discount['amount'],
                );
            }

            $lineItems[] = new LineItem(
                id: $lineItem['id'],
                productId: $lineItem['product_id'],
                productName: $lineItem['product_name'],
                amount: $lineItem['amount'],
                quantity: $lineItem['quantity'],
                discounts: $discounts,
                discountTotal: $lineItem['discount_total'],
                subTotal: $lineItem['sub_total'],
                taxTotal: $lineItem['tax_total'],
                taxRate: $lineItem['tax_rate'],
                taxName: $lineItem['tax_name']
            );
        }

        $billingItems = [];
        foreach ($altOrder['billing_items'] ?? [] as $billingItem) {
            $billingItems[] = new BillingItem(
                id: $billingItem['id'],
                productId: $billingItem['product_id'],
                billingPlanId: $billingItem['billing_plan_id'],
                productName: $billingItem['product_name'],
                amount: $billingItem['amount'],
                billingInterval: new Duration($billingItem['billing_interval'], DurationUnit::from($billingItem['billing_interval_unit'])),
                trialPeriod: $billingItem['trial_period'] ?
                    new Duration($billingItem['trail_period'], DurationUnit::from($billingItem['trial_period_unit']))
                    : null,
                additional: $billingItem['additional'],
                gatewayEntities: $this->extractGatewayEntities($entry, 'billing_item', $billingItem['id']),
            );
        }

        $taxItems = [];
        foreach ($altOrder['tax_items'] ?? [] as $taxItem) {
            $taxItems[] = new TaxItem(
                name: $taxItem['name'],
                amount: $taxItem['amount'],
                rate: $taxItem['rate'],
            );
        }

        $discountItems = [];
        foreach ($altOrder['discount_items'] ?? [] as $item) {
            $discountItems[] = new DiscountItem(
                id: $item['id'],
                name: $item['name'],
                amount: $item['amount'],
                type: DiscountType::from($item['type']),
                couponCode: $item['coupon_code'],
            );
        }

        $notes = [];
        foreach ($altOrder['notes'] ?? [] as $note) {
            $notes[] = new StatamicOrderNote(
                id: $note['id'],
                content: $note['content'],
                userId: $note['user_id'],
                userName: $note['user_name'],
                createdAt: Carbon::parse($note['created_at']),
            );
        }

        $logs = [];
        foreach ($altOrder['logs'] ?? [] as $log) {
            $logs[] = new StatamicOrderLog(
                id: $log['id'],
                content: $log['content'],
                createdAt: Carbon::parse($log['created_at']),
            );
        }

        $order = new StatamicOrder(
            id: $entry->id(),
            customerId: $altOrder->originValue('customer_id'),
            customerName: $altOrder['customer_name'],
            customerEmail: $altOrder['customer_email'],
            status: OrderStatus::from($altOrder['order_status']),
            currency: $altOrder['currency'],
            orderNumber: $altOrder['order_number'],
            lineItems: $lineItems,
            taxItems: $taxItems,
            discountItems: $discountItems,
            deliveryItems: [],
            feeItems: [],
            billingItems: $billingItems,
            subTotal: $altOrder['sub_total'],
            taxTotal: $altOrder['tax_total'],
            deliveryTotal: $altOrder['delivery_total'],
            discountTotal: $altOrder['discount_total'],
            feeTotal: $altOrder['fee_total'],
            total: $altOrder['total'],
            outstanding: $altOrder['outstanding'],
            orderDate: new DateTimeImmutable($altOrder['order_date']),
            createdAt: new DateTimeImmutable($altOrder['created_at']),
            basketId: $altOrder['basket_id'],
            billingAddress: new Address(
                company: $altOrder['billing_company'],
                fullName: $altOrder['billing_full_name'],
                countryCode: $altOrder['billing_country_code'],
                postalCode: $altOrder['billing_postcode'],
                region: $altOrder['billing_region'],
                locality: $altOrder['billing_locality'],
                street: $altOrder['billing_street'],
                phoneNumber: $altOrder['billing_phone_number'],
            ),
            transactions: $transactions,
            subscriptions: $subscriptions,
            additional: Arr::except($altOrder->data()->toArray(), StatamicOrder::RESERVED_FIELDS),
        );

        $order->notes = $notes;
        $order->logs = $logs;

        return $order;
    }
}