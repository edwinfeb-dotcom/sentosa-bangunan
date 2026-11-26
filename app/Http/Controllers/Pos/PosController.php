<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\CashLedger;
use App\Models\CashSession;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerDebtLedger;
use App\Models\DepositLedger;
use App\Models\EmployeeDebt;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockLedger;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PosController extends Controller
{
    public function index()
    {
        $session = CashSession::where('user_id', auth()->id())->where('status', 'open')->first();
        if (!$session) {
            return redirect()->route('pos.session.index')->with('error', 'Silakan buka sesi kasir terlebih dahulu.');
        }

        $products = Product::select(['id', 'category_id', 'barcode', 'name', 'unit', 'image', 'selling_price', 'secondary_unit', 'secondary_ratio', 'secondary_price', 'stock_minimum'])
            ->with('category:id,name')
            ->withStock()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $categories = Category::select(['id', 'name'])->where('is_active', true)->orderBy('name')->get();
        $wallets = Wallet::select(['id', 'name', 'type'])->where('is_active', true)->get();

        $idempotencyKey = (string) Str::uuid();

        $cashSales = Payment::whereHas('sale', function ($q) use ($session) {
            $q->where('cash_session_id', $session->id)->where('status', 'completed');
        })->where('method', 'cash')->sum('amount');

        $cashWalletIds = Wallet::where('type', 'cash')->pluck('id');
        $shiftPettyCashNet = (float) CashLedger::whereIn('wallet_id', $cashWalletIds)
            ->whereNull('reference_type')
            ->where('created_at', '>=', $session->start_time)
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'in' THEN amount ELSE -amount END), 0) as net")
            ->value('net');

        $expectedCash = $session->opening_balance + $cashSales + $shiftPettyCashNet;
        $employees = User::where('role', 'karyawan')->where('employee_discount', '>', 0)->get();
        $customers = Customer::select(['id', 'name', 'points', 'deposit_balance', 'debt_balance'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $recentSales = Sale::with(['customer', 'payments'])
            ->where('cash_session_id', $session->id)
            ->latest('id')
            ->take(15)
            ->get();

        return view('pos.index', compact(
            'products', 'categories', 'wallets', 'session',
            'idempotencyKey', 'cashSales', 'shiftPettyCashNet', 'expectedCash', 'employees', 'customers', 'recentSales'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'idempotency_key' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.unit_type' => 'nullable|in:primary,secondary',
            'payment_method' => 'required|in:cash,transfer,employee_debt,deposit,customer_debt',
            'paid_amount' => 'required|numeric|min:0',
            'manual_discount' => 'nullable|numeric|min:0',
            'discount_note' => 'nullable|string|max:120',
            'employee_id' => 'nullable|exists:users,id',
            'customer_id' => 'nullable|exists:customers,id',
            'wallet_id' => 'nullable|exists:wallets,id',
        ]);

        if (Sale::where('idempotency_key', $request->idempotency_key)->exists()) {
            return response()->json(['status' => 'error', 'message' => 'Transaksi ini sudah diproses.'], 422);
        }

        $session = CashSession::where('user_id', auth()->id())->where('status', 'open')->first();
        if (!$session) {
            return response()->json(['status' => 'error', 'message' => 'Sesi kasir tidak ditemukan/tertutup.'], 422);
        }

        try {
            $sale = DB::transaction(function () use ($request, $session) {
                $totalBeforeDiscount = 0;
                $invoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad(Sale::whereDate('created_at', date('Y-m-d'))->count() + 1, 4, '0', STR_PAD_LEFT);

                $sale = Sale::create([
                    'invoice_number' => $invoiceNumber,
                    'idempotency_key' => $request->idempotency_key,
                    'cash_session_id' => $session->id,
                    'user_id' => auth()->id(),
                    'customer_id' => $request->customer_id,
                    'total_amount' => 0,
                    'discount_amount' => 0,
                    'status' => 'completed',
                ]);

                foreach ($request->items as $item) {
                    $product = Product::withStock()->find($item['product_id']);
                    if (!$product) {
                        throw new \RuntimeException('Produk tidak ditemukan.');
                    }

                    $useSecondary = ($item['unit_type'] ?? 'primary') === 'secondary'
                        && filled($product->secondary_unit)
                        && (float) $product->secondary_price > 0;

                    $unitPrice = $useSecondary ? (float) $product->secondary_price : (float) $product->selling_price;
                    $unitLabel = $useSecondary ? $product->secondary_unit : $product->unit;

                    $stockDeductQty = (int) $item['qty'];
                    if ($useSecondary && (float) $product->secondary_ratio > 1) {
                        $stockDeductQty = max(1, (int) ceil($item['qty'] / (float) $product->secondary_ratio));
                    }

                    $stockBefore = $product->current_stock;
                    if ($stockBefore < $stockDeductQty) {
                        throw new \RuntimeException("Stok {$product->name} tidak mencukupi (tersisa {$stockBefore} {$product->unit}).");
                    }

                    $subtotal = $item['qty'] * $unitPrice;
                    $totalBeforeDiscount += $subtotal;

                    $sale->items()->create([
                        'product_id' => $product->id,
                        'qty' => $item['qty'],
                        'unit_label' => $unitLabel,
                        'price_snapshot' => $unitPrice,
                        'subtotal' => $subtotal,
                    ]);

                    StockLedger::create([
                        'product_id' => $product->id,
                        'user_id' => auth()->id(),
                        'type' => 'out',
                        'qty' => $stockDeductQty,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockBefore - $stockDeductQty,
                        'reference_type' => get_class($sale),
                        'reference_id' => $sale->id,
                        'description' => 'Penjualan Kasir: ' . $sale->invoice_number . ($useSecondary ? " ({$item['qty']} {$unitLabel})" : ''),
                    ]);
                }

                $discountAmount = 0;
                if ($request->employee_id) {
                    $employee = User::find($request->employee_id);
                    if ($employee) {
                        $discountAmount = $totalBeforeDiscount * ($employee->employee_discount / 100);
                    }
                }

                $manualDiscount = max(0, (float) ($request->manual_discount ?? 0));
                $afterEmpDiscount = max(0, $totalBeforeDiscount - $discountAmount);
                $manualDiscount = min($manualDiscount, $afterEmpDiscount);
                $totalAmount = max(0, $afterEmpDiscount - $manualDiscount);

                $sale->update([
                    'total_amount' => $totalAmount,
                    'discount_amount' => $discountAmount,
                    'manual_discount' => $manualDiscount,
                    'discount_note' => $manualDiscount > 0 ? $request->discount_note : null,
                ]);

                if ($request->customer_id) {
                    $customer = Customer::lockForUpdate()->find($request->customer_id);
                    if ($customer) {
                        if ($request->payment_method === 'deposit') {
                            if ((float) $customer->deposit_balance < $totalAmount) {
                                throw new \RuntimeException('Saldo deposit ' . $customer->name . ' tidak mencukupi (tersisa Rp ' . number_format($customer->deposit_balance, 0, ',', '.') . ').');
                            }
                            $customer->decrement('deposit_balance', $totalAmount);
                            DepositLedger::create([
                                'customer_id' => $customer->id,
                                'user_id' => auth()->id(),
                                'type' => 'out',
                                'amount' => $totalAmount,
                                'description' => 'Pembayaran POS: ' . $sale->invoice_number,
                            ]);
                        } elseif ($request->payment_method === 'customer_debt') {
                            $customer->increment('debt_balance', $totalAmount);
                            CustomerDebtLedger::create([
                                'customer_id' => $customer->id,
                                'user_id' => auth()->id(),
                                'type' => 'in',
                                'amount' => $totalAmount,
                                'description' => 'Belanja Bon POS: ' . $sale->invoice_number,
                            ]);
                        }

                        $pointsEarned = floor($totalAmount / 50000);
                        if ($pointsEarned > 0) {
                            $customer->increment('points', $pointsEarned);
                        }
                    }
                } elseif (in_array($request->payment_method, ['deposit', 'customer_debt'], true)) {
                    throw new \RuntimeException('Silakan pilih pelanggan terlebih dahulu untuk metode Deposit atau Bon Pelanggan.');
                }

                // Resolve wallet
                $walletId = $request->wallet_id;
                $nonCashMethods = ['employee_debt', 'deposit', 'customer_debt'];
                if (!$walletId && !in_array($request->payment_method, $nonCashMethods, true)) {
                    $walletType = $request->payment_method === 'transfer' ? 'bank' : 'cash';
                    $walletId = Wallet::where('type', $walletType)->where('is_active', true)->value('id');
                }

                $paidAmountRecorded = in_array($request->payment_method, $nonCashMethods, true)
                    ? $totalAmount
                    : $request->paid_amount;

                $sale->payments()->create([
                    'wallet_id' => in_array($request->payment_method, $nonCashMethods, true) ? null : $walletId,
                    'method' => $request->payment_method,
                    'amount' => $paidAmountRecorded,
                ]);

                if ($request->payment_method === 'employee_debt' && $request->employee_id) {
                    EmployeeDebt::create([
                        'user_id' => $request->employee_id,
                        'sale_id' => $sale->id,
                        'amount' => $totalAmount,
                        'status' => 'unpaid',
                        'notes' => 'Belanja di POS: ' . $sale->invoice_number,
                    ]);
                }

                if (!in_array($request->payment_method, $nonCashMethods, true)) {
                    CashLedger::create([
                        'user_id' => auth()->id(),
                        'wallet_id' => $walletId,
                        'type' => 'in',
                        'category' => 'penjualan',
                        'amount' => $totalAmount,
                        'description' => 'Penjualan POS: ' . $sale->invoice_number,
                        'reference_type' => get_class($sale),
                        'reference_id' => $sale->id,
                        'date' => date('Y-m-d'),
                    ]);
                }

                return $sale;
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Transaksi berhasil!',
                'redirect' => route('pos.receipt', $sale->id),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function receipt(Sale $sale)
    {
        $sale->load(['items.product', 'payments.wallet', 'cashier', 'customer']);
        return view('pos.receipt', compact('sale'));
    }

    public function voidSale(Request $request, Sale $sale)
    {
        if ($sale->status === 'cancelled') {
            return back()->with('error', 'Struk ini sudah pernah dibatalkan / diretur.');
        }

        $reason = trim((string) $request->input('reason', 'Retur / Pembatalan Struk Kasir'));

        DB::transaction(function () use ($sale, $reason) {
            $sale->load(['items.product', 'payments', 'customer']);

            // 1. Kembalikan stok berdasarkan StockLedger keluar yang tercatat untuk sale ini
            $outLedgers = StockLedger::where('reference_type', get_class($sale))
                ->where('reference_id', $sale->id)
                ->where('type', 'out')
                ->get();

            if ($outLedgers->isNotEmpty()) {
                foreach ($outLedgers as $ledger) {
                    $product = Product::withStock()->find($ledger->product_id);
                    if (!$product) {
                        continue;
                    }
                    $stockBefore = $product->current_stock;
                    StockLedger::create([
                        'product_id' => $product->id,
                        'user_id' => auth()->id(),
                        'type' => 'in',
                        'qty' => $ledger->qty,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockBefore + $ledger->qty,
                        'reference_type' => get_class($sale),
                        'reference_id' => $sale->id,
                        'description' => "Retur Struk {$sale->invoice_number}: {$reason}",
                    ]);
                }
            } else {
                foreach ($sale->items as $item) {
                    $product = Product::withStock()->find($item->product_id);
                    if (!$product) {
                        continue;
                    }
                    $stockBefore = $product->current_stock;
                    StockLedger::create([
                        'product_id' => $product->id,
                        'user_id' => auth()->id(),
                        'type' => 'in',
                        'qty' => $item->qty,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockBefore + $item->qty,
                        'reference_type' => get_class($sale),
                        'reference_id' => $sale->id,
                        'description' => "Retur Struk {$sale->invoice_number}: {$reason}",
                    ]);
                }
            }

            // 2. Kembalikan pembayaran sesuai metode
            $payment = $sale->payments->first();
            if ($payment) {
                if ($payment->method === 'deposit' && $sale->customer) {
                    $sale->customer->increment('deposit_balance', $sale->total_amount);
                    DepositLedger::create([
                        'customer_id' => $sale->customer->id,
                        'user_id' => auth()->id(),
                        'type' => 'in',
                        'amount' => $sale->total_amount,
                        'description' => "Refund Retur Struk {$sale->invoice_number}",
                    ]);
                } elseif ($payment->method === 'customer_debt' && $sale->customer) {
                    $deduct = min((float) $sale->customer->debt_balance, (float) $sale->total_amount);
                    if ($deduct > 0) {
                        $sale->customer->decrement('debt_balance', $deduct);
                    }
                    CustomerDebtLedger::create([
                        'customer_id' => $sale->customer->id,
                        'user_id' => auth()->id(),
                        'type' => 'out',
                        'amount' => $sale->total_amount,
                        'description' => "Batal / Retur Bon Struk {$sale->invoice_number}",
                    ]);
                } elseif (in_array($payment->method, ['cash', 'transfer'], true)) {
                    CashLedger::create([
                        'user_id' => auth()->id(),
                        'wallet_id' => $payment->wallet_id ?: Wallet::where('is_active', true)->value('id'),
                        'type' => 'out',
                        'category' => 'retur_penjualan',
                        'amount' => $sale->total_amount,
                        'description' => "Refund Retur Struk {$sale->invoice_number}: {$reason}",
                        'reference_type' => get_class($sale),
                        'reference_id' => $sale->id,
                        'date' => date('Y-m-d'),
                    ]);
                } elseif ($payment->method === 'employee_debt') {
                    EmployeeDebt::where('sale_id', $sale->id)->where('status', 'unpaid')->delete();
                }
            }

            $sale->update(['status' => 'cancelled']);
        });

        return back()->with('success', "Struk {$sale->invoice_number} berhasil dibatalkan. Stok dan kas/deposit sudah dikembalikan.");
    }
}
