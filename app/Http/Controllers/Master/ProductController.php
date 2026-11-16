<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Imports\ProductsImport;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    public function index()
    {
        // Mengambil produk beserta relasi kategorinya
        $products = Product::with('category')->latest()->paginate(10);
        return view('master.products.index', compact('products'));
    }

    public function create()
    {
        // Mengambil kategori yang aktif saja untuk dropdown
        $categories = Category::where('is_active', true)->get();
        return view('master.products.form', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'barcode' => 'nullable|string|max:255|unique:products,barcode',
            'unit' => 'required|string|max:50',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock_minimum' => 'required|integer|min:0',
        ]);

        Product::create([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'barcode' => $request->barcode,
            'unit' => $request->unit,
            'purchase_price' => $request->purchase_price,
            'selling_price' => $request->selling_price,
            'stock_minimum' => $request->stock_minimum,
            'is_active' => $request->has('is_active') ? true : false,
        ]);

        return redirect()->route('master.products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product)
    {
        $categories = Category::where('is_active', true)->get();
        return view('master.products.form', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'barcode' => 'nullable|string|max:255|unique:products,barcode,' . $product->id,
            'unit' => 'required|string|max:50',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock_minimum' => 'required|integer|min:0',
        ]);

        $product->update([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'barcode' => $request->barcode,
            'unit' => $request->unit,
            'purchase_price' => $request->purchase_price,
            'selling_price' => $request->selling_price,
            'stock_minimum' => $request->stock_minimum,
            'is_active' => $request->has('is_active') ? true : false,
        ]);

        return redirect()->route('master.products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('master.products.index')->with('success', 'Produk berhasil dihapus.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:2048'
        ]);

        try {
            Excel::import(new ProductsImport, $request->file('file'));
            return redirect()->route('master.products.index')->with('success', 'Data Produk berhasil di-import!');
        } catch (\Exception $e) {
            return redirect()->route('master.products.index')->with('error', 'Terjadi kesalahan saat import: ' . $e->getMessage());
        }
    }
}