<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Basket\Request\Middleware;

use AltDesign\AltCommerce\Commerce\Basket\BasketContext;
use App\Commerce\CalculateLineItemTax;

class AddItems
{
    public function handle(BasketContext $context, \Closure $next)
    {

        request()->validate([
            'items' => 'sometimes|array',
            'items.*.type' => 'required|string|in:line_item,discount_item',
            'items.*.product' => 'sometimes|array',
            'items.*.product.*' => 'required|string',
            'items.*.quantity' => 'sometimes|numeric',
            'items.*.price' => 'sometimes',
            'items.*.tax_auto' => 'sometimes',
            'items.*.tax_rate_manual' => 'nullable|numeric',
            'items.*.tax_name_manual' => 'nullable|string',
            'items.*.tax_amount_manual' => 'nullable',
            'items.*.discount_amount' => 'nullable',
            'items.*.discount_name' => 'nullable|string',
        ]);

        $lineItems = collect(request('items', []))
            ->filter(fn($item) => $item['type'] === 'line_item')
            ->filter(fn($item) => $item['quantity'] > 0)
            ->filter(fn($item) => $item['price']  > 0)
            ->filter(fn($item) => !!$item['product'][0]);

        foreach ($lineItems as $item) {
            $context->addToBasket(
                productId: $item['product'][0],
                quantity: $item['quantity'],
                price: floatval($item['price']) * 100,
            );

            $lineItem = $context->find($item['product'][0]);
            $taxAuto = $item['tax_auto'] == 'true';
            if (!$taxAuto) {
                CalculateLineItemTax::$skip[] = $lineItem->id;
                $lineItem->taxRate = $item['tax_rate_manual'] ?? 0;
                $lineItem->taxName = $item['tax_name_manual'] ?? '';
                $lineItem->taxTotal = (floatval($item['tax_amount_manual']) ?? 0) * 100;
            }
        }


        $discountItems = collect(request('items', []))
            ->filter(fn($item) => $item['type'] === 'discount_item')
            ->filter(fn($item) => floatval($item['discount_amount'] ?? 0)  > 0);


        foreach ($discountItems as $item) {
            $context->applyManualDiscount(floatval($item['discount_amount']) * 100, $item['discount_name'] ?? 'Manual discount');
        }


        return $next($context);
    }
}