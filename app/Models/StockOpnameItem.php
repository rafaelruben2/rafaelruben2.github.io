<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
