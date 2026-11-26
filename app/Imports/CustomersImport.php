<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\CustomerDebtLedger;
use App\Models\DepositLedger;
use App\Support\SimpleXlsxReader;

class CustomersImport
{
    public int $createdCount = 0;
    public int $updatedCount = 0;
    public int $skippedCount = 0;

    /**
     * Import data pelanggan beserta deposit & sisa bon dari file Excel Pak Nelson (`BON PELANGGAN`) atau template ringkas.
     */
    public function import(string $filePath, string $ext = 'xlsx'): void
    {
        $sheets = SimpleXlsxReader::readSpreadsheetRows($filePath, $ext, ['master']);
        if (empty($sheets)) {
            $sheets = SimpleXlsxReader::readSpreadsheetRows($filePath, $ext);
        }

        $authId = auth()->id() ?? 1;

        foreach ($sheets as $rows) {
            $colMap = null;

            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                if ($colMap === null) {
                    $detected = $this->detectHeader($row);
                    if ($detected !== null) {
                        $colMap = $detected;
                    }
                    continue;
                }

                $name = trim((string) ($row[$colMap['name']] ?? ''));
                if ($name === '' || strtoupper($name) === 'NAMA PELANGGAN' || strtoupper($name) === 'TOTAL') {
                    continue;
                }

                $address = $colMap['address'] !== null ? $this->cleanText($row[$colMap['address']] ?? null) : null;
                $phone = $colMap['phone'] !== null ? $this->cleanPhone($row[$colMap['phone']] ?? null) : null;
                $totalSpent = $colMap['total_spent'] !== null ? max(0, SimpleXlsxReader::parseNumber($row[$colMap['total_spent']] ?? 0)) : 0;
                $rawDeposit = $colMap['deposit'] !== null ? SimpleXlsxReader::parseNumber($row[$colMap['deposit']] ?? 0) : 0;
                $rawBon = $colMap['bon'] !== null ? SimpleXlsxReader::parseNumber($row[$colMap['bon']] ?? 0) : 0;

                // Pada Excel Pak Nelson, kadang minus pada deposit/sisa bon berarti hutang pelanggan
                $deposit = $rawDeposit > 0 ? $rawDeposit : 0.0;
                $debt = $rawBon > 0 ? $rawBon : ($rawBon < 0 ? abs($rawBon) : ($rawDeposit < 0 ? abs($rawDeposit) : 0.0));

                $type = 'reguler';
                if (str_contains(strtoupper($name), 'TOKO') || str_contains(strtoupper($name), 'TB ')) {
                    $type = 'toko';
                } elseif (str_contains(strtoupper($name), 'CV') || str_contains(strtoupper($name), 'PT') || str_contains(strtoupper($name), 'PROYEK') || str_contains(strtoupper($name), 'KONTRAKTOR')) {
                    $type = 'kontraktor';
                }

                $existing = Customer::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();

                if (!$existing && $phone) {
                    $existing = Customer::where('phone', $phone)->first();
                }

                if ($existing) {
                    $prevDeposit = (float) $existing->deposit_balance;
                    $prevDebt = (float) $existing->debt_balance;

                    $existing->update([
                        'address' => $address ?: $existing->address,
                        'phone' => $phone ?: $existing->phone,
                        'deposit_balance' => $colMap['deposit'] !== null ? $deposit : $prevDeposit,
                        'debt_balance' => $colMap['bon'] !== null ? $debt : $prevDebt,
                        'total_spent_history' => max((float) $existing->total_spent_history, $totalSpent),
                        'is_active' => true,
                    ]);

                    $this->recordLedgerDiff($existing, $authId, $prevDeposit, $deposit, $prevDebt, $debt, 'Sync Excel Pelanggan');
                    $this->updatedCount++;
                } else {
                    $customer = Customer::create([
                        'name' => $name,
                        'type' => $type,
                        'phone' => $phone,
                        'address' => $address,
                        'deposit_balance' => $deposit,
                        'debt_balance' => $debt,
                        'total_spent_history' => $totalSpent,
                        'points' => 0,
                        'is_active' => true,
                    ]);

                    $this->recordLedgerDiff($customer, $authId, 0, $deposit, 0, $debt, 'Saldo Awal Import Excel');
                    $this->createdCount++;
                }
            }
        }
    }

    private function recordLedgerDiff(Customer $customer, int $authId, float $oldDep, float $newDep, float $oldDebt, float $newDebt, string $desc): void
    {
        $depDiff = $newDep - $oldDep;
        if (abs($depDiff) >= 1) {
            DepositLedger::create([
                'customer_id' => $customer->id,
                'user_id' => $authId,
                'type' => $depDiff > 0 ? 'in' : 'out',
                'amount' => abs($depDiff),
                'description' => $desc,
            ]);
        }

        $debtDiff = $newDebt - $oldDebt;
        if (abs($debtDiff) >= 1) {
            CustomerDebtLedger::create([
                'customer_id' => $customer->id,
                'user_id' => $authId,
                'type' => $debtDiff > 0 ? 'in' : 'out',
                'amount' => abs($debtDiff),
                'description' => $desc,
            ]);
        }
    }

    private function detectHeader(array $row): ?array
    {
        $normalized = [];
        foreach ($row as $i => $v) {
            $normalized[$i] = strtoupper(trim(preg_replace('/\s+/', ' ', (string) $v)));
        }

        $nameCol = $this->findCol($normalized, ['NAMA PELANGGAN', 'NAMA_PELANGGAN', 'NAMA']);
        if ($nameCol === null) {
            return null;
        }

        return [
            'name' => $nameCol,
            'address' => $this->findCol($normalized, ['ALAMAT', 'ADDRESS']),
            'phone' => $this->findCol($normalized, ['NOMOR HP', 'NO HP', 'HP', 'TELEPON', 'NO WA', 'PHONE']),
            'total_spent' => $this->findCol($normalized, ['TOTAL BELANJA', 'TOTAL_BELANJA']),
            'deposit' => $this->findCol($normalized, ['DEPOSIT', 'SALDO DEPOSIT']),
            'bon' => $this->findCol($normalized, ['SISA BON', 'BON', 'PIUTANG', 'HUTANG']),
        ];
    }

    private function findCol(array $normalized, array $candidates): ?int
    {
        foreach ($candidates as $c) {
            foreach ($normalized as $i => $cell) {
                if ($cell === $c) {
                    return $i;
                }
            }
        }
        return null;
    }

    private function cleanText(mixed $val): ?string
    {
        $s = trim((string) ($val ?? ''));
        if ($s === '' || $s === '-' || str_starts_with($s, '#')) {
            return null;
        }
        return mb_substr($s, 0, 255);
    }

    private function cleanPhone(mixed $val): ?string
    {
        $s = trim((string) ($val ?? ''));
        if ($s === '' || $s === '-' || str_starts_with($s, '#')) {
            return null;
        }
        return mb_substr($s, 0, 20);
    }
}
