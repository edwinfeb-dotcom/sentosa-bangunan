<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\CashLedger;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use App\Models\StockLedger;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseReturnController extends Controller
{
    public function index()
    {
        $returns = PurchaseReturn::with(['supplier', 'purchaseOrder', 'user', 'items.product'])
            ->latest()
            ->paginate(10);

        return view('inventory.returns.index', compact('returns'));
    }

    public function create()
    {
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $products = Product::withStock()->where('is_active', true)->orderBy('name')->get();
        $purchaseOrders = PurchaseOrder::with('supplier')->where('status', 'received')->latest()->take(30)->get();
        $wallets = Wallet::where('is_active', true)->get();

        $date = date('Ymd');
        $seq = PurchaseReturn::whereDate('created_at', date('Y-m-d'))->count() + 1;
        $returnNumber = 'RET-' . $date . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);

        return view('inventory.returns.form', compact('suppliers', 'products', 'purchaseOrders', 'wallets', 'returnNumber'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'return_number' => 'required|string|unique:purchase_returns,return_number',
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'return_date' => 'required|date',
            'resolution' => 'required|in:cut_debt,cash_refund,exchange',
            'wallet_id' => 'nullable|exists:wallets,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.reason' => 'nullable|string|max:255',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $return = PurchaseReturn::create([
                    'return_number' => $request->return_number,
                    'supplier_id' => $request->supplier_id,
                    'purchase_order_id' => $request->purchase_order_id,
                    'user_id' => auth()->id(),
                    'return_date' => $request->return_date,
                    'total_amount' => 0,
                    'resolution' => $request->resolution,
                    'notes' => $request->notes,
                ]);

                $totalAmount = 0;

                foreach ($request->items as $item) {
                    $product = Product::withStock()->find($item['product_id']);
                    $stockBefore = $product->current_stock;
                    if ($stockBefore < $item['qty']) {
                        throw new \RuntimeException("Stok {$product->name} tidak cukup untuk diretur (tersisa {$stockBefore}).");
                    }

                    $subtotal = $item['qty'] * $item['price'];
                    $totalAmount += $subtotal;

                    $return->items()->create([
                        'product_id' => $product->id,
                        'qty' => $item['qty'],
                        'price' => $item['price'],
                        'subtotal' => $subtotal,
                        'reason' => $item['reason'] ?? null,
                    ]);

                    StockLedger::create([
                        'product_id' => $product->id,
                        'user_id' => auth()->id(),
                        'type' => 'out',
                        'qty' => $item['qty'],
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockBefore - $item['qty'],
                        'reference_type' => PurchaseReturn::class,
                        'reference_id' => $return->id,
                        'description' => 'Retur Supplier: ' . $return->return_number,
                    ]);
                }

                $return->update(['total_amount' => $totalAmount]);

                if ($request->resolution === 'cut_debt' && $totalAmount > 0) {
                    $remainingToCut = $totalAmount;
                    $invoices = SupplierInvoice::where('supplier_id', $request->supplier_id)
                        ->where('status', '!=', 'paid')
                        ->orderBy('id')
                        ->get();

                    foreach ($invoices as $invoice) {
                        if ($remainingToCut <= 0) {
                            break;
                        }
                        $unpaid = max(0, (float) $invoice->total_amount - (float) $invoice->paid_amount);
                        if ($unpaid <= 0) {
                            continue;
                        }
                        $cut = min($unpaid, $remainingToCut);
                        $newPaid = (float) $invoice->paid_amount + $cut;
                        $invoice->update([
                            'paid_amount' => $newPaid,
                            'status' => $newPaid >= $invoice->total_amount ? 'paid' : 'partial',
                        ]);
                        $remainingToCut -= $cut;
                    }
                } elseif ($request->resolution === 'cash_refund' && $totalAmount > 0) {
                    $walletId = $request->wallet_id ?: Wallet::where('is_active', true)->value('id');
                    CashLedger::create([
                        'user_id' => auth()->id(),
                        'wallet_id' => $walletId,
                        'type' => 'in',
                        'category' => 'retur_pembelian',
                        'amount' => $totalAmount,
                        'description' => 'Refund Retur Supplier: ' . $return->return_number,
                        'reference_type' => PurchaseReturn::class,
                        'reference_id' => $return->id,
                        'date' => $request->return_date,
                    ]);
                }
            });

            return redirect()->route('inventory.returns.index')->with('success', 'Retur barang ke supplier berhasil dicatat dan stok diperbarui.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal mencatat retur: ' . $e->getMessage())->withInput();
        }
    }
}
