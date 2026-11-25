<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChecklistTemplate extends Model
{
    protected $fillable = ['section', 'title', 'description', 'sort_order', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function sections(): array
    {
        return [
            'piket' => '1. Tugas Piket & Display',
            'cek_aset' => '2. Pemeriksaan Aset & Kendaraan',
            'ceklis_pagi' => '3. Ceklis Pembukaan (Pagi)',
            'ceklis_sore' => '4. Ceklis Penutupan (Sore)',
        ];
    }
}
