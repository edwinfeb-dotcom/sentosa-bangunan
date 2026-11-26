<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockLedger;
use App\Models\Supplier;
use Maatwebsite\Excel\Concerns\ToArray;

class ProductsImport implements ToArray
{
    public int $createdCount = 0;
    public int $updatedCount = 0;
    public int $skippedCount = 0;
    public int $categoriesSynced = 0;
    public int $suppliersSynced = 0;

    /**
     * Daftar kategori standar dari sheet master Owner (Kode 01 - 16).
     */
    private const DEFAULT_OWNER_CATEGORIES = [
        '01' => 'MATERIAL DASAR',
        '02' => 'MATERIAL STRUKTUR',
        '03' => 'CAT DAN KERAMIK & FINISHING',
        '04' => 'SELANG, PEMIPAAN & ACC',
        '05' => 'SANITASI KAMAR MANDI & PLUMBING',
        '06' => 'ORNAMEN BANGUNAN',
        '07' => 'PAKU DAN BAUT',
        '08' => 'PERKAKAS TUKANG & BENGKEL',
        '09' => 'ACC PINTU JENDELA',
        '10' => 'KAWAT, JARING DAN PENGIKAT',
        '11' => 'PERTANIAN',
        '12' => 'KELISTRIKAN ACC',
        '13' => 'LEM, PEMBERSIH, KIMIA & LAIN-LAIN',
        '14' => 'BAHAN PENUNJANG PERTUKANGAN',
        '15' => 'SPAREPART',
        '16' => 'LAINYA',
    ];

    /** @var array<string, Category> */
    private array $categoriesByCode = [];

    /** @var array<string, Category> */
    private array $categoriesByName = [];

    /** @var array<string, Supplier> */
    private array $suppliersByName = [];

    /** @var array<string, Supplier> */
    private array $suppliersByCode = [];

    /** @var array<int, Product> */
    private array $productsById = [];

    /** @var array<string, Product> */
    private array $productsByBarcode = [];

    /** @var array<string, Product> */
    private array $productsByName = [];

    /** @var array<int, int> */
    private array $stockByProductId = [];

    private int $authUserId = 1;

    public function array(array $rows): void
    {
        $this->bootstrapLookupMaps();

        $mode = null; // 'categories' | 'suppliers' | 'owner_master' | 'owner_template' | 'concise'
        $colMap = [];

        foreach ($rows as $rawRow) {
            if (!is_array($rawRow)) {
                continue;
            }

            $detected = $this->detectSectionHeader($rawRow);
            if ($detected !== null) {
                $mode = $detected['mode'];
                $colMap = $detected['map'];
                continue;
            }

            if ($mode === null) {
                continue;
            }

            if ($this->isBlankRow($rawRow)) {
                continue;
            }

            match ($mode) {
                'categories' => $this->handleCategoryRow($rawRow, $colMap),
                'suppliers' => $this->handleSupplierRow($rawRow, $colMap),
                'owner_master' => $this->handleOwnerMasterRow($rawRow, $colMap),
                'owner_template' => $this->handleOwnerTemplateRow($rawRow, $colMap),
                'concise' => $this->handleConciseRow($rawRow, $colMap),
                default => null,
            };
        }
    }

    private function bootstrapLookupMaps(): void
    {
        $this->authUserId = auth()->id() ?? 1;

        foreach (Category::all() as $category) {
            if ($category->code) {
                $this->categoriesByCode[$this->normalizeCategoryCode($category->code)] = $category;
            }
            $this->categoriesByName[mb_strtolower(trim($category->name))] = $category;
        }

        foreach (Supplier::all() as $supplier) {
            $this->suppliersByName[mb_strtolower(trim($supplier->name))] = $supplier;
        }

        foreach (Product::all() as $product) {
            $this->productsById[$product->id] = $product;
            if ($product->barcode) {
                $this->productsByBarcode[trim($product->barcode)] = $product;
            }
            $this->productsByName[mb_strtolower(trim($product->name))] = $product;
        }

        $ledgerTotals = StockLedger::query()
            ->selectRaw("product_id, COALESCE(SUM(CASE WHEN type = 'in' THEN qty ELSE -qty END), 0) as qty_sum")
            ->groupBy('product_id')
            ->pluck('qty_sum', 'product_id');

        foreach ($ledgerTotals as $pid => $sum) {
            $this->stockByProductId[(int) $pid] = (int) $sum;
        }
    }

