<?php

namespace AltDesign\AltCommerceStatamic\Http\Controllers;

use AltDesign\AltCommerce\Contracts\StockRepository;
use AltDesign\AltCommerceStatamic\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockController
{
    public function show(string $productId, StockRepository $stock): JsonResponse
    {
        return response()->json($this->state($productId, $stock));
    }

    public function adjust(Request $request, string $productId, StockRepository $stock): JsonResponse
    {
        $data = $request->validate([
            'quantity' => 'required|integer',
            'reason' => 'nullable|string',
            'note' => 'nullable|string',
        ]);

        $stock->adjust(
            productId: $productId,
            quantity: (int) $data['quantity'],
            reason: $data['reason'] ?? 'manual',
            note: $data['note'] ?? null,
        );

        return response()->json($this->state($productId, $stock));
    }

    /**
     * @return array{level: int|null, movements: array<int, array<string, mixed>>}
     */
    protected function state(string $productId, StockRepository $stock): array
    {
        return [
            'level' => $stock->available($productId),
            'movements' => StockMovement::query()
                ->where('product_id', $productId)
                ->latest()
                ->limit(5)
                ->get(['quantity', 'reason', 'note', 'created_at'])
                ->toArray(),
        ];
    }
}
