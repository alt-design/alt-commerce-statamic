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
use App\Models\User;
use Carbon\Carbon;
use DateTimeImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
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
            customer: $customer,
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
                ...$this->mapKeys($transaction, ['id', 'currency', 'amount', 'rejection_reason', 'gateway', 'gateway_id', 'additional']),
                type: TransactionType::from($transaction['type']),
                status: TransactionStatus::from($transaction['status']),
                createdAt: new DateTimeImmutable($transaction['created_at']),
            );
        }

        $subscriptions = [];
        foreach ($altOrder['subscriptions'] ?? [] as $subscription) {
            $subscriptions[] = new Subscription(
                ...$this->mapKeys($subscription, ['id', 'additional', 'gateway', 'gateway_id']),
                status: SubscriptionStatus::from($subscription['status']),
                createdAt: new DateTimeImmutable($subscription['created_at']),
            );

        }

        $lineItems = [];
        foreach ($altOrder['line_items'] ?? [] as $lineItem) {

            $discounts = [];
            foreach ($lineItem['discounts'] ?? [] as $discount) {
                $discounts[] = new LineDiscount(...$this->mapKeys($discount, ['id', 'discount_item_id', 'name', 'amount']));
            }

            $lineItems[] = new LineItem(
                ...$this->mapKeys($lineItem, [
                    'id',
                    'product_id',
                    'product_name',
                    'amount',
                    'quantity',
                    'discount_total',
                    'sub_total',
                    'tax_total',
                    'tax_rate',
                    'tax_name',
                ]),
                discounts: $discounts
            );
        }

        $billingItems = [];
        foreach ($altOrder['billing_items'] ?? [] as $billingItem) {
            $billingItems[] = new BillingItem(
                ...$this->mapKeys($billingItem, ['id', 'product_id', 'billing_plan_id', 'product_name', 'amount', 'additional']),
                billingInterval: new Duration($billingItem['billing_interval'], DurationUnit::from($billingItem['billing_interval_unit'])),
                trialPeriod: $billingItem['trial_period'] ?
                    new Duration($billingItem['trail_period'], DurationUnit::from($billingItem['trial_period_unit']))
                    : null,
                gatewayEntities: $this->extractGatewayEntities($entry, 'billing_item', $billingItem['id']),
            );
        }

        $taxItems = [];
        foreach ($altOrder['tax_items'] ?? [] as $taxItem) {
            $taxItems[] = new TaxItem(...$this->mapKeys($taxItem, ['name', 'amount', 'rate']));
        }

        $discountItems = [];
        foreach ($altOrder['discount_items'] ?? [] as $item) {
            $discountItems[] = new DiscountItem(
                ...$this->mapKeys($item, ['id', 'name', 'amount', 'coupon_code']),
                type: DiscountType::from($item['type']),
            );
        }

        $notes = [];
        foreach ($altOrder['notes'] ?? [] as $note) {
            $notes[] = new StatamicOrderNote(
                ...$this->mapKeys($note, ['id', 'content', 'user_id', 'user_name']),
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

        $customer = User::query()->find($entry->get('customer_id'));
        $order = new StatamicOrder(
            id: $entry->id(),
            customer: $customer,
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

    protected function mapKeys(array $array, array $only): array
    {
        return collect($array)
            ->only($only)
            ->mapWithKeys(fn($value, $key) => [Str::camel($key) => $value])
            ->toArray();
    }
}