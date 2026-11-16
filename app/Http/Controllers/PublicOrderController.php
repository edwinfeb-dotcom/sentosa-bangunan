<?php

namespace App\Http\Controllers;

use App\Models\PublicOrder;
use App\Models\Product;
use App\Models\StockLedger;
use App\Models\CashLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicOrderController extends Controller
{
    public function index()
    {
        $orders = PublicOrder::latest()->paginate(10);
        return view('admin.public_orders.index', compact('orders'));
    }

    public function process(PublicOrder $order)
    {
        if ($order->status !== 'pending') {
            return back()->with('error', 'Pesanan ini sudah diproses sebelumnya.');
        }

        try {
            DB::transaction(function () use ($order) {
                // 1. Potong Stok & Catat Ledger
                foreach ($order->items as $item) {
                    $product = Product::find($item->product_id);
                    $stockBefore = $product->current_stock;
                    
                    StockLedger::create([
                        'product_id' => $product->id,
                        'user_id' => auth()->id(),
                        'type' => 'out',
                        'qty' => $item->qty,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockBefore - $item->qty,
                        'reference_type' => get_class($order),
                        'reference_id' => $order->id,
                        'description' => 'Pesanan Online: ' . $order->order_code,
                    ]);
                }

                // 2. Catat Uang Masuk ke Buku Kas
                CashLedger::create([
                    'user_id' => auth()->id(),
                    'type' => 'in',
                    'amount' => $order->total_amount,
                    'description' => 'Pesanan Online: ' . $order->order_code . ' (' . $order->customer_name . ')',
                    'reference_type' => get_class($order),
                    'reference_id' => $order->id,
                    'date' => date('Y-m-d'),
                ]);

                // 3. Ubah status pesanan
                $order->update(['status' => 'processed']);
            });

            return back()->with('success', 'Pesanan berhasil diproses! Stok telah dipotong dan uang masuk ke Buku Kas.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses pesanan: ' . $e->getMessage());
        }
    }
}