<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Basket\Request\Middleware;

use AltDesign\AltCommerce\Commerce\Basket\BasketContext;

class ApplyCouponCode
{
    public function handle(BasketContext $context, \Closure $next)
    {

        request()->validate([
            'coupon_code' => 'nullable|string',
        ]);

        if ($code = request('coupon_code')) {
            try {
                $context->applyCoupon(coupon: $code);
            } catch (\Throwable $e) {}
        }

        return $next($context);
    }
}