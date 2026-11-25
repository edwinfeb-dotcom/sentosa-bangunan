<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;

    protected $fillable = ['customer_id', 'name', 'budget', 'start_date', 'end_date', 'status', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'start_date' => 'date', 'end_date' => 'date'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function materials()
    {
        return $this->hasMany(ProjectMaterial::class)->latest('used_date');
    }

    public function getTotalMaterialCostAttribute(): float
    {
        return (float) $this->materials()->sum('subtotal');
    }

    public function getBudgetUsagePercentAttribute(): float
    {
        if ($this->budget <= 0) {
            return 0;
        }
        return min(100, round(($this->total_material_cost / $this->budget) * 100, 1));
    }
}
