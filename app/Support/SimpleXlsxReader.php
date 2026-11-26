<?php

namespace App\Support;

/**
 * Pembaca arsip .xlsx murni berbasis zlib (bekerja penuh meski ekstensi PHP ZipArchive tidak aktif).
 */
class SimpleXlsxReader
{
    /**
     * Ekstrak seluruh entri file di dalam arsip .xlsx / ZIP ke array [path => content].
     *
     * @return array<string, string>
     */
    public static function extractZipEntries(string $filePath): array
    {
        $data = @file_get_contents($filePath);
        if ($data === false || strlen($data) < 22) {
            return [];
        }

        $eocdPos = strrpos($data, "\x50\x4b\x05\x06");
        if ($eocdPos === false) {
            return [];
        }

        $cdSize = unpack('V', substr($data, $eocdPos + 12, 4))[1];
        $cdOffset = unpack('V', substr($data, $eocdPos + 16, 4))[1];

        $entries = [];
        $pos = $cdOffset;
        $end = $cdOffset + $cdSize;

        while ($pos < $end && substr($data, $pos, 4) === "\x50\x4b\x01\x02") {
            $method = unpack('v', substr($data, $pos + 10, 2))[1];
            $compSize = unpack('V', substr($data, $pos + 20, 4))[1];
            $nameLen = unpack('v', substr($data, $pos + 28, 2))[1];
            $extraLen = unpack('v', substr($data, $pos + 30, 2))[1];
            $commentLen = unpack('v', substr($data, $pos + 32, 2))[1];
            $localOffset = unpack('V', substr($data, $pos + 42, 4))[1];

            $filename = str_replace('\\', '/', substr($data, $pos + 46, $nameLen));

            if (substr($data, $localOffset, 4) === "\x50\x4b\x03\x04") {
                $lNameLen = unpack('v', substr($data, $localOffset + 26, 2))[1];
                $lExtraLen = unpack('v', substr($data, $localOffset + 28, 2))[1];
                $payloadStart = $localOffset + 30 + $lNameLen + $lExtraLen;
                $raw = substr($data, $payloadStart, $compSize);

                if ($method === 0) {
                    $entries[$filename] = $raw;
                } elseif ($method === 8) {
                    $inflated = @gzinflate($raw);
                    if ($inflated !== false) {
                        $entries[$filename] = $inflated;
                    }
                }
            }

            $pos += 46 + $nameLen + $extraLen + $commentLen;
        }

        return $entries;
    }

    /**
     * Baca sheet tertentu (atau seluruh sheet jika $onlySheetNames kosong) sebagai map [sheetName => numericRows].
     * Aman terhadap file .xlsx / .xlsm berukuran besar karena hanya mem-parse XML sheet yang dipilih.
     *
     * @param  array<int, string>  $onlySheetNames
     * @return array<string, array<int, array<int, mixed>>>
     */
    public static function readNamedSheetsAsNumericRows(string $filePath, array $onlySheetNames = []): array
    {
        $entries = self::extractZipEntries($filePath);
        if (empty($entries)) {
            return [];
        }

        $sharedStrings = self::parseSharedStrings($entries['xl/sharedStrings.xml'] ?? '');
        $namedPaths = self::resolveSheetPathsByName($entries);

        if (empty($namedPaths)) {
            $fallback = [];
            foreach (self::sortedSheetPaths($entries) as $i => $path) {
                $fallback['Sheet' . ($i + 1)] = $path;
            }
            $namedPaths = $fallback;
        }

        $wantedUpper = array_map(fn ($s) => strtoupper(trim($s)), $onlySheetNames);
        $result = [];

        foreach ($namedPaths as $sheetName => $sheetPath) {
            if (!empty($wantedUpper) && !in_array(strtoupper(trim($sheetName)), $wantedUpper, true)) {
                continue;
            }
            if (!isset($entries[$sheetPath])) {
                continue;
            }

            $assocRows = self::parseSheetXmlRows($entries[$sheetPath], $sharedStrings);
            $result[$sheetName] = self::assocRowsToNumericRows($assocRows);
        }

        return $result;
    }

