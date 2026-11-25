<?php

namespace App\Http\Controllers\Master;

use App\Exports\ProductImagesTemplateExport;
use App\Exports\ProductsExport;
use App\Exports\ProductsTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\ProductImagesImport;
use App\Imports\ProductsImport;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockLedger;
use App\Support\SimpleXlsxReader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category', 'supplier'])->withStock();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->latest()->paginate(10)->withQueryString();
        $categories = Category::where('is_active', true)->orderBy('name')->get();

        return view('master.products.index', compact('products', 'categories'));
    }

    public function create()
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        return view('master.products.form', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'barcode' => 'nullable|string|max:255|unique:products,barcode',
            'image' => 'nullable|image|max:2048',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'rack_location' => 'nullable|string|max:100',
            'margin_code' => 'nullable|string|max:20',
            'secondary_unit' => 'nullable|string|max:50',
            'secondary_price' => 'nullable|numeric|min:0',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock_minimum' => 'required|integer|min:0',
            'initial_stock' => 'nullable|integer|min:0',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        $product = Product::create([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'barcode' => $request->barcode,
            'image' => $imagePath,
            'description' => $request->description,
            'unit' => $request->unit,
            'rack_location' => $request->rack_location,
            'margin_code' => $request->margin_code,
            'secondary_unit' => $request->secondary_unit,
            'secondary_price' => $request->filled('secondary_price') ? $request->secondary_price : null,
            'purchase_price' => $request->purchase_price,
            'selling_price' => $request->selling_price,
            'stock_minimum' => $request->stock_minimum,
            'is_active' => $request->has('is_active'),
        ]);

        $initialStock = max(0, (int) $request->input('initial_stock', 0));
        if ($initialStock > 0) {
            StockLedger::create([
                'product_id' => $product->id,
                'user_id' => auth()->id() ?? 1,
                'type' => 'in',
                'qty' => $initialStock,
                'stock_before' => 0,
                'stock_after' => $initialStock,
                'reference_type' => 'manual_form',
                'reference_id' => $product->id,
                'description' => 'Stok awal saat tambah produk',
            ]);
        }

        return redirect()->route('master.products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product)
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        return view('master.products.form', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'barcode' => 'nullable|string|max:255|unique:products,barcode,' . $product->id,
            'image' => 'nullable|image|max:2048',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'rack_location' => 'nullable|string|max:100',
            'margin_code' => 'nullable|string|max:20',
            'secondary_unit' => 'nullable|string|max:50',
            'secondary_price' => 'nullable|numeric|min:0',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock_minimum' => 'required|integer|min:0',
            'initial_stock' => 'nullable|integer|min:0',
        ]);

        $data = $request->except(['image', 'initial_stock']);
        if (!$request->filled('secondary_price')) {
            $data['secondary_price'] = null;
        }

        if ($request->hasFile('image')) {
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $data['is_active'] = $request->has('is_active');
        $product->update($data);

        if ($request->filled('initial_stock')) {
            $targetStock = max(0, (int) $request->input('initial_stock'));
            $currentStock = (int) $product->current_stock;
            $diff = $targetStock - $currentStock;
            if ($diff !== 0) {
                StockLedger::create([
                    'product_id' => $product->id,
                    'user_id' => auth()->id() ?? 1,
                    'type' => $diff > 0 ? 'in' : 'out',
                    'qty' => abs($diff),
                    'stock_before' => $currentStock,
                    'stock_after' => $targetStock,
                    'reference_type' => 'manual_form',
                    'reference_id' => $product->id,
                    'description' => 'Penyesuaian stok via edit produk',
                ]);
            }
        }

        return redirect()->route('master.products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();
        return redirect()->route('master.products.index')->with('success', 'Produk berhasil dihapus.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv,txt|max:25600',
        ]);

        $uploadedFile = $request->file('file');
        $ext = strtolower($uploadedFile->getClientOriginalExtension());
        $filePath = $uploadedFile->getRealPath();

        try {
            @set_time_limit(180);
            $import = new ProductsImport();
            $imageImporter = new ProductImagesImport();

            DB::transaction(function () use ($import, $imageImporter, $uploadedFile, $filePath, $ext) {
                $this->runProductDataImport($import, $uploadedFile, $filePath, $ext);

                // Jika file .xlsx juga berisi foto tertanam, langsung impor gambarnya sekaligus!
                if (in_array($ext, ['xlsx', 'xls'], true)) {
                    $imageImporter->import($filePath);
                }
            });

            if (
                $import->createdCount === 0
                && $import->updatedCount === 0
                && $import->categoriesSynced === 0
                && $import->suppliersSynced === 0
                && $imageImporter->updatedCount === 0
            ) {
                return redirect()->route('master.products.index')->with(
                    'error',
                    'Tidak ada data produk atau gambar yang terdeteksi di file ini. Pastikan header sesuai Template Produk atau Template Lengkap.'
                );
            }

            return redirect()->route('master.products.index')->with(
                'success',
                $this->buildImportSummaryMessage($import, $imageImporter)
            );
        } catch (\Throwable $e) {
            return redirect()->route('master.products.index')->with('error', 'Terjadi kesalahan saat import: ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        return Excel::download(new ProductsTemplateExport, 'Template_Import_Produk_Sentosa.xlsx');
    }

    public function export()
    {
        return Excel::download(new ProductsExport, 'Data_Produk_Sentosa_' . date('Y-m-d') . '.xlsx');
    }

    public function barcodeIndex()
    {
        $products = Product::select(['id', 'name', 'barcode', 'selling_price'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        return view('master.products.barcode', compact('products'));
    }

    public function downloadImageTemplate()
    {
        return Excel::download(new ProductImagesTemplateExport, 'Template_Import_Gambar_Sentosa.xlsx');
    }

    public function importImages(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:25600',
        ]);

        $uploadedFile = $request->file('file');
        $ext = strtolower($uploadedFile->getClientOriginalExtension());
        $filePath = $uploadedFile->getRealPath();

        try {
            @set_time_limit(180);
            $import = new ProductsImport();
            $imageImporter = new ProductImagesImport();

            DB::transaction(function () use ($import, $imageImporter, $uploadedFile, $filePath, $ext) {
                // Sekaligus sinkronkan produk, harga, dan stok dari kolom Excel jika ada
                $this->runProductDataImport($import, $uploadedFile, $filePath, $ext);
                $imageImporter->import($filePath);
            });

            $hasUpdates = ($imageImporter->updatedCount > 0 || $import->createdCount > 0 || $import->updatedCount > 0);
            if (!$hasUpdates && $imageImporter->notFoundCount === 0 && $imageImporter->skippedCount === 0) {
                return redirect()->route('master.products.index')->with(
                    'error',
                    'Tidak ada gambar atau perubahan data produk yang terdeteksi di file Excel. Pastikan gambar disisipkan lewat menu Insert -> Pictures di dalam sel kolom foto.'
                );
            }

            return redirect()->route('master.products.index')->with(
                $hasUpdates ? 'success' : 'error',
                $this->buildImportSummaryMessage($import, $imageImporter)
            );
        } catch (\Throwable $e) {
            return redirect()->route('master.products.index')->with('error', 'Gagal mengimport gambar Excel: ' . $e->getMessage());
        }
    }

    private function runProductDataImport(ProductsImport $import, \Illuminate\Http\UploadedFile $uploadedFile, string $filePath, string $ext): void
    {
        if ($ext === 'xlsx' && !class_exists('ZipArchive')) {
            $sheets = SimpleXlsxReader::readAllSheetsAsNumericRows($filePath);
            if (empty($sheets)) {
                throw new \RuntimeException('File .xlsx tidak dapat dibaca atau kosong.');
            }
            foreach ($sheets as $sheetRows) {
                $import->array($sheetRows);
            }
            return;
        }

        Excel::import($import, $uploadedFile);
    }

    private function buildImportSummaryMessage(ProductsImport $import, ProductImagesImport $imageImporter): string
    {
        $parts = [];
        if ($import->createdCount > 0) {
            $parts[] = "{$import->createdCount} produk baru ditambahkan";
        }
        if ($import->updatedCount > 0) {
            $parts[] = "{$import->updatedCount} data/stok produk diperbarui";
        }
        if ($imageImporter->updatedCount > 0) {
            $parts[] = "{$imageImporter->updatedCount} foto produk berhasil dipasang";
        }
        if ($import->categoriesSynced > 0) {
            $parts[] = "{$import->categoriesSynced} kategori baru dibuat";
        }
        if ($import->suppliersSynced > 0) {
            $parts[] = "{$import->suppliersSynced} supplier baru dibuat";
        }
        if ($imageImporter->notFoundCount > 0) {
            $parts[] = "{$imageImporter->notFoundCount} kode gambar tidak ditemukan";
        }
        if ($import->skippedCount > 0 || $imageImporter->skippedCount > 0) {
            $skipped = $import->skippedCount + $imageImporter->skippedCount;
            $parts[] = "{$skipped} baris dilewati";
        }

        return 'Import selesai! ' . implode(', ', $parts) . '.';
    }

    public function importImagesBatch(Request $request)
    {
        $request->validate([
            'images' => 'required|array|min:1|max:200',
            'images.*' => 'required|file|mimes:png,jpg,jpeg,gif,webp,xlsx,xls|max:25600',
        ], [
            'images.required' => 'Pilih minimal 1 file foto atau file Excel (.xlsx).',
            'images.*.mimes' => 'Format file harus PNG, JPG, JPEG, GIF, WEBP, atau file Excel (.xlsx/.xls).',
            'images.*.max' => 'Ukuran maksimal tiap file adalah 25 MB.',
        ]);

        try {
            @set_time_limit(180);
            $dataImport = new ProductsImport();
            $imageImporter = new ProductImagesImport();
            $imageFiles = [];

            DB::transaction(function () use ($request, $dataImport, $imageImporter, &$imageFiles) {
                foreach ($request->file('images', []) as $file) {
                    $ext = strtolower($file->getClientOriginalExtension());
                    if (in_array($ext, ['xlsx', 'xls'], true)) {
                        $this->runProductDataImport($dataImport, $file, $file->getRealPath(), $ext);
                        $imageImporter->import($file->getRealPath());
                    } else {
                        $imageFiles[] = $file;
                    }
                }

                if (!empty($imageFiles)) {
                    $imageImporter->importBatchFiles($imageFiles);
                }
            });

            $hasUpdates = ($imageImporter->updatedCount > 0 || $dataImport->createdCount > 0 || $dataImport->updatedCount > 0);
            if (!$hasUpdates && $imageImporter->notFoundCount === 0 && $imageImporter->skippedCount === 0) {
                return redirect()->route('master.products.index')->with(
                    'error',
                    'Tidak ada gambar atau data produk yang terdeteksi. Pastikan gambar disisipkan lewat menu Insert -> Pictures di Excel, atau upload file PNG/JPG bernama kode barang.'
                );
            }

            return redirect()->route('master.products.index')->with(
                $hasUpdates ? 'success' : 'error',
                $this->buildImportSummaryMessage($dataImport, $imageImporter)
            );
        } catch (\Throwable $e) {
            return redirect()->route('master.products.index')->with('error', 'Gagal upload foto massal: ' . $e->getMessage());
        }
    }
}
