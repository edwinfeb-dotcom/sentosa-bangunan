<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SupplierPayment extends Model
{
    protected $fillable = ['supplier_invoice_id', 'user_id', 'amount', 'payment_date', 'notes'];
    protected $casts = ['payment_date' => 'date'];

    public function invoice() { return $this->belongsTo(SupplierInvoice::class, 'supplier_invoice_id'); }
    public function user() { return $this->belongsTo(User::class); }
}