<?php

namespace App\Imports;

use App\Models\TransportJob;
use App\Models\TransportRate;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\SimpleXlsxReader;

class FleetImport
{
    public int $ratesSynced = 0;
    public int $vehiclesSynced = 0;
    public int $jobsCreated = 0;

    /**
     * Import tarif angkutan (sheet HARGA), armada kendaraan, dan opsional histori trip (DAM / STRADA) dari Excel `ANGKUTAN`.
     */
    public function import(string $filePath, bool $importTrips = false, string $ext = 'xlsx'): void
    {
        $targetSheets = $importTrips ? ['HARGA', 'DAM', 'STRADA ', 'STRADA', 'K1, K2, CARRY'] : ['HARGA', 'K1, K2, CARRY'];
        $sheets = SimpleXlsxReader::readSpreadsheetRows($filePath, $ext, $targetSheets);
        if (empty($sheets)) {
            $sheets = SimpleXlsxReader::readSpreadsheetRows($filePath, $ext);
        }

        // Pastikan armada default dari file Pak Nelson terdaftar jika sheet-nya ada
        $this->syncVehiclesFromSheets($sheets);

        foreach ($sheets as $sheetName => $rows) {
            $upperName = strtoupper(trim($sheetName));
            if ($upperName === 'HARGA' || $this->looksLikeRateSheet($rows)) {
                $this->importRates($rows);
            } elseif ($importTrips && (str_contains($upperName, 'DAM') || str_contains($upperName, 'STRADA'))) {
                $this->importTripRows($sheetName, $rows);
            }
        }
    }

    private function looksLikeRateSheet(array $rows): bool
    {
        foreach (array_slice($rows, 0, 10) as $row) {
            $joined = strtoupper(implode(' ', array_map('strval', $row)));
            if (str_contains($joined, 'ASAL') && str_contains($joined, 'TUJUAN')) {
                return true;
            }
        }
        return false;
    }

    private function importRates(array $rows): void
    {
        $originCol = null;
        $destCol = null;
        $rate4Col = null;
        $rate6Col = null;
        $notesCol = null;
        $lastOrigin = 'Toko Sentosa';

        foreach ($rows as $row) {
            $norm = array_map(fn ($v) => strtoupper(trim(preg_replace('/\s+/', ' ', (string) $v))), $row);

            if ($originCol === null) {
                foreach ($norm as $idx => $val) {
                    if ($val === 'ASAL') $originCol = $idx;
                    if ($val === 'TUJUAN') $destCol = $idx;
                    if (str_contains($val, '4 KUBIK')) $rate4Col = $idx;
                    if (str_contains($val, '6 KUBIK')) $rate6Col = $idx;
                    if ($val === 'KETERANGAN') $notesCol = $idx;
                }
                continue;
            }

            // Pada sheet HARGA Pak Nelson, baris sub-header punya "4 KUBIK" dan "6 KUBIK"
            if ($rate4Col === null || $rate6Col === null) {
                foreach ($norm as $idx => $val) {
                    if (str_contains($val, '4 KUBIK')) $rate4Col = $idx;
                    if (str_contains($val, '6 KUBIK')) $rate6Col = $idx;
                    if ($val === 'HARGA' && $rate4Col === null) $rate4Col = $idx;
                }
            }

            $origin = trim((string) ($row[$originCol] ?? ''));
            $dest = trim((string) ($row[$destCol ?? ($originCol + 1)] ?? ''));
            if ($origin !== '' && !str_starts_with(strtoupper($origin), 'ASAL')) {
                $lastOrigin = $origin;
            }
            if ($dest === '' || str_contains(strtoupper($dest), 'TUJUAN') || str_contains(strtoupper($dest), 'KUBIK')) {
                continue;
            }

            $r4 = $rate4Col !== null ? max(0, SimpleXlsxReader::parseNumber($row[$rate4Col] ?? 0)) : 0;
            $r6 = $rate6Col !== null ? max(0, SimpleXlsxReader::parseNumber($row[$rate6Col] ?? 0)) : 0;
            if ($r4 <= 0 && $r6 <= 0) {
                continue;
            }

            $notes = $notesCol !== null ? trim((string) ($row[$notesCol] ?? '')) : null;

            TransportRate::updateOrCreate(
                ['origin' => $lastOrigin, 'destination' => $dest],
                [
                    'rate_4_m3' => $r4,
                    'rate_6_m3' => $r6,
                    'notes' => $notes ?: null,
                    'is_active' => true,
                ]
            );
            $this->ratesSynced++;
        }
    }

