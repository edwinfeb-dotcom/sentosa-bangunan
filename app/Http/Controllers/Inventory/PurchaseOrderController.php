<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        // Eager loading relasi supplier dan user
        $purchaseOrders = PurchaseOrder::with(['supplier', 'user'])->latest()->paginate(10);
        return view('inventory.po.index', compact('purchaseOrders'));
    }

    public function create()
    {
        $suppliers = Supplier::where('is_active', true)->get();
        $products = Product::where('is_active', true)->get();
        
        // Generate nomor PO otomatis (Contoh: PO-20261113-0001)
        $date = date('Ymd');
        $lastPo = PurchaseOrder::whereDate('created_at', date('Y-m-d'))->count() + 1;
        $poNumber = 'PO-' . $date . '-' . str_pad($lastPo, 4, '0', STR_PAD_LEFT);

        return view('inventory.po.form', compact('suppliers', 'products', 'poNumber'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'order_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use ($request) {
                // 1. Buat PO
                $po = PurchaseOrder::create([
                    'po_number' => $request->po_number,
                    'supplier_id' => $request->supplier_id,
                    'user_id' => auth()->id(),
                    'order_date' => $request->order_date,
                    'status' => 'ordered', // Langsung berstatus ordered
                    'total_amount' => 0, // Akan diupdate di bawah
                    'notes' => $request->notes,
                ]);

                $totalAmount = 0;

                // 2. Simpan Item PO
                foreach ($request->items as $item) {
                    $subtotal = $item['qty'] * $item['price'];
                    $totalAmount += $subtotal;

                    $po->items()->create([
                        'product_id' => $item['product_id'],
                        'qty' => $item['qty'],
                        'price' => $item['price'],
                        'subtotal' => $subtotal,
                    ]);
                }

                // 3. Update Total Amount PO
                $po->update(['total_amount' => $totalAmount]);
            });

            return redirect()->route('inventory.po.index')->with('success', 'Purchase Order berhasil dibuat.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membuat PO: ' . $e->getMessage())->withInput();
        }
    }

    public function receive(PurchaseOrder $po)
    {
        // Pastikan statusnya masih ordered
        if ($po->status !== 'ordered') {
            return back()->with('error', 'PO ini tidak dalam status yang bisa diterima.');
        }

        try {
            DB::transaction(function () use ($po) {
                // Looping semua barang di PO
                foreach ($po->items as $item) {
                    $product = Product::find($item->product_id);
                    
                    // Hitung stok saat ini (dari ledger)
                    $stockBefore = $product->current_stock;
                    $stockAfter = $stockBefore + $item->qty;

                    // Catat ke Stock Ledger sebagai barang masuk ('in')
                    \App\Models\StockLedger::create([
                        'product_id' => $product->id,
                        'user_id' => auth()->id(),
                        'type' => 'in',
                        'qty' => $item->qty,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                        'reference_type' => get_class($po),
                        'reference_id' => $po->id,
                        'description' => 'Penerimaan barang PO: ' . $po->po_number,
                    ]);
                }
                // OTOMATIS BUAT NOTA SUPPLIER (HUTANG)
                \App\Models\SupplierInvoice::create([
                    'supplier_id' => $po->supplier_id,
                    'purchase_order_id' => $po->id,
                    'invoice_number' => 'INV-' . $po->po_number,
                    'total_amount' => $po->total_amount,
                    'paid_amount' => 0,
                    'status' => 'unpaid',
                ]);

                // Ubah status PO menjadi received
                $po->update(['status' => 'received']);
            });

            return redirect()->route('inventory.po.index')->with('success', 'Barang berhasil diterima. Stok telah bertambah otomatis!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menerima barang: ' . $e->getMessage());
        }
    }
}