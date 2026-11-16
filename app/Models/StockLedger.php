<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockLedger extends Model
{
    // Tidak ada SoftDeletes karena ini buku besar (Append-only)
    protected $fillable = [
        'product_id', 'user_id', 'type', 'qty', 
        'stock_before', 'stock_after', 'reference_type', 
        'reference_id', 'description'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}