    /**
     * Deteksi baris header untuk setiap bagian sheet Owner atau template ringkas.
     */
    private function detectSectionHeader(array $row): ?array
    {
        $normalized = [];
        foreach ($row as $idx => $val) {
            $text = strtoupper(trim(preg_replace('/\s+/', ' ', (string) $val)));
            $normalized[$idx] = $text;
        }

        $nonEmpty = array_values(array_filter($normalized, fn ($v) => $v !== ''));
        if (count($nonEmpty) < 2) {
            return null;
        }

        // 1. Master Produk Owner harus dicek lebih dulu sebelum Kategori/Supplier karena baris header BARANG juga memuat kolom KODE dan SUPLAYER 1
        $ktgIdx = $this->findColumn($normalized, ['KTG']);
        $itemIdx = $this->findColumn($normalized, ['ITEM']);
        if ($ktgIdx !== null && $itemIdx !== null) {
            $modalIdx = $this->findColumn($normalized, ['MODAL', 'HARGA BELI']);
            $rumusCol = $this->findColumn($normalized, ['RUMUS', 'KODE MARGIN']);
            $kodeAfterModalCol = $this->findColumnAfter($normalized, ['KODE'], $modalIdx ?? -1);

            return [
                'mode' => 'owner_master',
                'map' => [
                    'category' => $ktgIdx,
                    'barcode' => $this->findColumn($normalized, ['ID ITEM / BARCODE', 'ID ITEM/BARCODE', 'BARCODE', 'ID ITEM']),
                    'name' => $itemIdx,
                    'unit' => $this->findColumn($normalized, ['SATUAN', 'UNIT']),
                    'purchase_price' => $modalIdx,
                    'margin_code' => $rumusCol ?? $kodeAfterModalCol,
                    'margin_fallback' => $kodeAfterModalCol,
                    'selling_price' => $this->findColumn($normalized, ['HARGA JUAL', 'HARGA']),
                    'ratio_2' => $this->findColumn($normalized, ['RASIO']),
                    'unit_2' => $this->findColumn($normalized, ['M ECER']),
                    'margin_2' => $this->findColumn($normalized, ['RUM 2']),
                    'price_2' => $this->findColumn($normalized, ['J ECER']),
                    'supplier' => $this->findColumn($normalized, ['SUPLAYER 1', 'SUPPLIER 1', 'SUPLAYER', 'SUPPLIER']),
                    'rack' => $this->findColumn($normalized, ['RAK', 'NAMA RAK']),
                    'max_stock' => $this->findColumn($normalized, ['MAX', 'MAKSIMAL STOK']),
                    'min_stock' => $this->findColumn($normalized, ['MIN', 'MINIMAL STOK']),
                    'stock' => $this->findColumn($normalized, ['STOK', 'STOK AWAL']),
                ],
            ];
        }

        // 2. Template 19-Kolom Owner: [ID Kategori Angka, Nama Barang, Harga Beli, ...]
        $idKatIdx = $this->findColumn($normalized, ['ID KATEGORI ANGKA', 'ID KATEGORI']);
        $namaBarangIdx = $this->findColumn($normalized, ['NAMA BARANG']);
        if ($idKatIdx !== null && $namaBarangIdx !== null) {
            return [
                'mode' => 'owner_template',
                'map' => [
                    'category' => $idKatIdx,
                    'name' => $namaBarangIdx,
                    'purchase_price' => $this->findColumn($normalized, ['HARGA BELI', 'MODAL']),
                    'warehouse' => $this->findColumn($normalized, ['NAMA GUDANG', 'GUDANG']),
                    'rack' => $this->findColumn($normalized, ['NAMA RAK', 'RAK']),
                    'supplier' => $this->findColumn($normalized, ['NAMA SUPPLIER UTAMA', 'SUPPLIER UTAMA', 'SUPPLIER']),
                    'stock' => $this->findColumn($normalized, ['STOK AWAL', 'STOK']),
                    'min_stock' => $this->findColumn($normalized, ['BATAS MINIMUM STOK', 'MINIMAL STOK', 'MIN']),
                    'max_stock' => $this->findColumn($normalized, ['TARGET MAKSIMUM STOK', 'MAKSIMUM STOK', 'MAX']),
                    'unit' => $this->findColumn($normalized, ['SATUAN 1 TERBESAR', 'SATUAN 1', 'SATUAN']),
                    'margin_code' => $this->findColumn($normalized, ['KODE MARGIN 1', 'KODE MARGIN 1 (CTH: K5)', 'KODE MARGIN']),
                    'unit_2' => $this->findColumn($normalized, ['SATUAN 2 SEDANG', 'SATUAN 2']),
                    'ratio_2' => $this->findColumn($normalized, ['RASIO 2 KE SATUAN 3', 'RASIO 2']),
                    'margin_2' => $this->findColumn($normalized, ['KODE MARGIN 2']),
                ],
            ];
        }

        // 3. Tabel Referensi Kategori Owner: [..., KODE, KATEGORI, ...]
        $kodeKatIdx = $this->findColumn($normalized, ['KODE']);
        $katIdx = $this->findColumn($normalized, ['KATEGORI']);
        if ($kodeKatIdx !== null && $katIdx !== null && $katIdx === $kodeKatIdx + 1) {
            return [
                'mode' => 'categories',
                'map' => ['code' => $kodeKatIdx, 'name' => $katIdx],
            ];
        }

        // 4. Tabel Referensi Supplier Owner: [..., KODE, SUPLAYER / DISTRIBUTOR, ...]
        $supIdx = $this->findColumn($normalized, ['SUPLAYER / DISTRIBUTOR', 'SUPLAYER', 'SUPPLIER / DISTRIBUTOR']);
        if ($kodeKatIdx !== null && $supIdx !== null && $supIdx > $kodeKatIdx) {
            return [
                'mode' => 'suppliers',
                'map' => [
                    'code' => $kodeKatIdx,
                    'name' => $supIdx,
                    'phone' => $this->findColumn($normalized, ['HP', 'TELP', 'NO HP', 'TELEPON']),
                    'address' => $this->findColumn($normalized, ['ALAMAT', 'ADDRESS']),
                ],
            ];
        }

        // 5. Template Ringkas ERP: [nama_produk, kategori, barcode, satuan, harga_beli, harga_jual, ...]
        $conciseNameIdx = $this->findColumn($normalized, ['NAMA_PRODUK', 'NAMA PRODUK']);
        if ($conciseNameIdx !== null) {
            return [
                'mode' => 'concise',
                'map' => [
                    'name' => $conciseNameIdx,
                    'category' => $this->findColumn($normalized, ['KATEGORI', 'CATEGORY']),
                    'category_code' => $this->findColumn($normalized, ['KODE_KATEGORI', 'KODE KATEGORI']),
                    'barcode' => $this->findColumn($normalized, ['BARCODE', 'KODE_BARCODE', 'KODE_BARANG', 'KODE BARANG']),
                    'unit' => $this->findColumn($normalized, ['SATUAN', 'UNIT']),
                    'purchase_price' => $this->findColumn($normalized, ['HARGA_BELI', 'HARGA BELI', 'HPP']),
                    'margin_code' => $this->findColumn($normalized, ['KODE_MARGIN', 'KODE MARGIN']),
                    'selling_price' => $this->findColumn($normalized, ['HARGA_JUAL', 'HARGA JUAL', 'HARGA']),
                    'min_stock' => $this->findColumn($normalized, ['MINIMAL_STOK', 'MINIMAL STOK', 'STOK_MINIMAL']),
                    'max_stock' => $this->findColumn($normalized, ['MAKSIMAL_STOK', 'MAKSIMAL STOK']),
                    'stock' => $this->findColumn($normalized, ['STOK_AWAL', 'STOK AWAL', 'STOK', 'STOK_SAAT_INI', 'STOK SAAT INI']),
                    'warehouse' => $this->findColumn($normalized, ['GUDANG', 'NAMA_GUDANG']),
                    'rack' => $this->findColumn($normalized, ['RAK', 'NAMA_RAK']),
                    'supplier' => $this->findColumn($normalized, ['SUPPLIER', 'NAMA_SUPPLIER']),
                    'unit_2' => $this->findColumn($normalized, ['SATUAN_ECER', 'SATUAN ECER']),
                    'ratio_2' => $this->findColumn($normalized, ['RASIO_ECER', 'RASIO ECER']),
                    'margin_2' => $this->findColumn($normalized, ['KODE_MARGIN_ECER', 'KODE MARGIN ECER']),
                    'price_2' => $this->findColumn($normalized, ['HARGA_ECER', 'HARGA ECER']),
                    'description' => $this->findColumn($normalized, ['DESKRIPSI', 'KETERANGAN']),
                ],
            ];
        }

        return null;
    }

