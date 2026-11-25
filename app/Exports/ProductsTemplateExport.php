<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductsTemplateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    /**
     * Baris petunjuk pengisian di atas header utama mengikuti template Owner.
     */
    public function headings(): array
    {
        return [
            'Wajib (01-16 atau Nama Kategori)',
            'Wajib (Nama Lengkap Barang)',
            'Wajib (Harga Modal Satuan Terbesar)',
            'Opsional (Nama Gudang)',
            'Opsional (Kode Rak)',
            'Opsional (Supplier Utama)',
            'Opsional (Supplier 2)',
            'Opsional (Stok Awal)',
            'Opsional (Min Stok)',
            'Opsional (Max Stok)',
            'Wajib (Satuan Terbesar)',
            'Isi 1',
            'Wajib (Kode Margin cth: K10 / K50)',
            'Opsional (Satuan 2 / Ecer)',
            'Opsional (Isi Satuan 2)',
            'Opsional (Kode Margin 2)',
            'Opsional (Satuan 3 Terkecil)',
            'Opsional (Isi Satuan 3)',
            'Opsional (Kode Margin 3)',
        ];
    }

    public function array(): array
    {
        return [
            [
                'ID Kategori Angka',
                'Nama Barang',
                'Harga Beli',
                'Nama Gudang',
                'Nama Rak',
                'Nama Supplier Utama',
                'Nama Supplier 2',
                'Stok Awal',
                'Batas Minimum Stok',
                'Target Maksimum Stok',
                'Satuan 1 Terbesar',
                'Rasio 1 (Harus 1)',
                'Kode Margin 1 (Cth: K5)',
                'Satuan 2 Sedang',
                'Rasio 2 Ke Satuan 3',
                'Kode Margin 2',
                'Satuan 3 Terkecil',
                'Rasio 3 Isi 1',
                'Kode Margin 3',
            ],
            [
                '01',
                'Semen Tiga Roda 50Kg',
                56000,
                'Gudang Utama',
                'A1',
                'PT Indocement',
                '',
                100,
                10,
                200,
                'SAK',
                1,
                'K20',
                '',
                '',
                '',
                '',
                '',
                '',
            ],
            [
                '10',
                'Kawat Loket 1/2"x180cm Hijau',
                240000,
                'Gudang Utama',
                'K4',
                'TB Sumber Baja',
                '',
                12,
                3,
                25,
                'ROLL',
                1,
                'K50',
                'METER',
                42,
                'K50',
                '',
                '',
                '',
            ],
        ];
    }
}
