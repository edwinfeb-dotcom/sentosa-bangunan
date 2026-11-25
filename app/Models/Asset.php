<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    protected $fillable = [
        'code', 'name', 'category', 'purchase_date',
        'purchase_price', 'useful_life_years', 'residual_value', 'condition', 'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_price' => 'decimal:2',
        'residual_value' => 'decimal:2',
    ];

    /**
     * Straight-line annual depreciation.
     */
    public function getAnnualDepreciationAttribute(): float
    {
        if ($this->useful_life_years <= 0) {
            return 0;
        }
        $depreciable = max(0, (float) $this->purchase_price - (float) $this->residual_value);
        return round($depreciable / $this->useful_life_years, 2);
    }

    public function getMonthlyDepreciationAttribute(): float
    {
        return round($this->annual_depreciation / 12, 2);
    }

    public function getMonthsElapsedAttribute(): int
    {
        if (!$this->purchase_date || $this->purchase_date->isFuture()) {
            return 0;
        }
        $months = $this->purchase_date->diffInMonths(Carbon::now('Asia/Pontianak'));
        $maxMonths = max(0, $this->useful_life_years * 12);
        return min($months, $maxMonths);
    }

    public function getAccumulatedDepreciationAttribute(): float
    {
        $maxDepreciable = max(0, (float) $this->purchase_price - (float) $this->residual_value);
        return min($maxDepreciable, round($this->monthly_depreciation * $this->months_elapsed, 2));
    }

    public function getBookValueAttribute(): float
    {
        return max((float) $this->residual_value, round((float) $this->purchase_price - $this->accumulated_depreciation, 2));
    }
}
