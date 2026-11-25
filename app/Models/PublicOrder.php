<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicOrder extends Model
{
    protected $fillable = [
        'user_id', 'order_code', 'customer_name', 'phone',
        'shipping_method', 'address', 'shipping_cost',
        'payment_method', 'notes', 'total_amount', 'status',
    ];

    protected $casts = [
        'shipping_cost' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(PublicOrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu Konfirmasi',
            'packed' => 'Sedang Dikemas',
            'shipped' => 'Sedang Dikirim',
            'completed', 'processed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            default => ucfirst($this->status),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'bg-warning bg-opacity-10 text-warning',
            'packed' => 'bg-info bg-opacity-10 text-info',
            'shipped' => 'bg-primary bg-opacity-10 text-primary',
            'completed', 'processed' => 'bg-success bg-opacity-10 text-success',
            'cancelled' => 'bg-danger bg-opacity-10 text-danger',
            default => 'bg-secondary bg-opacity-10 text-secondary',
        };
    }
}
