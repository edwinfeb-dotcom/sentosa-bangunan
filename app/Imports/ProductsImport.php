<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Category;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Mencari ID kategori berdasarkan nama kategori di excel
        // Jika kategori tidak ada, otomatis dibuatkan baru
        $category = Category::firstOrCreate(
            ['name' => $row['kategori']],
            ['is_active' => true]
        );

        return new Product([
            'category_id'    => $category->id,
            'name'           => $row['nama_produk'],
            'barcode'        => $row['barcode'] ?? null,
            'unit'           => $row['satuan'] ?? 'Pcs',
            'purchase_price' => $row['harga_beli'] ?? 0,
            'selling_price'  => $row['harga_jual'] ?? 0,
            'stock_minimum'  => $row['minimal_stok'] ?? 0,
            'is_active'      => true,
        ]);
    }
}