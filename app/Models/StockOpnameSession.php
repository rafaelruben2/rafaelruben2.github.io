<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockOpnameSession extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'opname_date' => 'date',
            'snapshot_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockOpnameItem::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(StockOpnameApproval::class);
    }
}
