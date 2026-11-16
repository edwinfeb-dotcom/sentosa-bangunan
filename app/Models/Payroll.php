<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    protected $fillable = ['user_id', 'month', 'year', 'basic_salary', 'allowance', 'deduction', 'net_salary', 'status'];

    public function user() { return $this->belongsTo(User::class); }
}