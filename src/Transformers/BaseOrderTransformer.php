<?php

namespace AltDesign\AltCommerceStatamic\Transformers;

use AltDesign\AltCommerce\Commerce\Basket\BillingItem;
use AltDesign\AltCommerce\Commerce\Basket\DiscountItem;
use AltDesign\AltCommerce\Commerce\Basket\LineDiscount;
use AltDesign\AltCommerce\Commerce\Basket\LineItem;
use AltDesign\AltCommerce\Commerce\Basket\TaxItem;
use AltDesign\AltCommerce\Commerce\Customer\Address;
use AltDesign\AltCommerce\Commerce\Order\Order;
use AltDesign\AltCommerce\Commerce\Billing\Subscription;
use AltDesign\AltCommerce\Commerce\Payment\Transaction;
use AltDesign\AltCommerce\Enum\DiscountType;
use AltDesign\AltCommerceStatamic\Commerce\Order\StatamicOrder;
use AltDesign\AltCommerceStatamic\Commerce\Order\StatamicOrderLog;
use AltDesign\AltCommerceStatamic\Commerce\Order\StatamicOrderNote;
use AltDesign\AltCommerceStatamic\Concerns\HasGatewayEntities;
use AltDesign\AltCommerceStatamic\Contracts\OrderTransformer;
use League\ISO3166\ISO3166;

class BaseOrderTransformer implements OrderTransformer
{
    use HasGatewayEntities;

