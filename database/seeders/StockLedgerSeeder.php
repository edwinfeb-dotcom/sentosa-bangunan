<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\StockLedger;
use App\Models\User;
use Illuminate\Database\Seeder;

class StockLedgerSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil produk pertama dan user admin
        $product = Product::first();
        $user = User::where('role', 'admin')->first() ?? User::first();

        if ($product) {
            // Simulasi Stok Masuk 100
            StockLedger::create([
                'product_id' => $product->id,
                'user_id' => $user->id,
                'type' => 'in',
                'qty' => 100,
                'stock_before' => 0,
                'stock_after' => 100,
                'description' => 'Stok Awal (Seed)',
            ]);

            // Simulasi Stok Keluar 15
            StockLedger::create([
                'product_id' => $product->id,
                'user_id' => $user->id,
                'type' => 'out',
                'qty' => 15,
                'stock_before' => 100,
                'stock_after' => 85,
                'description' => 'Penjualan (Seed)',
            ]);
        }
    }
}