<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $product_category_id
 * @property string $sku
 * @property string $barcode
 * @property string $name
 * @property string|null $image_path
 * @property string $unit
 * @property int $unit_conversion
 * @property bool $is_active
 */
class Product extends Model
{
    protected $guarded = [];

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }
}
