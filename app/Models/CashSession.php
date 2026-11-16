<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CashSession extends Model
{
    protected $fillable = ['user_id', 'start_time', 'end_time', 'opening_balance', 'expected_closing_balance', 'actual_closing_balance', 'difference', 'status'];
    protected $casts = ['start_time' => 'datetime', 'end_time' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }
    public function sales() { return $this->hasMany(Sale::class); }
}