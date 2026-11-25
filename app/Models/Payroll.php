<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    protected $fillable = [
        'user_id', 'month', 'year', 'basic_salary', 'monthly_salary_base',
        'working_days', 'present_days', 'alpha_days', 'alpha_deduction', 'debt_deduction',
        'allowance', 'bonus_notes', 'deduction', 'net_salary', 'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
