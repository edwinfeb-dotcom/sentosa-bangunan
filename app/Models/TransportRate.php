<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportRate extends Model
{
    protected $fillable = [
        'origin',
        'destination',
        'rate_4_m3',
        'rate_6_m3',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'rate_4_m3' => 'decimal:2',
        'rate_6_m3' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
