<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockLedger;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        $purchaseOrders = PurchaseOrder::with(['supplier', 'user', 'items.product'])
            ->latest()
            ->paginate(10);
        return view('inventory.po.index', compact('purchaseOrders'));
    }

    public function create()
    {
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();

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
                $po = PurchaseOrder::create([
                    'po_number' => $request->po_number,
                    'supplier_id' => $request->supplier_id,
                    'user_id' => auth()->id(),
                    'order_date' => $request->order_date,
                    'status' => 'ordered',
                    'total_amount' => 0,
                    'notes' => $request->notes,
                ]);

                $totalAmount = 0;

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

                $po->update(['total_amount' => $totalAmount]);
            });

            return redirect()->route('inventory.po.index')->with('success', 'Purchase Order berhasil dibuat.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membuat PO: ' . $e->getMessage())->withInput();
        }
    }

    public function receive(PurchaseOrder $po)
    {
        if ($po->status !== 'ordered') {
            return back()->with('error', 'PO ini tidak dalam status yang bisa diterima.');
        }

        try {
            DB::transaction(function () use ($po) {
                foreach ($po->items as $item) {
                    $product = Product::find($item->product_id);
                    $stockBefore = $product->current_stock;
                    $stockAfter = $stockBefore + $item->qty;

                    StockLedger::create([
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

                SupplierInvoice::create([
                    'supplier_id' => $po->supplier_id,
                    'purchase_order_id' => $po->id,
                    'invoice_number' => 'INV-' . $po->po_number,
                    'total_amount' => $po->total_amount,
                    'paid_amount' => 0,
                    'status' => 'unpaid',
                ]);

                $po->update(['status' => 'received']);
            });

            return redirect()->route('inventory.po.index')->with('success', 'Barang berhasil diterima. Stok telah bertambah otomatis!');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menerima barang: ' . $e->getMessage());
        }
    }
}