    private function handleCategoryRow(array $row, array $map): void
    {
        $rawCode = trim((string) ($row[$map['code']] ?? ''));
        $rawName = trim((string) ($row[$map['name']] ?? ''));
        if ($rawName === '' || !preg_match('/^[0-9]{1,3}$/', $rawCode)) {
            return;
        }

        $code = $this->normalizeCategoryCode($rawCode);
        $this->resolveOrCreateCategory($code, $rawName, true);
    }

    private function handleSupplierRow(array $row, array $map): void
    {
        $code = trim((string) ($row[$map['code']] ?? ''));
        $name = trim((string) ($row[$map['name']] ?? ''));
        if ($name === '' || strtoupper($name) === 'SUPLAYER / DISTRIBUTOR') {
            return;
        }

        $phone = $map['phone'] !== null ? trim((string) ($row[$map['phone']] ?? '')) : null;
        $address = $map['address'] !== null ? trim((string) ($row[$map['address']] ?? '')) : null;

        $supplier = $this->resolveOrCreateSupplier($name, $phone, $address);
        if ($code !== '') {
            $this->suppliersByCode[strtoupper($code)] = $supplier;
        }
    }

    private function handleOwnerMasterRow(array $row, array $map): void
    {
        $name = trim((string) ($row[$map['name']] ?? ''));
        if ($name === '' || $this->isHeaderEcho($name, 'ITEM')) {
            $this->skippedCount++;
            return;
        }

        $rawKtg = trim((string) ($row[$map['category']] ?? ''));
        $category = $this->resolveCategoryFromCodeOrName($rawKtg);

        $barcode = $this->cleanNullableString($row[$map['barcode']] ?? null);
        $unit = $this->cleanUnit($row[$map['unit']] ?? null, 'Pcs');

        $purchasePrice = $this->parseNumber($map['purchase_price'] !== null ? ($row[$map['purchase_price']] ?? 0) : 0);
        $marginCode = $this->cleanMarginCode($map['margin_code'] !== null ? ($row[$map['margin_code']] ?? null) : null)
            ?? $this->cleanMarginCode(($map['margin_fallback'] ?? null) !== null ? ($row[$map['margin_fallback']] ?? null) : null);
        $sellingPrice = $this->parseNumber($map['selling_price'] !== null ? ($row[$map['selling_price']] ?? 0) : 0);
        if ($sellingPrice <= 0 && $purchasePrice > 0 && $marginCode !== null) {
            $sellingPrice = $this->calculatePriceFromMargin($purchasePrice, $marginCode);
        }

        $rack = $map['rack'] !== null ? $this->cleanNullableString($row[$map['rack']] ?? null) : null;
        $maxStock = $map['max_stock'] !== null ? max(0, (int) $this->parseNumber($row[$map['max_stock']] ?? 0)) : 0;
        $minStock = $map['min_stock'] !== null ? max(0, (int) $this->parseNumber($row[$map['min_stock']] ?? 0)) : 0;
        $rawStock = $map['stock'] !== null ? ($row[$map['stock']] ?? null) : null;

        $supplierId = null;
        if (($map['supplier'] ?? null) !== null) {
            $supplierName = trim((string) ($row[$map['supplier']] ?? ''));
            if ($supplierName !== '') {
                $supplierId = $this->resolveOrCreateSupplier($supplierName)->id;
            }
        }

        $secondaryUnit = ($map['unit_2'] ?? null) !== null ? $this->cleanNullableString($row[$map['unit_2']] ?? null) : null;
        $secondaryRatio = ($map['ratio_2'] ?? null) !== null ? $this->parseNumber($row[$map['ratio_2']] ?? 0) : 0;
        $secondaryMarginCode = ($map['margin_2'] ?? null) !== null ? $this->cleanMarginCode($row[$map['margin_2']] ?? null) : null;
        $secondaryPrice = ($map['price_2'] ?? null) !== null ? $this->parseNumber($row[$map['price_2']] ?? 0) : 0;
        if ($secondaryPrice <= 0 && $secondaryUnit !== null && $secondaryRatio > 0 && $purchasePrice > 0 && $secondaryMarginCode !== null) {
            $secondaryPrice = $this->calculatePriceFromMargin($purchasePrice / $secondaryRatio, $secondaryMarginCode);
        }

        $this->upsertProduct([
            'category_id' => $category->id,
            'supplier_id' => $supplierId,
            'barcode' => $barcode,
            'name' => $name,
            'unit' => $unit,
            'purchase_price' => $purchasePrice,
            'margin_code' => $marginCode,
            'selling_price' => $sellingPrice,
            'stock_minimum' => $minStock,
            'stock_maximum' => $maxStock,
            'rack_location' => $rack,
            'secondary_unit' => $secondaryUnit,
            'secondary_ratio' => $secondaryRatio > 0 ? $secondaryRatio : null,
            'secondary_margin_code' => $secondaryMarginCode,
            'secondary_price' => $secondaryPrice > 0 ? $secondaryPrice : null,
        ], $rawStock);
    }

