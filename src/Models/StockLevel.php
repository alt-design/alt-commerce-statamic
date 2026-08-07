<?php

namespace AltDesign\AltCommerceStatamic\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $product_id
 * @property int $on_hand
 */
class StockLevel extends Model
{
    protected $guarded = [];

    protected $casts = [
        'on_hand' => 'integer',
    ];
}
