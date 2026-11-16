<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\CashSession;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockLedger;
use App\Models\Payment;
use App\Models\CashLedger;
use App\Models\User;
use App\Models\EmployeeDebt; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Customer;

class PosController extends Controller
{
    public function index()
    {
        // Pastikan kasir sudah buka sesi
        $session = CashSession::where('user_id', auth()->id())->where('status', 'open')->first();
        if (!$session) {
            return redirect()->route('pos.session.index')->with('error', 'Silakan buka sesi kasir terlebih dahulu.');
        }

        // Ambil semua produk aktif untuk dilempar ke frontend (Alpine.js)
        $products = Product::where('is_active', true)->get();

        // Generate UUID untuk Idempotency Key (BR-08)
        $idempotencyKey = (string) Str::uuid();
        
        // Hitung uang laci
        $cashSales = Payment::whereHas('sale', function($q) use ($session) {
            $q->where('cash_session_id', $session->id)->where('status', 'completed');
        })->where('method', 'cash')->sum('amount');

        $expectedCash = $session->opening_balance + $cashSales;

        // AMBIL DATA KARYAWAN YANG PUNYA DISKON (DIPINDAHKAN KE SINI)
        $employees = User::where('role', 'karyawan')->where('employee_discount', '>', 0)->get();

        // AMBIL DATA PELANGGAN UNTUK LOYALTY
        $customers = Customer::where('is_active', true)->get();

        return view('pos.index', compact('products', 'session', 'idempotencyKey', 'cashSales', 'expectedCash', 'employees', 'customers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'idempotency_key' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'payment_method' => 'required|in:cash,transfer,employee_debt',
            'paid_amount' => 'required|numeric|min:0',
            'employee_id' => 'nullable|exists:users,id',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        // BR-08: Cek Idempotency (Cegah double submit)
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

                // 1. Buat Nota Penjualan (Total sementara 0)
                $sale = Sale::create([
                    'invoice_number' => $invoiceNumber,
                    'idempotency_key' => $request->idempotency_key,
                    'cash_session_id' => $session->id,
                    'user_id' => auth()->id(),
                    'total_amount' => 0, 
                    'discount_amount' => 0,
                    'status' => 'completed',
                ]);

                // 2. Simpan Item & Potong Stok (BR-01 & BR-03)
                foreach ($request->items as $item) {
                    $product = Product::find($item['product_id']);
                    $subtotal = $item['qty'] * $product->selling_price;
                    $totalBeforeDiscount += $subtotal;

                    // Simpan item dengan Snapshot Harga (BR-01)
                    $sale->items()->create([
                        'product_id' => $product->id,
                        'qty' => $item['qty'],
                        'price_snapshot' => $product->selling_price,
                        'subtotal' => $subtotal,
                    ]);

                    // Catat Stok Keluar ke Ledger (BR-03)
                    $stockBefore = $product->current_stock;
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

                // 3. Hitung Diskon Karyawan (Jika Ada)
                $discountAmount = 0;
                if ($request->employee_id) {
                    $employee = User::find($request->employee_id);
                    if ($employee) {
                        $discountAmount = $totalBeforeDiscount * ($employee->employee_discount / 100);
                    }
                }
                
                $totalAmount = $totalBeforeDiscount - $discountAmount;

                // Update Total Akhir & Diskon ke Nota
                $sale->update([
                    'total_amount' => $totalAmount,
                    'discount_amount' => $discountAmount
                ]);

                if ($request->customer_id) {
                    $customer = Customer::find($request->customer_id);
                    if ($customer) {
                        // Contoh aturan: Rp 50.000 = 1 Bintang
                        $pointsEarned = floor($totalAmount / 50000); 
                        $customer->increment('points', $pointsEarned);
                    }
                }

                // 4. Simpan Pembayaran
                $sale->payments()->create([
                    'method' => $request->payment_method,
                    'amount' => $request->paid_amount,
                ]);

                // 5. JIKA POTONG GAJI, CATAT KE UTANG KARYAWAN
                if ($request->payment_method === 'employee_debt' && $request->employee_id) {
                    EmployeeDebt::create([
                        'user_id' => $request->employee_id,
                        'sale_id' => $sale->id,
                        'amount' => $totalAmount,
                        'status' => 'unpaid',
                        'notes' => 'Belanja di POS: ' . $sale->invoice_number,
                    ]);
                }

                // 6. CATAT KE BUKU KAS (Hanya jika bukan potong gaji)
                if ($request->payment_method !== 'employee_debt') {
                    CashLedger::create([
                        'user_id' => auth()->id(),
                        'type' => 'in',
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
                'redirect' => route('pos.receipt', $sale->id) 
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function receipt(Sale $sale)
    {
        $sale->load(['items.product', 'payments', 'cashier']);
        return view('pos.receipt', compact('sale'));
    }
}