    private function syncVehiclesFromSheets(array $sheets): void
    {
        $presets = [
            'DAM' => ['plate_number' => 'DAM-01', 'name' => 'Dump Truck (DAM)', 'type' => 'internal'],
            'STRADA' => ['plate_number' => 'STRADA-01', 'name' => 'Mobil Strada', 'type' => 'internal'],
        ];

        foreach ($sheets as $sheetName => $rows) {
            $upper = strtoupper(trim($sheetName));
            foreach ($presets as $key => $info) {
                if (str_contains($upper, $key)) {
                    $this->ensureVehicle($info['plate_number'], $info['name'], $info['type']);
                }
            }

            if (str_contains($upper, 'CARRY') || str_contains($upper, 'K1')) {
                $this->ensureVehicle('KIJANG-1', 'Kijang 1', 'internal');
                $this->ensureVehicle('KIJANG-2', 'Kijang 2', 'internal');
                $this->ensureVehicle('CARRY-01', 'Mobil Carry', 'internal');
            }
        }
    }

    private function ensureVehicle(string $plate, string $name, string $type): Vehicle
    {
        $existing = Vehicle::where('plate_number', $plate)->first();
        if ($existing) {
            return $existing;
        }

        $this->vehiclesSynced++;
        return Vehicle::create([
            'plate_number' => $plate,
            'name' => $name,
            'type' => $type,
            'is_active' => true,
        ]);
    }

    private function importTripRows(string $sheetName, array $rows): void
    {
        $upper = strtoupper(trim($sheetName));
        $vehicle = str_contains($upper, 'STRADA')
            ? $this->ensureVehicle('STRADA-01', 'Mobil Strada', 'internal')
            : $this->ensureVehicle('DAM-01', 'Dump Truck (DAM)', 'internal');

        $defaultDriverId = User::where('role', 'driver')->value('id') ?? (auth()->id() ?? 1);

        $tglCol = null;
        $kegCol = null;
        $revCol = null;
        $opsCol = null;
        $upahCol = null;

        foreach ($rows as $rowIdx => $row) {
            $norm = array_map(fn ($v) => strtoupper(trim(preg_replace('/\s+/', ' ', (string) $v))), $row);

            if ($rowIdx <= 6) {
                foreach ($norm as $i => $v) {
                    if ($v === 'TGL' && $tglCol === null) $tglCol = $i;
                    if ($v === 'KEGIATAN' && $kegCol === null) $kegCol = $i;
                    if ($v === 'MOBIL' && $revCol === null) $revCol = $i;
                    if ($v === 'OPERASIONAL' && $opsCol === null) $opsCol = $i;
                    if ($v === 'UPAH' && $upahCol === null) $upahCol = $i;
                }
            }

            if ($tglCol === null || $kegCol === null) {
                continue;
            }

            $kegiatan = trim((string) ($row[$kegCol] ?? ''));
            $tglStr = SimpleXlsxReader::parseExcelDate($row[$tglCol] ?? null);
            if ($tglStr === null || $kegiatan === '' || str_contains(strtoupper($kegiatan), 'AKUMULASI')) {
                continue;
            }

            $revenue = $revCol !== null ? max(0, SimpleXlsxReader::parseNumber($row[$revCol] ?? 0)) : 0;
            $ops = $opsCol !== null ? max(0, SimpleXlsxReader::parseNumber($row[$opsCol] ?? 0)) : 0;
            $upah = $upahCol !== null ? max(0, SimpleXlsxReader::parseNumber($row[$upahCol] ?? 0)) : ($revenue * 0.10);

            if ($revenue <= 0 && $ops <= 0 && $upah <= 0) {
                continue;
            }

            $exists = TransportJob::where('vehicle_id', $vehicle->id)
                ->where('destination', $kegiatan)
                ->whereDate('departure_date', $tglStr)
                ->exists();

            if (!$exists) {
                TransportJob::create([
                    'vehicle_id' => $vehicle->id,
                    'driver_id' => $defaultDriverId,
                    'destination' => mb_substr($kegiatan, 0, 255),
                    'departure_date' => $tglStr . ' 08:00:00',
                    'return_date' => $tglStr . ' 17:00:00',
                    'status' => 'completed',
                    'revenue' => $revenue,
                    'operational_cost' => $ops,
                    'driver_commission' => $upah,
                    'notes' => "Import Excel Sheet {$sheetName}",
                ]);
                $this->jobsCreated++;
            }
        }
    }
}
