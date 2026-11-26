<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 
        'type', 
        'phone', 
        'address', 
        'is_active',
        'points',
        'deposit_balance',
        'debt_balance',
        'total_spent_history',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'deposit_balance' => 'decimal:2',
        'debt_balance' => 'decimal:2',
        'total_spent_history' => 'decimal:2',
    ];

    public function debtLedgers()
    {
        return $this->hasMany(CustomerDebtLedger::class)->latest();
    }
}