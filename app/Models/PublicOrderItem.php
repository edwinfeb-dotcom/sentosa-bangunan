<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PublicOrderItem extends Model
{
    protected $fillable = ['public_order_id', 'product_id', 'qty', 'price_snapshot', 'subtotal'];
    public function product() { return $this->belongsTo(Product::class); }
}