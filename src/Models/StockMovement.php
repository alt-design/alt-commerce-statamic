<?php

namespace AltDesign\AltCommerceStatamic\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $product_id
 * @property string|null $sku
 * @property int $quantity
 * @property string|null $reason
 * @property string|null $reference
 * @property string|null $note
 * @property string|null $user_id
 */
class StockMovement extends Model
{
    protected $guarded = [];

    protected $casts = [
        'quantity' => 'integer',
    ];
}
