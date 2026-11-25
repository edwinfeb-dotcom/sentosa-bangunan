<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MonthlyAttendanceExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function __construct(
        private int $month,
        private int $year,
        private array $summaryRows
    ) {
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Karyawan',
            'Jabatan',
            'Periode',
            'Hadir (Hari)',
            'Izin (Hari)',
            'Sakit (Hari)',
            'Bolos / Alpha (Hari)',
            'Total HOK',
            'Total Lembur (Jam)',
            'Scan QR (Kali)',
            'Gaji Full Bulanan (Rp)',
            'Potongan per Alpha (Rp)',
        ];
    }

    public function array(): array
    {
        $rows = [];
        $periode = sprintf('%02d/%d', $this->month, $this->year);

        foreach ($this->summaryRows as $idx => $item) {
            $user = $item['user'];
            $monthlySalary = (float) ($user->monthly_salary ?? 0);
            $penaltyPerDay = (float) ($user->absence_deduction_per_day ?? 0);
            if ($penaltyPerDay <= 0 && $monthlySalary > 0) {
                $penaltyPerDay = round($monthlySalary / 26);
            }

            $rows[] = [
                $idx + 1,
                $user->name,
                strtoupper($user->role),
                $periode,
                $item['hadir_days'],
                $item['izin_days'],
                $item['sakit_days'],
                $item['alpha_days'],
                round($item['total_hok'], 2),
                round($item['total_overtime_hours'], 2),
                $item['qr_count'],
                $monthlySalary,
                $penaltyPerDay,
            ];
        }

        return $rows;
    }
}
