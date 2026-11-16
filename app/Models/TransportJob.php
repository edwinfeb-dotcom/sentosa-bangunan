<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportJob extends Model
{
    protected $fillable = [
        'vehicle_id', 'driver_id', 'destination', 'departure_date', 
        'return_date', 'status', 'revenue', 'driver_commission', 
        'operational_cost', 'checklist', 'notes'
    ];

    protected $casts = [
        'departure_date' => 'datetime',
        'return_date' => 'datetime',
        'checklist' => 'array',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}