    private function handleOwnerTemplateRow(array $row, array $map): void
    {
        $name = trim((string) ($row[$map['name']] ?? ''));
        if ($name === '' || str_starts_with(strtoupper($name), 'WAJIB') || strtoupper($name) === 'NAMA BARANG') {
            $this->skippedCount++;
            return;
        }

        $rawCategory = trim((string) ($row[$map['category']] ?? ''));
        $category = $this->resolveCategoryFromCodeOrName($rawCategory);

        $purchasePrice = $this->parseNumber($map['purchase_price'] !== null ? ($row[$map['purchase_price']] ?? 0) : 0);
        $warehouse = $map['warehouse'] !== null ? $this->cleanNullableString($row[$map['warehouse']] ?? null) : null;
        $rack = $map['rack'] !== null ? $this->cleanNullableString($row[$map['rack']] ?? null) : null;

        $supplierId = null;
        if ($map['supplier'] !== null) {
            $supplierName = trim((string) ($row[$map['supplier']] ?? ''));
            if ($supplierName !== '' && !str_starts_with(strtoupper($supplierName), 'OPSIONAL')) {
                $supplierId = $this->resolveOrCreateSupplier($supplierName)->id;
            }
        }

        $minStock = $map['min_stock'] !== null ? max(0, (int) $this->parseNumber($row[$map['min_stock']] ?? 0)) : 0;
        $maxStock = $map['max_stock'] !== null ? max(0, (int) $this->parseNumber($row[$map['max_stock']] ?? 0)) : 0;
        $unit = $this->cleanUnit($map['unit'] !== null ? ($row[$map['unit']] ?? null) : null, 'Pcs');
        $marginCode = $this->cleanMarginCode($map['margin_code'] !== null ? ($row[$map['margin_code']] ?? null) : null);

        $sellingPrice = $purchasePrice;
        if ($purchasePrice > 0 && $marginCode !== null) {
            $sellingPrice = $this->calculatePriceFromMargin($purchasePrice, $marginCode);
        }

        $secondaryUnit = $map['unit_2'] !== null ? $this->cleanNullableString($row[$map['unit_2']] ?? null) : null;
        $secondaryRatio = $map['ratio_2'] !== null ? $this->parseNumber($row[$map['ratio_2']] ?? 0) : 0;
        $secondaryMarginCode = $this->cleanMarginCode($map['margin_2'] !== null ? ($row[$map['margin_2']] ?? null) : null);
        $secondaryPrice = null;

        if ($secondaryUnit !== null && $secondaryRatio > 0 && $purchasePrice > 0) {
            $unitCost = $purchasePrice / $secondaryRatio;
            $secondaryPrice = $secondaryMarginCode !== null
                ? $this->calculatePriceFromMargin($unitCost, $secondaryMarginCode)
                : ceil($unitCost / 500) * 500;
        }

        $rawStock = $map['stock'] !== null ? ($row[$map['stock']] ?? null) : null;

        $this->upsertProduct([
            'category_id' => $category->id,
            'supplier_id' => $supplierId,
            'name' => $name,
            'unit' => $unit,
            'purchase_price' => $purchasePrice,
            'margin_code' => $marginCode,
            'selling_price' => $sellingPrice,
            'stock_minimum' => $minStock,
            'stock_maximum' => $maxStock,
            'warehouse_name' => $warehouse,
            'rack_location' => $rack,
            'secondary_unit' => $secondaryUnit,
            'secondary_ratio' => $secondaryRatio > 0 ? $secondaryRatio : null,
            'secondary_margin_code' => $secondaryMarginCode,
            'secondary_price' => $secondaryPrice,
        ], $rawStock);
    }

