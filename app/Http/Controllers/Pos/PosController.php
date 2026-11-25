<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\CashLedger;
use App\Models\CashSession;
use App\Models\Category;
use App\Models\Customer;
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

        $products = Product::select(['id', 'category_id', 'barcode', 'name', 'unit', 'image', 'selling_price', 'stock_minimum'])
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

        $expectedCash = $session->opening_balance + $cashSales;
        $employees = User::where('role', 'karyawan')->where('employee_discount', '>', 0)->get();
        $customers = Customer::select(['id', 'name', 'points', 'deposit_balance'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('pos.index', compact(
            'products', 'categories', 'wallets', 'session',
            'idempotencyKey', 'cashSales', 'expectedCash', 'employees', 'customers'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'idempotency_key' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'payment_method' => 'required|in:cash,transfer,employee_debt,deposit',
            'paid_amount' => 'required|numeric|min:0',
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
                    $stockBefore = $product->current_stock;
                    if ($stockBefore < $item['qty']) {
                        throw new \RuntimeException("Stok {$product->name} tidak mencukupi (tersisa {$stockBefore} {$product->unit}).");
                    }

                    $subtotal = $item['qty'] * $product->selling_price;
                    $totalBeforeDiscount += $subtotal;

                    $sale->items()->create([
                        'product_id' => $product->id,
                        'qty' => $item['qty'],
                        'price_snapshot' => $product->selling_price,
                        'subtotal' => $subtotal,
                    ]);

                    StockLedger::create([
                        'product_id' => $product->id,
                        'user_id' => auth()->id(),
                        'type' => 'out',
                        'qty' => $item['qty'],
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockBefore - $item['qty'],
                        'reference_type' => get_class($sale),
                        'reference_id' => $sale->id,
                        'description' => 'Penjualan Kasir: ' . $sale->invoice_number,
                    ]);
                }

                $discountAmount = 0;
                if ($request->employee_id) {
                    $employee = User::find($request->employee_id);
                    if ($employee) {
                        $discountAmount = $totalBeforeDiscount * ($employee->employee_discount / 100);
                    }
                }

                $totalAmount = $totalBeforeDiscount - $discountAmount;

                $sale->update([
                    'total_amount' => $totalAmount,
                    'discount_amount' => $discountAmount,
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
                        }

                        $pointsEarned = floor($totalAmount / 50000);
                        if ($pointsEarned > 0) {
                            $customer->increment('points', $pointsEarned);
                        }
                    }
                } elseif ($request->payment_method === 'deposit') {
                    throw new \RuntimeException('Silakan pilih pelanggan terlebih dahulu untuk membayar dengan Saldo Deposit.');
                }

                // Resolve wallet
                $walletId = $request->wallet_id;
                if (!$walletId && !in_array($request->payment_method, ['employee_debt', 'deposit'], true)) {
                    $walletType = $request->payment_method === 'transfer' ? 'bank' : 'cash';
                    $walletId = Wallet::where('type', $walletType)->where('is_active', true)->value('id');
                }

                $paidAmountRecorded = in_array($request->payment_method, ['employee_debt', 'deposit'], true)
                    ? $totalAmount
                    : $request->paid_amount;

                $sale->payments()->create([
                    'wallet_id' => in_array($request->payment_method, ['employee_debt', 'deposit'], true) ? null : $walletId,
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

                if (!in_array($request->payment_method, ['employee_debt', 'deposit'], true)) {
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
}
