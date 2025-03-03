<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Order;

use AltDesign\AltCommerce\Commerce\Basket\Basket;
use AltDesign\AltCommerce\Commerce\Basket\BillingItem;
use AltDesign\AltCommerce\Commerce\Basket\CouponDiscountItem;
use AltDesign\AltCommerce\Commerce\Basket\LineItem;
use AltDesign\AltCommerce\Commerce\Basket\TaxItem;
use AltDesign\AltCommerce\Commerce\Customer\Address;
use AltDesign\AltCommerce\Commerce\Payment\Transaction;
use AltDesign\AltCommerce\Commerce\Billing\Subscription;
use AltDesign\AltCommerce\Contracts\CouponRepository;
use AltDesign\AltCommerce\Contracts\Customer;
use AltDesign\AltCommerce\Contracts\OrderFactory;
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
use Ramsey\Uuid\Uuid;
use Statamic\Entries\Entry;

class StatamicOrderFactory implements OrderFactory
{
    use HasGatewayEntities;

    public function __construct(protected CouponRepository $couponRepository)
    {

    }
    /**
     * @param array<string, mixed> $additional
     */
    public function createFromBasket(
        string $orderNumber,
        Basket $basket,
        Customer $customer,
        array $additional = [],
        string|null $orderId = null,
        \DateTimeImmutable|null $orderDate = null,
    ): StatamicOrder
    {
        $billingAddress = ($additional['billing_address'] ?? null) instanceof Address ?
            $additional['billing_address'] : null;

        $shippingAddress = ($additional['shipping_address'] ?? null) instanceof Address ?
            $additional['shipping_address'] : null;

        unset($additional['billing_address'], $additional['shipping_address']);

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
        $transactions = [];
        foreach ($entry->get('transactions') ?? [] as $transaction) {
            $transactions[] = new Transaction(
                id: $transaction['id'],
                type: TransactionType::from($transaction['type']),
                status: TransactionStatus::from($transaction['status']),
                currency: $transaction['currency'],
                amount: $transaction['amount'],
                createdAt: new DateTimeImmutable($transaction['created_at']),
                rejectionReason: $transaction['rejection_reason'],
                additional: $transaction['additional'],
                gateway: $transaction['gateway'] ?? null,
                gatewayId: $transaction['gateway_id'] ?? null,
            );
        }

        $subscriptions = [];
        foreach ($entry->get('subscriptions') ?? [] as $subscription) {
            $subscriptions[] = new Subscription(
                id: $subscription['id'],
                status: SubscriptionStatus::from($subscription['status']),
                createdAt: new DateTimeImmutable($subscription['created_at']),
                additional: $subscription['additional'],
                gateway: $subscription['gateway'] ?? null,
                gatewayId: $subscription['gateway_id'] ?? null,
            );

        }

        $lineItems = [];
        foreach ($entry->get('line_items') ?? [] as $lineItem) {
            $lineItems[] = new LineItem(
                id: $lineItem['id'],
                productId: $lineItem['product_id'],
                productName: $lineItem['product_name'],
                amount: $lineItem['amount'],
                quantity: $lineItem['quantity'],
                discountTotal: $lineItem['discount_total'],
                subTotal: $lineItem['sub_total'],
                taxTotal: $lineItem['tax_total'],
                taxRate: $lineItem['tax_rate'],
                taxName: $lineItem['tax_name'],
            );
        }

        $billingItems = [];
        foreach ($entry->get('billing_items') ?? [] as $billingItem) {
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
        foreach ($entry->get('tax_items') ?? [] as $taxItem) {
            $taxItems[] = new TaxItem(
                name: $taxItem['name'],
                amount: $taxItem['amount'],
                rate: $taxItem['rate'],
            );
        }

        $discountItems = [];
        foreach ($entry->get('applied_coupons') ?? [] as $coupon) {
            $discountItems[] = new CouponDiscountItem(
                name: $coupon['name'],
                amount: $coupon['amount'],
                coupon: $this->couponRepository->find($entry->get('currency'), $coupon['code'])
            );
        }

        $notes = [];
        foreach ($entry->get('notes') ?? [] as $note) {
            $notes[] = new StatamicOrderNote(
                id: $note['id'],
                content: $note['content'],
                userId: $note['user_id'],
                userName: $note['user_name'],
                createdAt: Carbon::parse($note['created_at']),
            );
        }

        $logs = [];
        foreach ($entry->get('logs') ?? [] as $log) {
            $logs[] = new StatamicOrderLog(
                id: $log['id'],
                content: $log['content'],
                createdAt: Carbon::parse($log['created_at']),
            );
        }

        $order = new StatamicOrder(
            id: $entry->id(),
            customer: User::query()->find($entry->get('customer_id')),
            status: OrderStatus::from($entry->get('order_status')),
            currency: $entry->get('currency'),
            orderNumber: $entry->get('order_number'),
            lineItems: $lineItems,
            taxItems: $taxItems,
            discountItems: $discountItems,
            deliveryItems: [],
            feeItems: [],
            billingItems: $billingItems,
            subTotal: (int)$entry->get('sub_total'),
            taxTotal: (int)$entry->get('tax_total'),
            deliveryTotal: (int)$entry->get('delivery_total'),
            discountTotal: (int)$entry->get('discount_total'),
            feeTotal: (int)$entry->get('fee_total'),
            total: (int)$entry->get('total'),
            outstanding: (int)$entry->get('outstanding'),
            orderDate: new DateTimeImmutable($entry->get('order_date')),
            createdAt: new DateTimeImmutable($entry->get('created_at')),
            basketId: $entry->get('basket_id'),
            billingAddress: new Address(
                company: $entry->get('billing_company'),
                fullName: $entry->get('billing_full_name'),
                countryCode: $entry->get('billing_country_code'),
                postalCode: $entry->get('billing_postcode'),
                region: $entry->get('billing_region'),
                locality: $entry->get('billing_locality'),
                street: $entry->get('billing_street'),
                phoneNumber: $entry->get('billing_phone_number'),
            ),
            transactions: $transactions,
            subscriptions: $subscriptions,
            additional: $entry->get('additional') ?? [],
        );

        $order->notes = $notes;
        $order->logs = $logs;

        return $order;
    }
}