<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\CashLedger;
use App\Models\Product;
use App\Models\DailyReport;
use App\Models\Supplier;
use App\Models\PurchaseOrder;
use App\Models\Payment;
use App\Models\Customer; // <-- Tambahan untuk Pelanggan
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ControlCenterController extends Controller
{
    public function index(Request $request)
    {
        // 1. Logika Filter Waktu
        $filter = $request->get('filter', 'today'); // default: hari ini
        $queryDate = function ($query, $column = 'created_at') use ($filter) {
            if ($filter === 'today') {
                return $query->whereDate($column, Carbon::today('Asia/Pontianak'));
            } elseif ($filter === 'this_month') {
                return $query->whereMonth($column, Carbon::now('Asia/Pontianak')->month)
                             ->whereYear($column, Carbon::now('Asia/Pontianak')->year);
            }
            return $query; // 'all' (semua periode)
        };

        // 2. Ringkasan Penjualan & Transaksi
        $totalTransactions = $queryDate(Sale::where('status', 'completed'))->count();
        $omzet = $queryDate(Sale::where('status', 'completed'))->sum('total_amount');
        
        // Hari tercatat (tanggal unik ada transaksi)
        $recordedDays = $queryDate(Sale::where('status', 'completed'))
                        ->select(DB::raw('DATE(created_at) as date'))
                        ->distinct()->count();

        // 3. Laporan Harian Terverifikasi
        $verifiedReports = $queryDate(DailyReport::where('status', 'verified'), 'date')->count();

        // 4. Keuangan (Margin & Pengeluaran)
        $totalMargin = 0;
        $sales = $queryDate(Sale::with('items.product')->where('status', 'completed'))->get();
        foreach($sales as $sale) {
            foreach($sale->items as $item) {
                if($item->product) {
                    $totalMargin += ($item->price_snapshot - $item->product->purchase_price) * $item->qty;
                }
            }
        }
        
        $totalExpenses = $queryDate(CashLedger::where('type', 'out'), 'date')->sum('amount');
        $netMargin = $totalMargin - $totalExpenses;

        // 5. Kas Tunai & Bank
        $kasTunai = $queryDate(Payment::where('method', 'cash'))->sum('amount');
        $kasBank = $queryDate(Payment::where('method', 'transfer'))->sum('amount');

        // 6. Informasi Barang & Persediaan
        $totalSuppliers = Supplier::count();
        $totalProducts = Product::count();
        $belanjaHariIni = PurchaseOrder::whereDate('created_at', Carbon::today('Asia/Pontianak'))->sum('total_amount');

        // 7. Alert Stok Kritis
        $semuaProduk = Product::where('is_active', true)->get();
        $stokKritis = $semuaProduk->filter(function ($product) {
            return $product->current_stock <= $product->stock_minimum;
        })->take(5);

        // 8. Pelanggan Favorit (Bintang Gold)
        $topCustomers = Customer::orderBy('points', 'desc')->take(3)->get();

        return view('dashboard', compact(
            'filter', 'totalTransactions', 'omzet', 'recordedDays', 'verifiedReports',
            'totalMargin', 'totalExpenses', 'netMargin', 'kasTunai', 'kasBank',
            'totalSuppliers', 'totalProducts', 'belanjaHariIni', 'stokKritis', 'topCustomers'
        ));
    }
}