<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'customer_id', 'employee_discount',
        'employee_debt', 'daily_salary', 'monthly_salary', 'absence_deduction_per_day',
        'photo', 'birth_date', 'birth_place', 'origin', 'address', 'phone',
        'join_date', 'resign_date', 'employee_status', 'employee_category',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'employee_discount' => 'decimal:2',
        'employee_debt' => 'decimal:2',
        'monthly_salary' => 'decimal:2',
        'absence_deduction_per_day' => 'decimal:2',
        'birth_date' => 'date',
        'join_date' => 'date',
        'resign_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function publicOrders()
    {
        return $this->hasMany(PublicOrder::class);
    }

    public function isStaff(): bool
    {
        return $this->role !== 'pelanggan';
    }
}
