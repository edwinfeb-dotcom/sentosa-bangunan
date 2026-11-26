<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use SoftDeletes;

    protected $fillable = ['plate_number', 'name', 'type', 'is_active', 'last_service_at', 'next_service_at', 'tax_due_at', 'service_notes'];
    
    protected $casts = [
        'is_active' => 'boolean',
        'last_service_at' => 'date',
        'next_service_at' => 'date',
        'tax_due_at' => 'date',
    ];

    // TAMBAHKAN FUNGSI RELASI INI
    public function transportJobs()
    {
        return $this->hasMany(TransportJob::class);
    }
}