    public function toEntryData(StatamicOrder $order): array
    {

        $items = [];
        foreach ($order->lineItems as $lineItem) {
            $items[] = [
                'product' => $lineItem->productId,
                'price' => $lineItem->amount,
                'quantity' => $lineItem->quantity,
                'subtotal' => $lineItem->subTotal,
                'tax_auto' => true,
                'tax_name' => $lineItem->taxName,
                'tax_rate' => $lineItem->taxRate,
                'tax_amount' => $lineItem->taxTotal,
                'type' => 'line_item',
                'enabled' => true,
                'options' => $this->buildLineItemOptions($lineItem),
            ];
        }

        foreach ($order->discountItems as $discountItem) {
            if ($discountItem->type === DiscountType::MANUAL) {
                $items[] = [
                    'discount_name' => $discountItem->name,
                    'discount_amount' => $discountItem->amount,
                    'type' => 'discount_item',
                    'enabled' => true
                ];
            }
        }

        return [
            'id' => $order->id,
            'title' => 'Order #'.$order->orderNumber,
            'created_at' => $order->createdAt->format('Y-m-d H:i:s'),
            'order_date' => $order->orderDate->format('Y-m-d H:i:s'),
            'basket_id' => $order->basketId,
            'customer_id' => $order->customerId,
            'customer_email' => $order->customerEmail,
            'customer_name' => $order->customerName,
            'order_number' => $order->orderNumber,
            'order_status' => $order->status->value,
            'currency' => $order->currency,
            'sub_total' => $order->subTotal,
            'delivery_total' => $order->deliveryTotal,
            'tax_total' => $order->taxTotal,
            'discount_total' => $order->discountTotal,
            'fee_total' => $order->feeTotal,
            'total' => $order->total,
            'outstanding' => $order->outstanding,
            'notes' => collect($order->notes)
                ->map(fn(StatamicOrderNote $note) => [
                    'id' => $note->id,
                    'content' => $note->content,
                    'user_id' => $note->userId,
                    'user_name' => $note->userName,
                    'created_at' => $note->createdAt->toISOString()
                ])
                ->toArray(),
            'logs' => collect($order->logs)
                ->map(fn(StatamicOrderLog $log) => [
                    'id' => $log->id,
                    'content' => $log->content,
                    'created_at' => $log->createdAt->toISOString()
                ])
                ->toArray(),
            'transactions' => collect($order->transactions)
                ->map(fn(Transaction $transaction) => [
                    'id' => $transaction->id,
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                    'status' => $transaction->status->value,
                    'created_at' => $transaction->createdAt->format('Y-m-d H:i:s'),
                    'additional' => $transaction->additional,
                    'rejection_reason' => $transaction->rejectionReason,
                    'type' => $transaction->type->value,
                    'gateway' => $transaction->gateway,
                    'gateway_id' => $transaction->gatewayId,
                ])
                ->toArray(),
            'subscriptions' => collect($order->subscriptions)
                ->map(fn(Subscription $subscription) => [
                    'id' => $subscription->id,
                    'status' => $subscription->status->value,
                    'created_at' => $subscription->createdAt->format('Y-m-d H:i:s'),
                    'additional' => $subscription->additional,
                    'gateway' => $subscription->gateway,
                    'gateway_id' => $subscription->gatewayId,
                ])
                ->toArray(),
            'line_items' => collect($order->lineItems)
                ->map(fn(LineItem $lineItem) => [
                    'id' => $lineItem->id,
                    'product_id' => $lineItem->productId,
                    'product_name' => $lineItem->productName,
                    'quantity' => $lineItem->quantity,
                    'discount_total' => $lineItem->discountTotal,
                    'discounts' => collect($lineItem->discounts)
                        ->map(fn(LineDiscount $lineDiscount) => [
                            'name' => $lineDiscount->name,
                            'id' => $lineDiscount->id,
                            'amount' => $lineDiscount->amount,
                            'discount_item_id' => $lineDiscount->discountItemId,
                        ])
                        ->toArray(),
                    'sub_total' => $lineItem->subTotal,
                    'amount' => $lineItem->amount,
                    'tax_total' => $lineItem->taxTotal,
                    'tax_rate' => $lineItem->taxRate,
                    'tax_name' => $lineItem->taxName,
                    'options' => $this->buildLineItemOptions($lineItem),
                ])
                ->toArray(),
            'billing_items' => collect($order->billingItems)
                ->map(fn(BillingItem $billingItem) => [
                    'id' => $billingItem->id,
                    'product_id' => $billingItem->productId,
                    'product_name' => $billingItem->productName,
                    'billing_plan_id' => $billingItem->id,
                    'amount' => $billingItem->amount,
                    'billing_interval' => $billingItem->billingInterval->amount,
                    'billing_interval_unit' => $billingItem->billingInterval->unit->value,
                    'trial_period' => $billingItem->trialPeriod?->amount,
                    'trial_period_unit' => $billingItem->trialPeriod?->unit->value,
                    'additional' => $billingItem->additional,
                ])
                ->toArray(),
            'tax_items' => collect($order->taxItems)
                ->map(fn(TaxItem $item) => [
                    'name' => $item->name,
                    'amount' => $item->amount,
                    'rate' => $item->rate,
                ])
                ->toArray(),
            'discount_items' => collect($order->discountItems)
                ->map(fn(DiscountItem $item) => [
                    'name' => $item->name,
                    'amount' => $item->amount,
                    'id' => $item->id,
                    'type' => $item->type->value,
                    'coupon_code' => $item->couponCode
                ])
                ->toArray(),
            'gateway_entities' => $this->buildGatewayEntities($order),
            ...$this->buildAddress('billing', $order->billingAddress),
            'coupon_code' => collect($order->discountItems)->first(fn(DiscountItem $item) => $item->type === DiscountType::PRODUCT_COUPON)?->couponCode,
            'items' => $items,
            ...$order->additional,
        ];
    }

    protected function buildLineItemOptions(LineItem $lineItem): array
    {
        return collect($lineItem->options)
            ->mapWithKeys(fn($val, $key) => [$key => is_array($val) ? json_encode($val) : $val])
            ->toArray();
    }

    protected function buildGatewayEntities(Order $order): array
    {
        $gatewayEntities = [];
        foreach ($order->billingItems as $billingItem) {
            $gatewayEntities = array_merge($gatewayEntities, $this->mapGatewayEntities('billing_item', $billingItem->id, $billingItem->gatewayEntities));
        }

        return $gatewayEntities;
    }

    protected function buildAddress(string $prefix, Address|null $address): array
    {
        $countryCode = $address?->countryCode;
        if ($countryCode && strlen($countryCode) === 2) {
            $countryCode = (new ISO3166)->alpha2($countryCode)['alpha3'];
        }

        return [
            $prefix.'_full_name' => $address?->fullName,
            $prefix.'_company' => $address?->company,
            $prefix.'_phone_number' => $address?->phoneNumber,
            $prefix.'_street' => $address?->street,
            $prefix.'_locality' => $address?->locality,
            $prefix.'_region' => $address?->region,
            $prefix.'_postcode' => $address?->postalCode,
            $prefix.'_country_code' => $countryCode,
        ];
    }

}