    private function handleConciseRow(array $row, array $map): void
    {
        $name = trim((string) ($row[$map['name']] ?? ''));
        if ($name === '') {
            $this->skippedCount++;
            return;
        }

        $catCode = $map['category_code'] !== null ? trim((string) ($row[$map['category_code']] ?? '')) : '';
        $catName = $map['category'] !== null ? trim((string) ($row[$map['category']] ?? '')) : '';
        $categoryId = null;
        if ($catCode !== '') {
            $categoryId = $this->resolveOrCreateCategory($this->normalizeCategoryCode($catCode), $catName ?: 'Umum')->id;
        } elseif ($catName !== '') {
            $categoryId = $this->resolveCategoryFromCodeOrName($catName)->id;
        }

        $barcode = $map['barcode'] !== null ? $this->cleanNullableString($row[$map['barcode']] ?? null) : null;
        $unit = $map['unit'] !== null ? $this->cleanNullableString($row[$map['unit']] ?? null) : null;

        $purchasePrice = $map['purchase_price'] !== null && trim((string) ($row[$map['purchase_price']] ?? '')) !== ''
            ? $this->parseNumber($row[$map['purchase_price']])
            : null;
        $marginCode = $this->cleanMarginCode($map['margin_code'] !== null ? ($row[$map['margin_code']] ?? null) : null)
            ?? $this->cleanMarginCode(($map['margin_fallback'] ?? null) !== null ? ($row[$map['margin_fallback']] ?? null) : null);
        $sellingPrice = $map['selling_price'] !== null && trim((string) ($row[$map['selling_price']] ?? '')) !== ''
            ? $this->parseNumber($row[$map['selling_price']])
            : null;
        if (($sellingPrice === null || $sellingPrice <= 0) && ($purchasePrice ?? 0) > 0 && $marginCode !== null) {
            $sellingPrice = $this->calculatePriceFromMargin((float) $purchasePrice, $marginCode);
        }

        $minStock = $map['min_stock'] !== null && trim((string) ($row[$map['min_stock']] ?? '')) !== ''
            ? max(0, (int) $this->parseNumber($row[$map['min_stock']]))
            : null;
        $maxStock = $map['max_stock'] !== null ? max(0, (int) $this->parseNumber($row[$map['max_stock']] ?? 0)) : 0;
        $warehouse = $map['warehouse'] !== null ? $this->cleanNullableString($row[$map['warehouse']] ?? null) : null;
        $rack = $map['rack'] !== null ? $this->cleanNullableString($row[$map['rack']] ?? null) : null;

        $supplierId = null;
        if ($map['supplier'] !== null) {
            $supplierName = trim((string) ($row[$map['supplier']] ?? ''));
            if ($supplierName !== '') {
                $supplierId = $this->resolveOrCreateSupplier($supplierName)->id;
            }
        }

        $secondaryUnit = $map['unit_2'] !== null ? $this->cleanNullableString($row[$map['unit_2']] ?? null) : null;
        $secondaryRatio = $map['ratio_2'] !== null ? $this->parseNumber($row[$map['ratio_2']] ?? 0) : 0;
        $secondaryMarginCode = $this->cleanMarginCode($map['margin_2'] !== null ? ($row[$map['margin_2']] ?? null) : null);
        $secondaryPrice = $map['price_2'] !== null ? $this->parseNumber($row[$map['price_2']] ?? 0) : 0;
        if ($secondaryPrice <= 0 && $secondaryUnit !== null && $secondaryRatio > 0 && ($purchasePrice ?? 0) > 0 && $secondaryMarginCode !== null) {
            $secondaryPrice = $this->calculatePriceFromMargin(((float) $purchasePrice) / $secondaryRatio, $secondaryMarginCode);
        }

        $description = $map['description'] !== null ? $this->cleanNullableString($row[$map['description']] ?? null) : null;
        $rawStock = $map['stock'] !== null ? ($row[$map['stock']] ?? null) : null;

        $this->upsertProduct([
            'category_id' => $categoryId,
            'supplier_id' => $supplierId,
            'barcode' => $barcode,
            'name' => $name,
            'unit' => $unit,
            'purchase_price' => $purchasePrice,
            'margin_code' => $marginCode,
            'selling_price' => $sellingPrice,
            'stock_minimum' => $minStock,
            'stock_maximum' => $maxStock,
            'warehouse_name' => $warehouse,
            'rack_location' => $rack,
            'secondary_unit' => $secondaryUnit,
            'secondary_ratio' => $secondaryRatio > 0 ? $secondaryRatio : null,
            'secondary_margin_code' => $secondaryMarginCode,
            'secondary_price' => $secondaryPrice > 0 ? $secondaryPrice : null,
            'description' => $description,
        ], $rawStock);
    }

