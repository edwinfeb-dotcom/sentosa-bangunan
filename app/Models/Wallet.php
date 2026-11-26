<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $fillable = ['name', 'type', 'account_number', 'account_holder', 'initial_balance', 'is_active', 'show_on_catalog'];

    protected $casts = [
        'initial_balance' => 'decimal:2',
        'is_active' => 'boolean',
        'show_on_catalog' => 'boolean',
    ];

    public static function activeCatalogBanks()
    {
        return self::where('is_active', true)
            ->where('show_on_catalog', true)
            ->whereIn('type', ['bank', 'ewallet'])
            ->whereNotNull('account_number')
            ->where('account_number', '!=', '')
            ->orderBy('type')
            ->orderBy('name')
            ->get();
    }

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
