<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['sale_id', 'wallet_id', 'method', 'amount'];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }
}
