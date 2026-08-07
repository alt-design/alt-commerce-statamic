<?php

namespace AltDesign\AltCommerceStatamic\Commerce\Basket\Request\Middleware;

use AltDesign\AltCommerce\Commerce\Basket\BasketContext;
use AltDesign\AltCommerce\Commerce\Pipeline\RecalculateBasket\CalculateLineItemTax;

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
            'items.*.options' => 'sometimes|array',
        ]);

        $lineItems = collect(request('items', []))
            ->filter(fn($item) => $item['type'] === 'line_item')
            ->filter(fn($item) => $item['quantity'] > 0)
            ->filter(fn($item) => $this->parseAmount($item['price'] ?? 0) > 0)
            ->filter(fn($item) => !empty($item['product'][0]));

        foreach ($lineItems as $item) {
            $context->addToBasket(
                productId: $item['product'][0],
                quantity: $item['quantity'],
                price: (int) round($this->parseAmount($item['price']) * 100),
                options: $item['options'] ?? [],
            );

            $lineItem = $context->find($item['product'][0]);
            $taxAuto = $item['tax_auto'] == 'true';
            if (!$taxAuto) {
                CalculateLineItemTax::$skip[] = $lineItem->id;
                $lineItem->taxRate = $item['tax_rate_manual'] ?? 0;
                $lineItem->taxName = $item['tax_name_manual'] ?? '';
                $lineItem->taxTotal = (int) round($this->parseAmount($item['tax_amount_manual'] ?? 0) * 100);
            }
        }


        $discountItems = collect(request('items', []))
            ->filter(fn($item) => $item['type'] === 'discount_item')
            ->filter(fn($item) => $this->parseAmount($item['discount_amount'] ?? 0) > 0);


        foreach ($discountItems as $item) {
            $context->applyManualDiscount((int) round($this->parseAmount($item['discount_amount']) * 100), $item['discount_name'] ?? 'Manual discount');
        }


        return $next($context);
    }

    /**
     * Form values arrive preProcessed by the money fieldtype ("2,100.00");
     * floatval() reads that as 2, silently corrupting prices over 999.
     */
    protected function parseAmount(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $parsed = (new \NumberFormatter('en_GB', \NumberFormatter::DECIMAL))->parse((string) $value);

        return $parsed === false ? 0.0 : (float) $parsed;
    }
}
