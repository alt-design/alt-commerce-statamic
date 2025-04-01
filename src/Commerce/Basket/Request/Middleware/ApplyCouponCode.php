<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Basket\Request\Middleware;

use AltDesign\AltCommerce\Commerce\Basket\BasketContext;

class ApplyCouponCode
{
    public function handle(BasketContext $context, \Closure $next)
    {

        $validated = request()->validate([
            'discount_code' => 'nullable|string',
        ]);

        if ($discountCode = $validated['discount_code'] ?? null) {
            $context->applyCoupon(coupon: $discountCode);
        }

        return $next($context);
    }
}