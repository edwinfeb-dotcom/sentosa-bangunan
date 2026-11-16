<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'category_id', 'barcode', 'name', 'unit', 
        'purchase_price', 'selling_price', 'stock_minimum', 'is_active'
    ];
    protected $casts = [
        'is_active' => 'boolean',
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

     public function stockLedgers()
    {
        return $this->hasMany(StockLedger::class);
    }

    public function getCurrentStockAttribute()
    {
        $in = $this->stockLedgers()->where('type', 'in')->sum('qty');
        $out = $this->stockLedgers()->where('type', 'out')->sum('qty');
        return $in - $out;
    }
}