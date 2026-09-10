<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $stock_opname_session_id
 * @property int $product_id
 * @property int|null $product_batch_id
 * @property int|null $warehouse_location_id
 * @property string|int|float $system_quantity
 * @property string|int|float|null $physical_quantity
 * @property string|int|float|null $difference_quantity
 * @property string|null $condition
 * @property string|null $difference_reason
 * @property string|null $difference_note
 * @property bool $is_significant
 * @property int|null $counted_by
 * @property Carbon|null $counted_at
 * @property Carbon|null $reconciled_at
 * @property Carbon|null $posted_at
 * @property string|null $notes
 * @property-read bool $is_counted
 */
class StockOpnameItem extends Model
{
    protected $fillable = [
        'stock_opname_session_id', 'product_id', 'product_batch_id', 'warehouse_location_id',
        'system_quantity', 'physical_quantity', 'difference_quantity',
        'condition', 'difference_reason', 'difference_note', 'is_significant',
        'counted_by', 'counted_at', 'reconciled_at', 'posted_at', 'notes',
    ];

    protected $casts = [
        'system_quantity' => 'decimal:3',
        'physical_quantity' => 'decimal:3',
        'difference_quantity' => 'decimal:3',
        'is_significant' => 'boolean',
        'counted_at' => 'datetime',
        'reconciled_at' => 'datetime',
        'posted_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(StockOpnameSession::class, 'stock_opname_session_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function batch()
    {
        return $this->belongsTo(ProductBatch::class, 'product_batch_id');
    }

    public function location()
    {
        return $this->belongsTo(WarehouseLocation::class, 'warehouse_location_id');
    }

    public function counter()
    {
        return $this->belongsTo(User::class, 'counted_by');
    }

    public function getIsCountedAttribute(): bool
    {
        return ! is_null($this->physical_quantity);
    }
}