    private function upsertProduct(array $attributes, mixed $rawStock): void
    {
        $barcode = $attributes['barcode'] ?? null;
        $name = $attributes['name'];
        $nameKey = mb_strtolower(trim($name));

        $product = null;
        if ($barcode !== null && preg_match('/^PRD-(\d+)$/i', $barcode, $m) && isset($this->productsById[(int) $m[1]])) {
            $product = $this->productsById[(int) $m[1]];
            $barcode = null; // Jangan simpan alias PRD-ID ke kolom barcode asli
        } elseif ($barcode !== null && isset($this->productsByBarcode[$barcode])) {
            $product = $this->productsByBarcode[$barcode];
        } elseif (isset($this->productsByName[$nameKey])) {
            $product = $this->productsByName[$nameKey];
        }

        if ($product) {
            $payload = array_filter([
                'category_id' => $attributes['category_id'] ?? $product->category_id,
                'supplier_id' => $attributes['supplier_id'] ?? $product->supplier_id,
                'barcode' => $barcode ?? $product->barcode,
                'name' => $name,
                'unit' => $attributes['unit'] ?? $product->unit,
                'purchase_price' => $attributes['purchase_price'] ?? $product->purchase_price,
                'selling_price' => $attributes['selling_price'] ?? $product->selling_price,
                'stock_minimum' => $attributes['stock_minimum'] ?? $product->stock_minimum,
                'stock_maximum' => $attributes['stock_maximum'] ?? $product->stock_maximum,
                'warehouse_name' => $attributes['warehouse_name'] ?? $product->warehouse_name,
                'rack_location' => $attributes['rack_location'] ?? $product->rack_location,
                'margin_code' => $attributes['margin_code'] ?? $product->margin_code,
                'secondary_unit' => $attributes['secondary_unit'] ?? $product->secondary_unit,
                'secondary_ratio' => $attributes['secondary_ratio'] ?? $product->secondary_ratio,
                'secondary_margin_code' => $attributes['secondary_margin_code'] ?? $product->secondary_margin_code,
                'secondary_price' => $attributes['secondary_price'] ?? $product->secondary_price,
                'description' => ($attributes['description'] ?? null) !== null ? $attributes['description'] : $product->description,
                'is_active' => true,
            ], fn ($v) => $v !== null || in_array($v, [0, 0.0], true));

            $product->update($payload);
            $this->updatedCount++;
        } else {
            if (($attributes['category_id'] ?? null) === null) {
                $attributes['category_id'] = $this->resolveCategoryFromCodeOrName('Umum')->id;
            }
            $product = Product::create(array_merge([
                'is_active' => true,
                'unit' => 'Pcs',
                'purchase_price' => 0,
                'selling_price' => 0,
                'stock_minimum' => 0,
                'stock_maximum' => 0,
            ], array_filter($attributes, fn ($v) => $v !== null), ['barcode' => $barcode]));
            $this->createdCount++;
            $this->productsById[$product->id] = $product;
            $this->stockByProductId[$product->id] = 0;
        }

        if ($product->barcode) {
            $this->productsByBarcode[$product->barcode] = $product;
        }
        $this->productsByName[$nameKey] = $product;

        if ($rawStock !== null && trim((string) $rawStock) !== '') {
            $targetStock = max(0, (int) $this->parseNumber($rawStock));
            $currentStock = $this->stockByProductId[$product->id] ?? 0;
            $diff = $targetStock - $currentStock;

            if ($diff !== 0) {
                StockLedger::create([
                    'product_id' => $product->id,
                    'user_id' => $this->authUserId,
                    'type' => $diff > 0 ? 'in' : 'out',
                    'qty' => abs($diff),
                    'stock_before' => $currentStock,
                    'stock_after' => $targetStock,
                    'reference_type' => 'excel_import',
                    'reference_id' => null,
                    'description' => 'Penyesuaian stok via Import Excel / CSV Owner',
                ]);
                $this->stockByProductId[$product->id] = $targetStock;
            }
        }
    }

