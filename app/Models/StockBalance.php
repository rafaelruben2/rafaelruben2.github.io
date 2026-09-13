<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $product_id
 * @property int|null $product_batch_id
 * @property int $warehouse_location_id
 * @property string|int|float $quantity
 */
class StockBalance extends Model
{
    protected $guarded = [];
}
