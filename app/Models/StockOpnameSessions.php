<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOpnameSessions extends Model
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

    // Progress hitung fisik
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

    // Scopes berguna
    public function scopeInProgress($query)
    {
        return $query->whereIn('status', ['draft', 'counting']);
    }
}
