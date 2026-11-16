<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SupplierInvoice extends Model
{
    protected $fillable = ['supplier_id', 'purchase_order_id', 'invoice_number', 'total_amount', 'paid_amount', 'status', 'due_date'];
    protected $casts = ['due_date' => 'date'];

    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function purchaseOrder() { return $this->belongsTo(PurchaseOrder::class); }
    public function payments() { return $this->hasMany(SupplierPayment::class); }

    // Sisa Hutang
    public function getRemainingAmountAttribute() {
        return $this->total_amount - $this->paid_amount;
    }
}