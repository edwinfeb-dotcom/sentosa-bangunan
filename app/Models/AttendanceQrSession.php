<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceQrSession extends Model
{
    protected $fillable = [
        'date',
        'type',
        'label',
        'token',
        'created_by',
        'is_active',
        'expires_at',
    ];

    protected $casts = [
        'date' => 'date',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
