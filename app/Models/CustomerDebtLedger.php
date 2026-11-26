<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerDebtLedger extends Model
{
    protected $fillable = [
        'customer_id',
        'user_id',
        'type',
        'amount',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