    /**
     * Petakan nama sheet di xl/workbook.xml -> path xl/worksheets/sheetX.xml.
     *
     * @return array<string, string>
     */
    public static function resolveSheetPathsByName(array $entries): array
    {
        $workbookXml = $entries['xl/workbook.xml'] ?? '';
        $relsXml = $entries['xl/_rels/workbook.xml.rels'] ?? '';
        if ($workbookXml === '' || $relsXml === '') {
            return [];
        }

        $rels = self::parseRelsMap($relsXml);
        $wb = @simplexml_load_string($workbookXml);
        if (!$wb || !isset($wb->sheets->sheet)) {
            return [];
        }

        $map = [];
        foreach ($wb->sheets->sheet as $sheet) {
            $name = (string) ($sheet['name'] ?? '');
            $rAttrs = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $rId = (string) ($rAttrs['id'] ?? $sheet['r:id'] ?? '');
            if ($name !== '' && $rId !== '' && isset($rels[$rId])) {
                $map[$name] = $rels[$rId];
            }
        }

        return $map;
    }

    /**
     * @param  array<int, array<string, string>>  $assocRows
     * @return array<int, array<int, mixed>>
     */
    private static function assocRowsToNumericRows(array $assocRows): array
    {
        $numericRows = [];

        foreach ($assocRows as $cellsByLetter) {
            $maxCol = 0;
            $indexed = [];
            foreach ($cellsByLetter as $letter => $val) {
                $colIdx = self::columnLetterToIndex($letter);
                if ($colIdx > $maxCol) {
                    $maxCol = $colIdx;
                }
                $indexed[$colIdx] = $val;
            }

            $row = [];
            for ($c = 0; $c <= $maxCol; $c++) {
                $row[] = $indexed[$c] ?? null;
            }
            $numericRows[] = $row;
        }

        return $numericRows;
    }

    /**
     * Parse angka dari sel Excel (termasuk format ribuan titik/koma & abaikan formula error #NAME?, #REF!, dll).
     */
    public static function parseNumber(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $str = trim((string) $value);
        if ($str === '' || $str === '-' || str_starts_with($str, '#')) {
            return 0.0;
        }

        $str = str_ireplace(['Rp', 'IDR', ' '], '', $str);

        if (preg_match('/^-?\d{1,3}(\.\d{3})+(,\d{1,2})?$/', $str)) {
            $str = str_replace('.', '', $str);
            $str = str_replace(',', '.', $str);
            return (float) $str;
        }

        if (preg_match('/^-?\d{1,3}(,\d{3})+(\.\d{1,2})?$/', $str)) {
            $str = str_replace(',', '', $str);
            return (float) $str;
        }

        if (is_numeric($str)) {
            return (float) $str;
        }

        if (preg_match('/^-?\d+,\d{1,4}$/', $str)) {
            $str = str_replace(',', '.', $str);
            return (float) $str;
        }

        $cleaned = preg_replace('/[^0-9.-]/', '', str_replace('.', '', $str));
        return is_numeric($cleaned) ? (float) $cleaned : 0.0;
    }

    /**
     * Konversi serial tanggal Excel (cth: 45846) atau string tanggal menjadi format Y-m-d.
     */
    public static function parseExcelDate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $str = trim((string) $value);
        if ($str === '' || str_starts_with($str, '#')) {
            return null;
        }

        if (is_numeric($str)) {
            $num = (int) floor((float) $str);
            if ($num >= 20000 && $num <= 70000) {
                return \Carbon\Carbon::create(1899, 12, 30, 0, 0, 0, 'Asia/Pontianak')
                    ->addDays($num)
                    ->format('Y-m-d');
            }
            return null;
        }

