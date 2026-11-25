<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $fillable = ['name', 'type', 'account_number', 'initial_balance', 'is_active'];

    protected $casts = [
        'initial_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function cashLedgers()
    {
        return $this->hasMany(CashLedger::class);
    }

    public function getCurrentBalanceAttribute(): float
    {
        $in = (float) $this->cashLedgers()->where('type', 'in')->sum('amount');
        $out = (float) $this->cashLedgers()->where('type', 'out')->sum('amount');
        return (float) $this->initial_balance + $in - $out;
    }
}
