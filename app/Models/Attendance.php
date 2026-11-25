<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'user_id', 'status', 'date', 'clock_in', 'clock_out',
        'check_in_method', 'hok', 'overtime_hours', 'overtime_pay', 'notes',
    ];
    protected $casts = ['date' => 'date', 'hok' => 'decimal:2'];

    public function user() { return $this->belongsTo(User::class); }
}