<?php

namespace App\Imports;

use App\Models\Product;
use App\Support\SimpleXlsxReader;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;

class ProductImagesImport
{
    public int $updatedCount = 0;
    public int $notFoundCount = 0;
    public int $skippedCount = 0;

    /** @var array<string, Product> */
    private array $productsByBarcode = [];

    /** @var array<string, Product> */
    private array $productsByCodeAlias = [];

    /** @var array<string, Product> */
    private array $productsByName = [];

    public function import(string $filePath): void
    {
        $this->bootstrapProductLookup();

        if (class_exists('ZipArchive')) {
            $this->importViaPhpSpreadsheet($filePath);
            return;
        }

        $this->importViaPureZipFallback($filePath);
    }

    /**
     * Upload langsung banyak file foto sekaligus dengan pencocokan nama file ke kode_barang / nama_produk.
     *
     * @param array<int, \Illuminate\Http\UploadedFile> $uploadedFiles
     */
    public function importBatchFiles(array $uploadedFiles): void
    {
        $this->bootstrapProductLookup();

        foreach ($uploadedFiles as $file) {
            if (!$file || !$file->isValid()) {
                $this->skippedCount++;
                continue;
            }

            $baseName = trim((string) pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $product = $this->findMatchingProduct($baseName, $baseName);
            if (!$product) {
                $this->notFoundCount++;
                continue;
            }

            $bytes = @file_get_contents($file->getRealPath());
            if ($bytes === false || $bytes === '') {
                $this->skippedCount++;
                continue;
            }

            $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
            if (!$this->saveProductImage($product, $bytes, $ext)) {
                $this->skippedCount++;
            }
        }
    }

    private function bootstrapProductLookup(): void
    {
        foreach (Product::all() as $product) {
            if ($product->barcode) {
                $this->productsByBarcode[trim($product->barcode)] = $product;
            }
            $this->productsByCodeAlias['PRD-' . $product->id] = $product;
            $this->productsByCodeAlias[(string) $product->id] = $product;
            $this->productsByName[mb_strtolower(trim($product->name))] = $product;
        }
    }

    private function importViaPhpSpreadsheet(string $filePath): void
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $spreadsheet = $reader->load($filePath);

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $drawingsByRow = [];

            foreach ($sheet->getDrawingCollection() as $drawing) {
                $rowNum = $this->extractRowFromCoordinate($drawing->getCoordinates());
                if ($rowNum <= 0) {
                    continue;
                }

                $imageData = $this->readDrawingBinary($drawing);
                if ($imageData !== null) {
                    $drawingsByRow[$rowNum] = $imageData;
                }
            }

            if (empty($drawingsByRow)) {
                // Dukungan Excel 365 mode "Place in Cell" (xl/richData & xl/metadata.xml)
                $entries = SimpleXlsxReader::extractZipEntries($filePath);
                if (!empty($entries)) {
                    $sheetPaths = SimpleXlsxReader::sortedSheetPaths($entries);
                    $richImages = $this->extractPlaceInCellImagesFromEntries($entries, $sheetPaths);
                    $drawingsByRow = $richImages[0] ?? [];
                }
            }

            if (empty($drawingsByRow)) {
                continue;
            }

            $highestRow = max($sheet->getHighestRow(), max(array_keys($drawingsByRow)));
            $rows = $sheet->rangeToArray("A1:F{$highestRow}", null, false, false, true);
            $colMap = $this->detectColumnLetters($rows);

            foreach ($drawingsByRow as $rowNum => $imageData) {
                $rowCells = $rows[$rowNum] ?? [];
                $this->applyImageToRow($rowCells, $colMap, $imageData['bytes'], $imageData['ext']);
            }
        }
    }

    /**
     * Fallback pembaca .xlsx berbasis zlib (tidak membutuhkan ekstensi PHP ZipArchive).
     */
    private function importViaPureZipFallback(string $filePath): void
    {
        $entries = SimpleXlsxReader::extractZipEntries($filePath);
        if (empty($entries)) {
            throw new \RuntimeException('File Excel (.xlsx) tidak dapat dibaca. Pastikan format file valid (.xlsx).');
        }

        $sharedStrings = SimpleXlsxReader::parseSharedStrings($entries['xl/sharedStrings.xml'] ?? '');
        $sheetPaths = SimpleXlsxReader::sortedSheetPaths($entries);

        // Ambil gambar dari rels drawing serta fallback Place-in-Cell (Excel 365 richData)
        $drawingsByRow = $this->extractAllDrawingsFromEntries($entries);
        $placeInCellBySheet = $this->extractPlaceInCellImagesFromEntries($entries, $sheetPaths);

        foreach ($sheetPaths as $idx => $sheetPath) {
            $sheetRows = SimpleXlsxReader::parseSheetXmlRows($entries[$sheetPath], $sharedStrings);
            if (empty($sheetRows)) {
                continue;
            }

            $colMap = $this->detectColumnLetters($sheetRows);
            $rowImages = !empty($drawingsByRow[$idx])
                ? $drawingsByRow[$idx]
                : ($placeInCellBySheet[$idx] ?? ($drawingsByRow[0] ?? []));

            foreach ($rowImages as $rowNum => $imageData) {
                $rowCells = $sheetRows[$rowNum] ?? [];
                $this->applyImageToRow($rowCells, $colMap, $imageData['bytes'], $imageData['ext']);
            }
        }
    }

    private function applyImageToRow(array $rowCells, array $colMap, string $bytes, string $ext): void
    {
        $codeVal = trim((string) ($rowCells[$colMap['code']] ?? ''));
        $nameVal = $colMap['name'] ? trim((string) ($rowCells[$colMap['name']] ?? '')) : '';

        if ($codeVal === '' && $nameVal === '') {
            $this->skippedCount++;
            return;
        }

        if (in_array(strtolower($codeVal), ['kode_barang', 'barcode', 'kode', 'id_barang'], true)) {
            return;
        }

        $product = $this->findMatchingProduct($codeVal, $nameVal);
        if (!$product) {
            $this->notFoundCount++;
            return;
        }

        if (!$this->saveProductImage($product, $bytes, $ext)) {
            $this->skippedCount++;
        }
    }

    private function saveProductImage(Product $product, string $bytes, string $ext): bool
    {
        $safeExt = $this->normalizeImageExtension($ext, $bytes);
        if ($safeExt === null) {
            return false;
        }

        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }

        $filename = 'products/img_' . $product->id . '_' . Str::lower(Str::random(8)) . '.' . $safeExt;
        Storage::disk('public')->put($filename, $bytes);

        $product->update(['image' => $filename]);
        $this->updatedCount++;

        return true;
    }

    private function findMatchingProduct(string $code, string $name): ?Product
    {
        if ($code !== '') {
            if (isset($this->productsByBarcode[$code])) {
                return $this->productsByBarcode[$code];
            }
            $upperCode = strtoupper($code);
            if (isset($this->productsByCodeAlias[$upperCode])) {
                return $this->productsByCodeAlias[$upperCode];
            }
            if (isset($this->productsByName[mb_strtolower($code)])) {
                return $this->productsByName[mb_strtolower($code)];
            }
        }

        if ($name !== '' && isset($this->productsByName[mb_strtolower($name)])) {
            return $this->productsByName[mb_strtolower($name)];
        }

        return null;
    }

    private function detectColumnLetters(array $rows): array
    {
        $codeCol = 'A';
        $nameCol = 'B';

        foreach (array_slice($rows, 0, 5, true) as $rowCells) {
            foreach ($rowCells as $col => $val) {
                $upper = strtoupper(trim((string) $val));
                if (in_array($upper, ['KODE_BARANG', 'KODE BARANG', 'BARCODE', 'ID ITEM / BARCODE', 'KODE'], true)) {
                    $codeCol = $col;
                }
                if (in_array($upper, ['NAMA_PRODUK', 'NAMA PRODUK', 'NAMA BARANG', 'ITEM'], true)) {
                    $nameCol = $col;
                }
            }
        }

        return ['code' => $codeCol, 'name' => $nameCol];
    }

    private function readDrawingBinary(mixed $drawing): ?array
    {
        if ($drawing instanceof MemoryDrawing) {
            $resource = $drawing->getImageResource();
            if (!$resource) {
                return null;
            }
            ob_start();
            $mime = $drawing->getMimeType();
            if ($mime === MemoryDrawing::MIMETYPE_JPEG) {
                imagejpeg($resource, null, 90);
                $ext = 'jpg';
            } else {
                imagepng($resource);
                $ext = 'png';
            }
            $bytes = ob_get_clean();
            return $bytes !== false ? ['bytes' => $bytes, 'ext' => $ext] : null;
        }

        if ($drawing instanceof Drawing && $drawing->getPath()) {
            $bytes = @file_get_contents($drawing->getPath());
            if ($bytes === false || $bytes === '') {
                return null;
            }
            return [
                'bytes' => $bytes,
                'ext' => strtolower($drawing->getExtension() ?: 'png'),
            ];
        }

        return null;
    }

    private function extractRowFromCoordinate(string $coordinate): int
    {
        $digits = preg_replace('/[^0-9]/', '', $coordinate);
        return (int) $digits;
    }

    private function normalizeImageExtension(string $ext, string $bytes): ?string
    {
        $allowed = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
        $cleanExt = strtolower(trim($ext));
        if ($cleanExt === 'jpeg') {
            $cleanExt = 'jpg';
        }
        if (in_array($cleanExt, $allowed, true)) {
            return $cleanExt;
        }

        $info = @getimagesizefromstring($bytes);
        if (!$info || empty($info['mime'])) {
            return null;
        }

        return match ($info['mime']) {
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => null,
        };
    }

    /**
     * @return array<int, array<int, array{bytes: string, ext: string}>>
     */
    private function extractAllDrawingsFromEntries(array $entries): array
    {
        $drawingPaths = array_filter(array_keys($entries), fn ($p) => preg_match('#^xl/drawings/drawing\d+\.xml$#i', $p));
        sort($drawingPaths);

        $result = [];
        foreach ($drawingPaths as $sheetIdx => $drawingPath) {
            $relsPath = dirname($drawingPath) . '/_rels/' . basename($drawingPath) . '.rels';
            $relMap = SimpleXlsxReader::parseRelsMap($entries[$relsPath] ?? '');

            $dom = new \DOMDocument();
            if (!@$dom->loadXML($entries[$drawingPath])) {
                continue;
            }

            $nsXdr = 'http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing';
            $nsA = 'http://schemas.openxmlformats.org/drawingml/2006/main';
            $nsR = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

            foreach (['twoCellAnchor', 'oneCellAnchor'] as $anchorTag) {
                $anchors = $dom->getElementsByTagNameNS($nsXdr, $anchorTag);
                if ($anchors->length === 0) {
                    $anchors = $dom->getElementsByTagName($anchorTag);
                }

                foreach ($anchors as $anchor) {
                    $fromNodes = $anchor->getElementsByTagNameNS($nsXdr, 'from');
                    if ($fromNodes->length === 0) {
                        $fromNodes = $anchor->getElementsByTagName('from');
                    }
                    if ($fromNodes->length === 0) {
                        continue;
                    }

                    $rowNodes = $fromNodes->item(0)->getElementsByTagNameNS($nsXdr, 'row');
                    if ($rowNodes->length === 0) {
                        $rowNodes = $fromNodes->item(0)->getElementsByTagName('row');
                    }
                    if ($rowNodes->length === 0) {
                        continue;
                    }

                    $rowNum = ((int) trim($rowNodes->item(0)->textContent)) + 1;

                    $blips = $anchor->getElementsByTagNameNS($nsA, 'blip');
                    if ($blips->length === 0) {
                        $blips = $anchor->getElementsByTagName('blip');
                    }
                    if ($blips->length === 0) {
                        continue;
                    }

                    $blipNode = $blips->item(0);
                    $rId = $blipNode->getAttributeNS($nsR, 'embed') ?: $blipNode->getAttribute('r:embed');
                    if ($rId === '' || !isset($relMap[$rId])) {
                        continue;
                    }

                    $mediaPath = $relMap[$rId];
                    if (isset($entries[$mediaPath])) {
                        $result[$sheetIdx][$rowNum] = [
                            'bytes' => $entries[$mediaPath],
                            'ext' => strtolower(pathinfo($mediaPath, PATHINFO_EXTENSION) ?: 'png'),
                        ];
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Ekstrak gambar mode Excel 365 "Place in Cell" (metadata.xml + richData + media).
     *
     * @return array<int, array<int, array{bytes: string, ext: string}>>
     */
    private function extractPlaceInCellImagesFromEntries(array $entries, array $sheetPaths): array
    {
        $metaXml = $entries['xl/metadata.xml'] ?? '';
        $richValXml = $entries['xl/richData/rdrichvalue.xml'] ?? '';
        $richRelXml = $entries['xl/richData/richValueRel.xml'] ?? '';
        $richRelsXml = $entries['xl/richData/_rels/richValueRel.xml.rels'] ?? '';

        if ($metaXml === '' || $richValXml === '' || $richRelXml === '' || $richRelsXml === '') {
            return [];
        }

        $relMap = SimpleXlsxReader::parseRelsMap($richRelsXml);

        // Daftar rId berurutan di richValueRel.xml
        $relSx = @simplexml_load_string($richRelXml);
        if (!$relSx) {
            return [];
        }
        $nsR = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
        $mediaByRelIdx = [];
        $idx = 0;
        foreach ($relSx->rel as $relNode) {
            $rId = (string) ($relNode->attributes($nsR)->id ?? '');
            if ($rId !== '' && isset($relMap[$rId])) {
                $mediaByRelIdx[$idx] = $relMap[$rId];
            }
            $idx++;
        }

        // Petakan index richValue -> index rel media
        $rvSx = @simplexml_load_string($richValXml);
        if (!$rvSx) {
            return [];
        }
        $mediaByRvIdx = [];
        $rvIdx = 0;
        foreach ($rvSx->rv as $rv) {
            $firstVal = isset($rv->v[0]) ? (int) ((string) $rv->v[0]) : -1;
            if (isset($mediaByRelIdx[$firstVal])) {
                $mediaByRvIdx[$rvIdx] = $mediaByRelIdx[$firstVal];
            }
            $rvIdx++;
        }

        // Petakan 1-based vm attribute sel -> richValue index
        $metaSx = @simplexml_load_string($metaXml);
        if (!$metaSx || !isset($metaSx->valueMetadata->bk)) {
            return [];
        }
        $mediaByVm = [];
        $vm = 1;
        foreach ($metaSx->valueMetadata->bk as $bk) {
            $rvIndex = (int) ($bk->rc['v'] ?? -1);
            if (isset($mediaByRvIdx[$rvIndex])) {
                $mediaByVm[$vm] = $mediaByRvIdx[$rvIndex];
            }
            $vm++;
        }

        $result = [];
        foreach (array_values($sheetPaths) as $sheetIdx => $sheetPath) {
            $sx = @simplexml_load_string($entries[$sheetPath] ?? '');
            if (!$sx || !isset($sx->sheetData->row)) {
                continue;
            }
            foreach ($sx->sheetData->row as $rowNode) {
                $rowNum = (int) ($rowNode['r'] ?? 0);
                foreach ($rowNode->c as $cell) {
                    $cellVm = (int) ($cell['vm'] ?? 0);
                    if ($rowNum > 0 && $cellVm > 0 && isset($mediaByVm[$cellVm])) {
                        $mediaPath = $mediaByVm[$cellVm];
                        if (isset($entries[$mediaPath])) {
                            $result[$sheetIdx][$rowNum] = [
                                'bytes' => $entries[$mediaPath],
                                'ext' => strtolower(pathinfo($mediaPath, PATHINFO_EXTENSION) ?: 'png'),
                            ];
                        }
                    }
                }
            }
        }

        return $result;
    }
}
