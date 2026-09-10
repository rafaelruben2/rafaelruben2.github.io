<?php

namespace App\Models;

 use Illuminate\Database\Eloquent\Model;

class StockOpnameApproval extends Model
{
    protected $fillable = [
        'stock_opname_session_id', 'user_id', 'action', 'note',
        'created_at', 'updated_at',
        ];

    public function session()
    {
        return $this->belongsTo(StockOpnameSessions::class, 'stock_opname_session_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}