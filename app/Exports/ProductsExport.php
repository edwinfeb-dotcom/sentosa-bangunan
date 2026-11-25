<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function query()
    {
        return Product::with(['category', 'supplier'])->withStock()->orderBy('name');
    }

    public function headings(): array
    {
        return [
            'nama_produk',
            'kategori',
            'kode_kategori',
            'barcode',
            'satuan',
            'harga_beli',
            'kode_margin',
            'harga_jual',
            'minimal_stok',
            'maksimal_stok',
            'stok_awal',
            'gudang',
            'rak',
            'supplier',
            'satuan_ecer',
            'rasio_ecer',
            'kode_margin_ecer',
            'harga_ecer',
            'deskripsi',
        ];
    }

    public function map($product): array
    {
        return [
            $product->name,
            $product->category->name ?? 'Umum',
            $product->category->code ?? '',
            $product->barcode,
            $product->unit,
            (float) $product->purchase_price,
            $product->margin_code ?? '',
            (float) $product->selling_price,
            (int) $product->stock_minimum,
            (int) ($product->stock_maximum ?? 0),
            (int) $product->current_stock,
            $product->warehouse_name ?? '',
            $product->rack_location ?? '',
            $product->supplier->name ?? '',
            $product->secondary_unit ?? '',
            $product->secondary_ratio ? (float) $product->secondary_ratio : '',
            $product->secondary_margin_code ?? '',
            $product->secondary_price ? (float) $product->secondary_price : '',
            $product->description,
        ];
    }
}
