<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property Carbon $opname_date
 * @property string $type
 * @property string $status
 * @property int|null $created_by
 * @property int|null $verified_by
 * @property int|null $approved_by
 * @property Carbon|null $snapshot_at
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $approved_at
 * @property string|null $approval_note
 * @property-read int $total_items
 * @property-read int $counted_items
 * @property-read int $progress_percent
 * @property-read bool $is_fully_counted
 */
class StockOpnameSession extends Model
{
    protected $fillable = [
        'code', 'opname_date', 'type', 'status',
        'created_by', 'verified_by', 'approved_by',
        'snapshot_at', 'reviewed_at', 'approved_at', 'approval_note',
    ];

    protected $casts = [
        'opname_date' => 'datetime',
        'snapshot_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(StockOpnameItem::class, 'stock_opname_session_id');
    }

    public function approvals()
    {
        return $this->hasMany(StockOpnameApproval::class, 'stock_opname_session_id')->latest();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getTotalItemsAttribute(): int
    {
        return $this->items()->count();
    }

    public function getCountedItemsAttribute(): int
    {
        return $this->items()->whereNotNull('physical_quantity')->count();
    }

    public function getProgressPercentAttribute(): int
    {
        $total = $this->total_items;

        return $total > 0 ? (int) round(($this->counted_items / $total) * 100) : 0;
    }

    public function getIsFullyCountedAttribute(): bool
    {
        return $this->total_items > 0 && $this->counted_items === $this->total_items;
    }

    public function scopeInProgress($query)
    {
        return $query->whereIn('status', ['draft', 'counting']);
    }
}
