<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashLedger extends Model
{
    protected $fillable = ['user_id', 'type', 'amount', 'description', 'reference_type', 'reference_id', 'date'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}