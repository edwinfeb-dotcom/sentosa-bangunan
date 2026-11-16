<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class EmployeeDebt extends Model
{
    protected $fillable = ['user_id', 'sale_id', 'amount', 'status', 'notes'];
    public function user() { return $this->belongsTo(User::class); }
}