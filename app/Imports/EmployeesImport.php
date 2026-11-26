<?php

namespace App\Imports;

use App\Models\EmployeeDebt;
use App\Models\User;
use App\Support\SimpleXlsxReader;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployeesImport
{
    public int $createdCount = 0;
    public int $updatedCount = 0;
    public int $debtsImported = 0;

    /**
     * Import data karyawan & kasbon aktif dari file `.xlsm` `LAPORAN HARIAN SENTOSA` (sheet `MANAGEMENT`)
     * ataupun tabel Excel karyawan standar (`NAMA`, `ROLE`, `GAJI HARIAN`).
     */
    public function import(string $filePath, string $ext = 'xlsx'): void
    {
        $sheets = SimpleXlsxReader::readSpreadsheetRows($filePath, $ext, ['MANAGEMENT']);
        if (empty($sheets)) {
            $sheets = SimpleXlsxReader::readSpreadsheetRows($filePath, $ext);
        }

        foreach ($sheets as $sheetName => $rows) {
            if (strtoupper(trim($sheetName)) === 'MANAGEMENT' || $this->looksLikeManagementSheet($rows)) {
                $this->importFromManagementSheet($rows);
            } else {
                $this->importFromGenericSheet($rows);
            }
        }
    }

    private function looksLikeManagementSheet(array $rows): bool
    {
        $first = $rows[0] ?? [];
        $joined = strtoupper(implode(' ', array_map('strval', $first)));
        return str_contains($joined, 'ID KREDIT') || str_contains($joined, 'NAMA KARYAWAN ABSENSI') || str_contains($joined, 'DAFTAR KARYAWAN SLIP');
    }

    private function importFromManagementSheet(array $rows): void
    {
        $headers = array_map(fn ($v) => strtoupper(trim(preg_replace('/\s+/', ' ', (string) $v))), $rows[0] ?? []);
        $find = function (string $key) use ($headers): ?int {
            foreach ($headers as $i => $h) {
                if ($h === $key) return $i;
            }
            return null;
        };

        $slipListCol = $find('DAFTAR KARYAWAN SLIP');
        $empIdAbsCol = $find('ID KARYAWAN ABSENSI');
        $empNameAbsCol = $find('NAMA KARYAWAN ABSENSI');
        $dailyWageCol = $find('UPAH HARIAN DINAMIS');

        $empStatusIdCol = $find('ID KARYAWAN STATUS');
        $empStatusSrcCol = $find('SUMBER STATUS');

        $krdIdCol = $find('ID KREDIT');
        $krdEmpIdCol = $find('ID KARYAWAN');
        $krdNameCol = $find('NAMA KREDIT');
        $krdAmtCol = $find('NOMINAL AWAL');
        $krdStatusCol = $find('STATUS KREDIT');

        $bayarKrdIdCol = $find('ID KREDIT BAYAR');
        $bayarAmtCol = $find('NOMINAL BAYAR');

        $idToName = [];
        $wageSamples = [];
        $idToStatus = [];
        $slipNames = [];
        $credits = [];
        $paymentsByCreditId = [];

        foreach (array_slice($rows, 1) as $row) {
            if ($slipListCol !== null) {
                $sn = trim((string) ($row[$slipListCol] ?? ''));
                if ($sn !== '' && !str_starts_with($sn, '#')) {
                    $slipNames[strtoupper($sn)] = $sn;
                }
            }

            if ($empIdAbsCol !== null && $empNameAbsCol !== null) {
                $eid = strtoupper(trim((string) ($row[$empIdAbsCol] ?? '')));
                $ename = trim((string) ($row[$empNameAbsCol] ?? ''));
                if ($eid !== '' && $ename !== '' && !str_starts_with($ename, '#')) {
                    $idToName[$eid] = $ename;
                    if ($dailyWageCol !== null) {
                        $wage = SimpleXlsxReader::parseNumber($row[$dailyWageCol] ?? 0);
                        if ($wage >= 20000) {
                            $wageSamples[strtoupper($ename)] = $wage;
                        }
                    }
                }
            }

            if ($empStatusIdCol !== null && $empStatusSrcCol !== null) {
                $sid = strtoupper(trim((string) ($row[$empStatusIdCol] ?? '')));
                $ssrc = strtoupper(trim((string) ($row[$empStatusSrcCol] ?? '')));
                if ($sid !== '' && $ssrc !== '') {
                    $idToStatus[$sid] = str_contains($ssrc, 'KELUAR') ? 'nonaktif' : 'aktif';
                }
            }

            if ($krdIdCol !== null && $krdEmpIdCol !== null) {
                $kid = trim((string) ($row[$krdIdCol] ?? ''));
                $keid = strtoupper(trim((string) ($row[$krdEmpIdCol] ?? '')));
                $kamt = $krdAmtCol !== null ? SimpleXlsxReader::parseNumber($row[$krdAmtCol] ?? 0) : 0;
                $kstatus = strtoupper(trim((string) ($row[$krdStatusCol] ?? '')));
                $kname = trim((string) ($row[$krdNameCol] ?? 'Kasbon'));

                if ($kid !== '' && $keid !== '' && $kamt > 0 && $kstatus === 'AKTIF') {
                    $credits[$kid] = [
                        'emp_id' => $keid,
                        'amount' => $kamt,
                        'note' => $kname,
                    ];
                }
            }

            if ($bayarKrdIdCol !== null && $bayarAmtCol !== null) {
                $bkid = trim((string) ($row[$bayarKrdIdCol] ?? ''));
                $bamt = SimpleXlsxReader::parseNumber($row[$bayarAmtCol] ?? 0);
                if ($bkid !== '' && $bamt > 0) {
                    $paymentsByCreditId[$bkid] = ($paymentsByCreditId[$bkid] ?? 0) + $bamt;
                }
            }
        }

        // Gabungkan semua nama karyawan unik
        $allEmployees = [];
        foreach ($idToName as $eid => $name) {
            $allEmployees[strtoupper($name)] = [
                'name' => $name,
                'status' => $idToStatus[$eid] ?? 'aktif',
                'daily_salary' => $wageSamples[strtoupper($name)] ?? 100000,
                'emp_id' => $eid,
            ];
        }
        foreach ($slipNames as $upper => $name) {
            if (!isset($allEmployees[$upper])) {
                $allEmployees[$upper] = [
                    'name' => $name,
                    'status' => 'aktif',
                    'daily_salary' => $wageSamples[$upper] ?? 100000,
                    'emp_id' => null,
                ];
            }
        }

        $empIdToUser = [];
        foreach ($allEmployees as $info) {
            $user = $this->upsertEmployee($info['name'], 'karyawan', $info['daily_salary'], $info['status'], null);
            if ($info['emp_id']) {
                $empIdToUser[$info['emp_id']] = $user;
            }
        }

        // Import kredit/kasbon aktif yang masih tersisa
        foreach ($credits as $kid => $credit) {
            $user = $empIdToUser[$credit['emp_id']] ?? null;
            if (!$user) {
                continue;
            }
            $paid = $paymentsByCreditId[$kid] ?? 0;
            $remaining = max(0, $credit['amount'] - $paid);
            if ($remaining <= 0) {
                continue;
            }
            $noteTag = "[{$kid}] {$credit['note']}";
            $already = EmployeeDebt::where('user_id', $user->id)
                ->where('notes', $noteTag)
                ->exists();
            if (!$already) {
                EmployeeDebt::create([
                    'user_id' => $user->id,
                    'amount' => $remaining,
                    'status' => 'unpaid',
                    'notes' => $noteTag,
                ]);
                $this->debtsImported++;
            }
        }
    }

    private function importFromGenericSheet(array $rows): void
    {
        $map = null;
        foreach ($rows as $row) {
            if (!is_array($row)) continue;

            if ($map === null) {
                $norm = array_map(fn ($v) => strtoupper(trim(preg_replace('/\s+/', ' ', (string) $v))), $row);
                $nameCol = null;
                foreach ($norm as $i => $c) {
                    if (in_array($c, ['NAMA', 'NAMA KARYAWAN', 'NAME'], true)) {
                        $nameCol = $i;
                        break;
                    }
                }
                if ($nameCol !== null) {
                    $map = [
                        'name' => $nameCol,
                        'role' => $this->findAny($norm, ['ROLE', 'JABATAN']),
                        'salary' => $this->findAny($norm, ['GAJI HARIAN', 'UPAH HARIAN', 'DAILY_SALARY']),
                        'phone' => $this->findAny($norm, ['NO HP', 'HP', 'PHONE', 'TELEPON']),
                        'status' => $this->findAny($norm, ['STATUS']),
                    ];
                }
                continue;
            }

            $name = trim((string) ($row[$map['name']] ?? ''));
            if ($name === '') continue;

            $roleRaw = strtolower(trim((string) ($row[$map['role'] ?? -1] ?? 'karyawan')));
            $role = in_array($roleRaw, ['admin', 'kasir', 'gudang', 'driver', 'karyawan'], true) ? $roleRaw : 'karyawan';
            $salary = $map['salary'] !== null ? max(0, SimpleXlsxReader::parseNumber($row[$map['salary']] ?? 100000)) : 100000;
            $phone = $map['phone'] !== null ? trim((string) ($row[$map['phone']] ?? '')) : null;
            $statusRaw = strtolower(trim((string) ($row[$map['status'] ?? -1] ?? 'aktif')));
            $status = in_array($statusRaw, ['aktif', 'training', 'nonaktif'], true) ? $statusRaw : 'aktif';

            $this->upsertEmployee($name, $role, $salary ?: 100000, $status, $phone ?: null);
        }
    }

    private function upsertEmployee(string $name, string $role, float $dailySalary, string $status, ?string $phone): User
    {
        $existing = User::whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])->first();
        if ($existing) {
            if ($dailySalary > 0 && (float) $existing->daily_salary <= 0) {
                $existing->daily_salary = $dailySalary;
            }
            if ($phone && !$existing->phone) {
                $existing->phone = $phone;
            }
            $existing->employee_status = $status;
            $existing->save();
            $this->updatedCount++;
            return $existing;
        }

        $slug = Str::slug($name, '.') ?: 'staff';
        $email = $slug . '@sentosa.local';
        $suffix = 1;
        while (User::where('email', $email)->exists()) {
            $email = $slug . $suffix . '@sentosa.local';
            $suffix++;
        }

        $user = User::create([
            'name' => trim($name),
            'email' => $email,
            'password' => Hash::make('password123'),
            'role' => $role,
            'daily_salary' => $dailySalary,
            'phone' => $phone,
            'join_date' => date('Y-m-d'),
            'employee_status' => $status,
            'employee_category' => 'karyawan tetap',
        ]);
        $this->createdCount++;
        return $user;
    }

    private function findAny(array $norm, array $keys): ?int
    {
        foreach ($keys as $k) {
            foreach ($norm as $i => $cell) {
                if ($cell === $k) return $i;
            }
        }
        return null;
    }
}
