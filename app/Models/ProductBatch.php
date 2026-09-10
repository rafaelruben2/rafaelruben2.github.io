<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductBatch extends Model
{
    protected $fillable = [
        'product_id',
        'batch_number', // sesuaikan dengan nama kolom asli di tabel product_batches
        'expiry_date',
        // tambahkan kolom lain sesuai struktur tabel kamu
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function stockOpnameItems()
    {
        return $this->hasMany(StockOpnameItem::class, 'product_batch_id');
    }
}