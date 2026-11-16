<?php

namespace App\Exports;

use App\Models\DailyReport;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DailyReportsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return DailyReport::with('user')->latest()->get();
    }

    // Judul kolom di Excel
    public function headings(): array
    {
        return [
            'Tanggal',
            'Petugas',
            'Kode Verifikasi',
            'Status',
            'Piket',
            'Uang Kecil',
            'Uang Besar',
            'Transfer',
            'Total Kasir',
            'Barang Datang',
            'Cek Aset',
            'Absensi',
            'Ceklis Sore'
        ];
    }

    // Mapping data JSON ke kolom Excel
    public function map($report): array
    {
        $data = $report->report_data;
        
        return [
            $report->date->format('d/m/Y'),
            $report->user->name ?? '-',
            $report->verification_code,
            ucfirst($report->status),
            $data['piket'] ?? '-',
            $data['laporan_kasir']['uang_kecil'] ?? 0,
            $data['laporan_kasir']['uang_besar'] ?? 0,
            $data['laporan_kasir']['transfer'] ?? 0,
            $data['laporan_kasir']['total_kasir'] ?? 0,
            $data['barang_datang'] ?? '-',
            $data['cek_aset'] ?? '-',
            $data['absensi'] ?? '-',
            $data['ceklis_sore'] ?? '-',
        ];
    }
}