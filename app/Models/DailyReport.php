<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyReport extends Model
{
    protected $fillable = ['user_id', 'date', 'verification_code', 'status', 'report_data', 'verified_by', 'verified_at'];

    protected $casts = [
        'report_data' => 'array', // Otomatis convert JSON ke Array
        'verified_at' => 'datetime',
         'date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}