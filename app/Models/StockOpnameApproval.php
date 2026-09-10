<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $stock_opname_session_id
 * @property int $user_id
 * @property string $action
 * @property string|null $note
 */
class StockOpnameApproval extends Model
{
    protected $fillable = [
        'stock_opname_session_id', 'user_id', 'action', 'note',
        'created_at', 'updated_at',
    ];

    public function session()
    {
        return $this->belongsTo(StockOpnameSession::class, 'stock_opname_session_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
