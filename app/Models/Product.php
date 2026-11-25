<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id', 'supplier_id', 'barcode', 'name', 'image', 'description', 'unit',
        'purchase_price', 'selling_price', 'stock_minimum', 'stock_maximum',
        'warehouse_name', 'rack_location', 'margin_code',
        'secondary_unit', 'secondary_ratio', 'secondary_margin_code', 'secondary_price',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'secondary_ratio' => 'decimal:2',
        'secondary_price' => 'decimal:2',
    ];

    protected $appends = ['current_stock'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function stockLedgers()
    {
        return $this->hasMany(StockLedger::class);
    }

    /**
     * Eagerly compute stock sums in a single query to avoid N+1 on catalog & POS.
     */
    public function scopeWithStock(Builder $query): Builder
    {
        return $query
            ->withSum(['stockLedgers as stock_in_sum' => fn ($q) => $q->where('type', 'in')], 'qty')
            ->withSum(['stockLedgers as stock_out_sum' => fn ($q) => $q->where('type', 'out')], 'qty');
    }

    public function getCurrentStockAttribute(): int
    {
        if (array_key_exists('stock_in_sum', $this->attributes) || array_key_exists('stock_out_sum', $this->attributes)) {
            $in = (int) ($this->attributes['stock_in_sum'] ?? 0);
            $out = (int) ($this->attributes['stock_out_sum'] ?? 0);
            return $in - $out;
        }

        $in = (int) $this->stockLedgers()->where('type', 'in')->sum('qty');
        $out = (int) $this->stockLedgers()->where('type', 'out')->sum('qty');
        return $in - $out;
    }
}
