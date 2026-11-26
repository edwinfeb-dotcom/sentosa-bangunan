<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\Project;
use App\Support\SimpleXlsxReader;

class ProjectsImport
{
    public int $createdCount = 0;
    public int $updatedCount = 0;
    public int $skippedCount = 0;

    /**
     * Import master proyek aktif dari file `MASTER PROJECT SENTOSA` (hanya membaca sheet `MASTER` agar hemat memori).
     */
    public function import(string $filePath, string $ext = 'xlsx'): void
    {
        $sheets = SimpleXlsxReader::readSpreadsheetRows($filePath, $ext, ['MASTER']);
        if (empty($sheets)) {
            $sheets = SimpleXlsxReader::readSpreadsheetRows($filePath, $ext);
        }

        foreach ($sheets as $rows) {
            $map = null;

            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                if ($map === null) {
                    $map = $this->detectHeader($row);
                    continue;
                }

                $name = trim((string) ($row[$map['name']] ?? ''));
                if ($name === '' || in_array(strtoupper($name), ['NAMA', 'TOTAL', 'SUBTOTAL', 'ALL MASTER', 'MASTER BON'], true)) {
                    continue;
                }

                $sumber = $map['source'] !== null ? trim((string) ($row[$map['source']] ?? '')) : '';
                $depo = $map['depo'] !== null ? max(0, SimpleXlsxReader::parseNumber($row[$map['depo']] ?? 0)) : 0;
                $debet = $map['debet'] !== null ? max(0, SimpleXlsxReader::parseNumber($row[$map['debet']] ?? 0)) : 0;
                $saldo = $map['saldo'] !== null ? SimpleXlsxReader::parseNumber($row[$map['saldo']] ?? 0) : 0;

                if ($depo <= 0 && $debet <= 0 && abs($saldo) < 1 && $sumber === '') {
                    $this->skippedCount++;
                    continue;
                }

                $customerId = null;
                $upperSource = strtoupper($sumber);
                if ($sumber !== '' && !in_array($upperSource, ['OWNER', 'INTERNAL', 'PRIBADI', 'TOKO', 'SENTOSA', '-'], true)) {
                    $customer = Customer::firstOrCreate(
                        ['name' => $sumber],
                        [
                            'type' => 'kontraktor',
                            'is_active' => true,
                        ]
                    );
                    $customerId = $customer->id;
                }

                $budget = max($depo, $debet);
                $status = ($saldo != 0 || $budget > 0) ? 'active' : 'completed';

                $existing = Project::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
                if ($existing) {
                    $existing->update([
                        'customer_id' => $customerId ?? $existing->customer_id,
                        'budget' => $budget > 0 ? $budget : $existing->budget,
                    ]);
                    $this->updatedCount++;
                } else {
                    Project::create([
                        'customer_id' => $customerId,
                        'name' => mb_substr($name, 0, 255),
                        'budget' => $budget,
                        'start_date' => date('Y-m-d'),
                        'status' => $status,
                        'is_active' => true,
                    ]);
                    $this->createdCount++;
                }
            }
        }
    }

    private function detectHeader(array $row): ?array
    {
        $norm = array_map(fn ($v) => strtoupper(trim(preg_replace('/\s+/', ' ', (string) $v))), $row);

        $nameCol = null;
        foreach ($norm as $i => $cell) {
            if (in_array($cell, ['NAMA', 'NAMA PROYEK', 'PROYEK'], true)) {
                $nameCol = $i;
                break;
            }
        }
        if ($nameCol === null) {
            return null;
        }

        $find = function (array $keys) use ($norm) {
            foreach ($keys as $k) {
                foreach ($norm as $i => $c) {
                    if ($c === $k) return $i;
                }
            }
            return null;
        };

        return [
            'name' => $nameCol,
            'source' => $find(['SUMBER', 'KONTRAKTOR']),
            'depo' => $find(['DEPO', 'PAGU', 'BUDGET', 'ANGGARAN']),
            'debet' => $find(['DEBET', 'REALISASI']),
            'saldo' => $find(['SALDO', 'SISA']),
        ];
    }
}
