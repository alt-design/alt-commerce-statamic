<?php

namespace AltDesign\AltCommerceStatamic\Support;

use AltDesign\AltCommerceStatamic\Commerce\Order\StatamicOrder;
use Illuminate\Support\Str;

class GatewayUrlGenerator
{
    public function url(string $gateway, string $type, string $id): string
    {
        return match($gateway) {
            'braintree' => $this->braintreeUrl($type, $id),
            'stripe' => $this->stripeUrl($type, $id),
            default => throw new \Exception("Gateway $gateway is not supported")
        };
    }

    public function forOrder(StatamicOrder $order): array
    {
        $gatewayUrls = [];
        foreach ($order->transactions as $transaction) {
            $gatewayUrls[] = [
                'type' => 'transaction',
                'id' => $transaction->id,
                'url' => $this->url($transaction->gateway, 'transaction', $transaction->gatewayId),
            ];
        }
        foreach ($order->subscriptions as $subscription) {
            $gatewayUrls[] = [
                'type' => 'subscription',
                'id' => $subscription->id,
                'url' => $this->url($subscription->gateway, 'subscription', $subscription->gatewayId),
            ];
        }
        return $gatewayUrls;
    }

    protected function stripeUrl(string $type, string $id): string
    {
        $isTest = Str::contains(config('alt-commerce.payment_gateways.available.stripe.secret_key'), 'sk_test');

        if ($isTest) {
            return "https://dashboard.stripe.com/test/payments/{$id}";
        }

        return "https://dashboard.stripe.com/payments/{$id}";
    }

    protected function braintreeUrl(string $type, string $id): string
    {
        $mode = config('alt-commerce.payment_gateways.available.braintree.mode');
        $merchantId = config('alt-commerce.payment_gateways.available.braintree.merchant_id');

        $url = strtolower($mode) === 'sandbox' ?  'https://sandbox.braintreegateway.com' : 'http://braintreegateway.com';
        $url .= "/merchants/$merchantId/";
        $url .= match($type) {
            'transaction' =>"transactions/{$id}",
            'subscription' => "subscriptions/{$id}"
        };

        return $url;
    }
}