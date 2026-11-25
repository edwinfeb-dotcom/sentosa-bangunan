<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ProductImagesTemplateExport extends DefaultValueBinder implements FromArray, WithHeadings, WithEvents, WithCustomValueBinder
{
    public function headings(): array
    {
        return [
            'kode_barang',
            'nama_produk',
            'kategori',
            'satuan',
            'harga_beli',
            'harga_jual',
            'stok',
            'minimal_stok',
            'foto_produk (Insert Picture PNG / JPG di sini)',
        ];
    }

    public function bindValue(Cell $cell, mixed $value): bool
    {
        // Paksa kolom A (kode_barang) selalu bertipe Text agar barcode 13 digit tidak berubah menjadi 8,99E+12
        if ($cell->getColumn() === 'A' && (int) $cell->getRow() >= 2) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);
            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function array(): array
    {
        $products = Product::with('category:id,name')
            ->withStock()
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(500)
            ->get();

        if ($products->isEmpty()) {
            return [
                ['8991234560001', 'Semen Merah Putih 50Kg (Contoh)', 'MATERIAL DASAR', 'Sak', 52000, 60000, 50, 10, ''],
                ['8991234560002', 'Cat Tembok 5Kg (Contoh)', 'CAT DAN KERAMIK & FINISHING', 'Pail', 145000, 175000, 20, 5, ''],
            ];
        }

        return $products->map(function (Product $product) {
            return [
                (string) ($product->barcode ?: ('PRD-' . $product->id)),
                $product->name,
                $product->category->name ?? 'Umum',
                $product->unit ?? 'Pcs',
                (float) $product->purchase_price,
                (float) $product->selling_price,
                (int) $product->current_stock,
                (int) $product->stock_minimum,
                '',
            ];
        })->all();
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->getColumnDimension('A')->setWidth(24);
                $sheet->getColumnDimension('B')->setWidth(38);
                $sheet->getColumnDimension('C')->setWidth(24);
                $sheet->getColumnDimension('D')->setWidth(12);
                $sheet->getColumnDimension('E')->setWidth(15);
                $sheet->getColumnDimension('F')->setWidth(15);
                $sheet->getColumnDimension('G')->setWidth(12);
                $sheet->getColumnDimension('H')->setWidth(14);
                $sheet->getColumnDimension('I')->setWidth(44);

                $sheet->getStyle('A1:I1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1C2430'],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(28);

                $highestRow = $sheet->getHighestRow();
                for ($row = 2; $row <= $highestRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(72);
                }

                if ($highestRow >= 2) {
                    $sheet->getStyle("A2:A{$highestRow}")
                        ->getNumberFormat()
                        ->setFormatCode(NumberFormat::FORMAT_TEXT);

                    $sheet->getStyle("A2:I{$highestRow}")->applyFromArray([
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_CENTER,
                            'wrapText' => true,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => 'CBD5E1'],
                            ],
                        ],
                    ]);

                    // Tandai kolom foto (I) dengan warna latar lembut agar admin jelas menempatkan gambar
                    $sheet->getStyle("I2:I{$highestRow}")->getFill()->applyFromArray([
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F0F9FF'],
                    ]);
                }
            },
        ];
    }
}
