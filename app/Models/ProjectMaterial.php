<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectMaterial extends Model
{
    protected $fillable = ['project_id', 'product_id', 'user_id', 'qty', 'unit_price', 'subtotal', 'used_date', 'notes'];

    protected $casts = [
        'used_date' => 'date',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
