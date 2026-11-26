<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesTarget extends Model
{
    protected $fillable = [
        'month',
        'year',
        'target_amount',
        'target_profit',
        'max_expense',
        'working_days',
        'focus_category_id',
        'notes',
        'strategy_plan',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'target_profit' => 'decimal:2',
        'max_expense' => 'decimal:2',
        'working_days' => 'integer',
    ];

    public function focusCategory()
    {
        return $this->belongsTo(Category::class, 'focus_category_id');
    }
}
