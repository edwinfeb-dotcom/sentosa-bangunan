<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class StoreSetting extends Model
{
    public const DEFAULT_ADDRESS = 'Jl. Raya Km 6, Desa Amboyo Inti, Kecamatan Ngabang, Kabupaten Landak, Kalimantan Barat 79357';

    protected $fillable = [
        'store_name',
        'store_address',
        'store_phone',
        'bank_transfer_note',
        'show_customer_accounts',
    ];

    protected $casts = [
        'show_customer_accounts' => 'boolean',
    ];

    public static function current(): self
    {
        if (!Schema::hasTable('store_settings')) {
            return new self([
                'store_name' => 'Sentosa Bangunan',
                'store_address' => self::DEFAULT_ADDRESS,
                'show_customer_accounts' => true,
            ]);
        }

        return self::first() ?? self::create([
            'store_name' => 'Sentosa Bangunan',
            'store_address' => self::DEFAULT_ADDRESS,
            'show_customer_accounts' => true,
        ]);
    }
}
