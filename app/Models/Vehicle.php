<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use SoftDeletes;

    protected $fillable = ['plate_number', 'name', 'type', 'is_active'];
    
    protected $casts = ['is_active' => 'boolean'];

    // TAMBAHKAN FUNGSI RELASI INI
    public function transportJobs()
    {
        return $this->hasMany(TransportJob::class);
    }
}