    private function resolveCategoryFromCodeOrName(string $raw): Category
    {
        $cleaned = trim($raw);
        if ($cleaned === '') {
            $cleaned = 'Umum';
        }

        if (preg_match('/^[0-9]{1,3}$/', $cleaned)) {
            $code = $this->normalizeCategoryCode($cleaned);
            $defaultName = self::DEFAULT_OWNER_CATEGORIES[$code] ?? ('KATEGORI ' . $code);
            return $this->resolveOrCreateCategory($code, $defaultName);
        }

        return $this->resolveOrCreateCategory(null, $cleaned);
    }

    private function resolveOrCreateCategory(?string $code, string $name, bool $updateCodeOrName = false): Category
    {
        $nameKey = mb_strtolower(trim($name));

        if ($code !== null && isset($this->categoriesByCode[$code])) {
            $cat = $this->categoriesByCode[$code];
            if ($updateCodeOrName && $name !== '' && $cat->name !== $name) {
                $cat->update(['name' => $name]);
                $this->categoriesByName[mb_strtolower(trim($name))] = $cat;
            }
            return $cat;
        }

        if (isset($this->categoriesByName[$nameKey])) {
            $cat = $this->categoriesByName[$nameKey];
            if ($code !== null && empty($cat->code)) {
                $cat->update(['code' => $code]);
                $this->categoriesByCode[$code] = $cat;
            }
            return $cat;
        }

        $cat = Category::create([
            'code' => $code,
            'name' => $name,
            'is_active' => true,
        ]);
        $this->categoriesSynced++;

        if ($code !== null) {
            $this->categoriesByCode[$code] = $cat;
        }
        $this->categoriesByName[$nameKey] = $cat;

        return $cat;
    }

