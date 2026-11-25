<?php

namespace App\Http\Controllers;

use App\Models\CashLedger;
use App\Models\Product;
use App\Models\PublicOrder;
use App\Models\StockLedger;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = PublicOrder::with('items.product')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('order_code', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $orders = $query->paginate(10)->withQueryString();
        return view('admin.public_orders.index', compact('orders'));
    }

    public function process(PublicOrder $order)
    {
        return $this->transitionOrder($order, 'packed');
    }

    public function updateStatus(Request $request, PublicOrder $order)
    {
        $request->validate([
            'status' => 'required|in:packed,shipped,completed,cancelled',
        ]);

        return $this->transitionOrder($order, $request->status);
    }

    private function transitionOrder(PublicOrder $order, string $targetStatus)
    {
        if (in_array($order->status, ['completed', 'cancelled'])) {
            return back()->with('error', 'Pesanan ini sudah berstatus final.');
        }

        try {
            DB::transaction(function () use ($order, $targetStatus) {
                if ($targetStatus !== 'cancelled') {
                    $alreadyDeducted = StockLedger::where('reference_type', PublicOrder::class)
                        ->where('reference_id', $order->id)
                        ->exists();

                    if (!$alreadyDeducted) {
                        foreach ($order->items as $item) {
                            $product = Product::withStock()->find($item->product_id);
                            if (!$product) {
                                continue;
                            }
                            $stockBefore = $product->current_stock;
                            if ($stockBefore < $item->qty) {
                                throw new \RuntimeException("Stok {$product->name} tidak mencukupi (tersisa {$stockBefore}).");
                            }

                            StockLedger::create([
                                'product_id' => $product->id,
                                'user_id' => auth()->id(),
                                'type' => 'out',
                                'qty' => $item->qty,
                                'stock_before' => $stockBefore,
                                'stock_after' => $stockBefore - $item->qty,
                                'reference_type' => PublicOrder::class,
                                'reference_id' => $order->id,
                                'description' => 'Pesanan Online: ' . $order->order_code,
                            ]);
                        }
                    }

                    $alreadyRecordedCash = CashLedger::where('reference_type', PublicOrder::class)
                        ->where('reference_id', $order->id)
                        ->exists();

                    if (!$alreadyRecordedCash) {
                        $walletType = $order->payment_method === 'transfer' ? 'bank' : 'cash';
                        $wallet = Wallet::where('type', $walletType)->where('is_active', true)->first();

                        CashLedger::create([
                            'user_id' => auth()->id(),
                            'wallet_id' => $wallet?->id,
                            'type' => 'in',
                            'category' => 'pesanan_online',
                            'amount' => $order->total_amount,
                            'description' => 'Pesanan Online: ' . $order->order_code . ' (' . $order->customer_name . ')',
                            'reference_type' => PublicOrder::class,
                            'reference_id' => $order->id,
                            'date' => date('Y-m-d'),
                        ]);
                    }
                }

                $order->update(['status' => $targetStatus]);
            });

            return back()->with('success', 'Status pesanan berhasil diperbarui.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memproses pesanan: ' . $e->getMessage());
        }
    }
}
