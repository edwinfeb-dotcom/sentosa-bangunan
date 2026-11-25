<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesTarget extends Model
{
    protected $fillable = ['month', 'year', 'target_amount', 'notes'];

    protected $casts = [
        'target_amount' => 'decimal:2',
    ];
}