    private function resolveOrCreateSupplier(string $name, ?string $phone = null, ?string $address = null): Supplier
    {
        $key = mb_strtolower(trim($name));
        if (isset($this->suppliersByName[$key])) {
            return $this->suppliersByName[$key];
        }

        $supplier = Supplier::create([
            'name' => trim($name),
            'phone' => $phone ?: null,
            'address' => $address ?: null,
            'is_active' => true,
        ]);
        $this->suppliersSynced++;
        $this->suppliersByName[$key] = $supplier;

        return $supplier;
    }

    private function normalizeCategoryCode(string $code): string
    {
        $trimmed = trim($code);
        if (ctype_digit($trimmed) && strlen($trimmed) === 1) {
            return '0' . $trimmed;
        }
        return strtoupper($trimmed);
    }

    private function findColumn(array $normalizedRow, array $candidates): ?int
    {
        foreach ($candidates as $candidate) {
            foreach ($normalizedRow as $idx => $cell) {
                if ($cell === $candidate) {
                    return $idx;
                }
            }
        }
        // Fallback starts-with match for instructional headers like "Kode Margin 1 (Cth: K5)"
        foreach ($candidates as $candidate) {
            foreach ($normalizedRow as $idx => $cell) {
                if ($cell !== '' && str_starts_with($cell, $candidate)) {
                    return $idx;
                }
            }
        }
        return null;
    }

    private function findColumnAfter(array $normalizedRow, array $candidates, int $afterIndex): ?int
    {
        foreach ($candidates as $candidate) {
            foreach ($normalizedRow as $idx => $cell) {
                if ($idx > $afterIndex && ($cell === $candidate || str_starts_with($cell, $candidate))) {
                    return $idx;
                }
            }
        }
        return null;
    }

    private function isBlankRow(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }
        return true;
    }

    private function isHeaderEcho(string $value, string $headerWord): bool
    {
        $upper = strtoupper(trim($value));
        return $upper === $headerWord || $upper === 'SUBTOTAL' || $upper === 'TOTAL';
    }

    private function cleanNullableString(mixed $val): ?string
    {
        if ($val === null) {
            return null;
        }
        $str = trim((string) $val);
        if ($str === '' || $str === '-' || str_starts_with($str, '#')) {
            return null;
        }
        return $str;
    }

    private function cleanUnit(mixed $val, string $fallback = 'Pcs'): string
    {
        $cleaned = $this->cleanNullableString($val);
        return $cleaned ?? $fallback;
    }

    private function cleanMarginCode(mixed $val): ?string
    {
        $str = strtoupper(trim((string) $val));
        if ($str === '' || $str === '-' || str_starts_with($str, '#')) {
            return null;
        }
        return preg_match('/^K\s*[0-9]+([.,][0-9]+)?$/', $str) ? str_replace(' ', '', $str) : null;
    }

    /**
     * Hitung harga jual dari modal + kode margin Owner (mis. K5 = +5%, K50 = +50%), dibulatkan ke Rp 500 terdekat ke atas.
     */
    public function calculatePriceFromMargin(float $costPrice, string $marginCode): float
    {
        if ($costPrice <= 0) {
            return 0.0;
        }

        if (!preg_match('/^K\s*([0-9]+(?:[.,][0-9]+)?)$/i', trim($marginCode), $matches)) {
            return ceil($costPrice / 500) * 500;
        }

        $percent = (float) str_replace(',', '.', $matches[1]);
        $rawSelling = $costPrice * (1 + ($percent / 100));

        return (float) (ceil($rawSelling / 500) * 500);
    }

    private function parseNumber(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $str = trim((string) $value);
        if ($str === '' || $str === '-' || str_starts_with($str, '#')) {
            return 0.0;
        }

        $str = str_ireplace(['Rp', 'IDR', ' '], '', $str);

        // Format ribuan Indonesia bertitik: 62.000 atau 1.250.000 (cek SEBELUM is_numeric agar 62.000 tidak terbaca 62)
        if (preg_match('/^-?\d{1,3}(\.\d{3})+(,\d{1,2})?$/', $str)) {
            $str = str_replace('.', '', $str);
            $str = str_replace(',', '.', $str);
            return (float) $str;
        }

        // Format ribuan berkoma: 62,000 atau 62,000.00
        if (preg_match('/^-?\d{1,3}(,\d{3})+(\.\d{1,2})?$/', $str)) {
            $str = str_replace(',', '', $str);
            return (float) $str;
        }

        if (is_numeric($str)) {
            return (float) $str;
        }

        // Desimal koma sederhana: 7,5
        if (preg_match('/^-?\d+,\d{1,4}$/', $str)) {
            $str = str_replace(',', '.', $str);
            return (float) $str;
        }

        $cleaned = preg_replace('/[^0-9.-]/', '', str_replace('.', '', $str));
        return is_numeric($cleaned) ? (float) $cleaned : 0.0;
    }
}
