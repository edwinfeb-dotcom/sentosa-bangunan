<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $fillable = ['sale_id', 'product_id', 'qty', 'unit_label', 'price_snapshot', 'subtotal'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Hitung HPP akurat per item penjualan dengan memperhitungkan satuan utama vs eceran.
     */
    public function getHppTotalAttribute(): float
    {
        if (!$this->product) {
            return 0.0;
        }

        $unitCost = (float) $this->product->purchase_price;
        if (
            filled($this->unit_label)
            && filled($this->product->secondary_unit)
            && strcasecmp(trim($this->unit_label), trim($this->product->secondary_unit)) === 0
            && (float) $this->product->secondary_ratio > 0
        ) {
            $unitCost = $unitCost / (float) $this->product->secondary_ratio;
        }

        return $unitCost * (float) $this->qty;
    }

    /**
     * Ekspresi SQL untuk menghitung modal per unit item terjual (mendukung eceran).
     */
    public static function sqlUnitCostExpression(): string
    {
        return "CASE WHEN sale_items.unit_label IS NOT NULL AND products.secondary_unit IS NOT NULL AND LOWER(TRIM(sale_items.unit_label)) = LOWER(TRIM(products.secondary_unit)) AND products.secondary_ratio > 0 THEN products.purchase_price / products.secondary_ratio ELSE products.purchase_price END";
    }
}