        try {
            return \Carbon\Carbon::parse($str, 'Asia/Pontianak')->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Pembaca universal spreadsheet (.xlsx, .xlsm, .xls, .csv) mengembalikan [sheetName => numericRows].
     *
     * @param  array<int, string>  $onlySheetNames
     * @return array<string, array<int, array<int, mixed>>>
     */
    public static function readSpreadsheetRows(string $filePath, string $ext = 'xlsx', array $onlySheetNames = []): array
    {
        $ext = strtolower($ext);
        if (in_array($ext, ['xlsx', 'xlsm'], true)) {
            $sheets = self::readNamedSheetsAsNumericRows($filePath, $onlySheetNames);
            if (!empty($sheets) || !empty($onlySheetNames)) {
                return $sheets;
            }
        }

        try {
            $readerType = match ($ext) {
                'csv', 'txt' => \Maatwebsite\Excel\Excel::CSV,
                'xls' => \Maatwebsite\Excel\Excel::XLS,
                default => null,
            };
            $sheetsArray = \Maatwebsite\Excel\Facades\Excel::toArray(new \stdClass(), $filePath, null, $readerType);
            $out = [];
            foreach ($sheetsArray as $idx => $rows) {
                $out['Sheet' . ($idx + 1)] = $rows;
            }
            return $out;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Baca semua sheet menjadi daftar baris 0-indexed [sheetIdx => [rowIdx => [colIdx => val]]].
     *
     * @return array<int, array<int, array<int, mixed>>>
     */
    public static function readAllSheetsAsNumericRows(string $filePath): array
    {
        return array_values(self::readNamedSheetsAsNumericRows($filePath));
    }

    /**
     * @return array<int, string>
     */
    public static function sortedSheetPaths(array $entries): array
    {
        $sheetPaths = array_filter(array_keys($entries), fn ($p) => preg_match('#^xl/worksheets/sheet\d+\.xml$#i', $p));
        usort($sheetPaths, function (string $a, string $b) {
            preg_match('/(\d+)\.xml$/i', $a, $ma);
            preg_match('/(\d+)\.xml$/i', $b, $mb);
            return ((int) ($ma[1] ?? 0)) <=> ((int) ($mb[1] ?? 0));
        });

        return array_values($sheetPaths);
    }

    /**
     * @return array<int, string>
     */
    public static function parseSharedStrings(string $xml): array
    {
        if ($xml === '') {
            return [];
        }

        $sx = @simplexml_load_string($xml);
        if (!$sx) {
            return [];
        }

        $strings = [];
        foreach ($sx->si as $si) {
            if (isset($si->t)) {
                $strings[] = (string) $si->t;
            } else {
                $text = '';
                foreach ($si->r as $run) {
                    $text .= (string) ($run->t ?? '');
                }
                $strings[] = $text;
            }
        }

        return $strings;
    }

    /**
     * @return array<int, array<string, string>>
     */
    public static function parseSheetXmlRows(string $xml, array $sharedStrings): array
    {
        $sx = @simplexml_load_string($xml);
        if (!$sx || !isset($sx->sheetData->row)) {
            return [];
        }

        $rows = [];
        foreach ($sx->sheetData->row as $rowNode) {
            $rowNum = (int) ($rowNode['r'] ?? 0);
            foreach ($rowNode->c as $cell) {
                $coord = (string) ($cell['r'] ?? '');
                $col = preg_replace('/[^A-Z]/i', '', $coord);
                $type = (string) ($cell['t'] ?? '');

                if ($type === 's') {
                    $idx = (int) ($cell->v ?? 0);
                    $value = $sharedStrings[$idx] ?? '';
                } elseif ($type === 'inlineStr') {
                    if (isset($cell->is->t)) {
                        $value = (string) $cell->is->t;
                    } else {
                        $text = '';
                        foreach ($cell->is->r ?? [] as $run) {
                            $text .= (string) ($run->t ?? '');
                        }
                        $value = $text;
                    }
                } else {
                    $value = (string) ($cell->v ?? '');
                }

                if ($rowNum > 0 && $col !== '') {
                    $rows[$rowNum][$col] = $value;
                }
            }
        }

        return $rows;
    }

    /**
     * @return array<string, string>
     */
    public static function parseRelsMap(string $xml): array
    {
        if ($xml === '') {
            return [];
        }

        $sx = @simplexml_load_string($xml);
        if (!$sx) {
            return [];
        }

        $map = [];
        foreach ($sx->Relationship as $rel) {
            $id = (string) ($rel['Id'] ?? '');
            $target = (string) ($rel['Target'] ?? '');
            if ($id !== '' && $target !== '') {
                $normalized = ltrim(str_replace(['../', './'], '', $target), '/');
                if (!str_starts_with($normalized, 'xl/')) {
                    $normalized = 'xl/' . $normalized;
                }
                $map[$id] = $normalized;
            }
        }

        return $map;
    }

    public static function columnLetterToIndex(string $letters): int
    {
        $letters = strtoupper(trim($letters));
        $idx = 0;
        for ($i = 0, $len = strlen($letters); $i < $len; $i++) {
            $idx = $idx * 26 + (ord($letters[$i]) - ord('A') + 1);
        }

        return max(0, $idx - 1);
    }
}
