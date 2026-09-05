<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpnameItem extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'physical_quantity' => 'decimal:3',
            'system_quantity' => 'decimal:3',
            'difference_quantity' => 'decimal:3',
            'is_significant' => 'boolean',
            'counted_at' => 'datetime',
            'reconciled_at' => 'datetime',
            'posted_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(StockOpnameSession::class, 'stock_opname_session_id');
    }
}
