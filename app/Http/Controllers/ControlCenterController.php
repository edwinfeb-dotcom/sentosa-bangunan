<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\CashLedger;
use App\Models\Product;
use App\Models\DailyReport;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ControlCenterController extends Controller
{
    public function index()
    {
        // 1. Ringkasan Omzet Hari Ini
        $today = Carbon::today('Asia/Pontianak')->format('Y-m-d');
        $omzetHariIni = Sale::whereDate('created_at', $today)->where('status', 'completed')->sum('total_amount');

        // 2. Total Kas (Buku Kas)
        $kasMasuk = CashLedger::where('type', 'in')->sum('amount');
        $kasKeluar = CashLedger::where('type', 'out')->sum('amount');
        $saldoKas = $kasMasuk - $kasKeluar;

        // 3. Stok Kritis (Stok <= Minimal Stok)
        // Karena stok dihitung dari relasi (BR-03), kita gunakan query manual atau filter di PHP
        $semuaProduk = Product::where('is_active', true)->get();
        $stokKritis = $semuaProduk->filter(function ($product) {
            return $product->current_stock <= $product->stock_minimum;
        })->take(5); // Ambil 5 teratas

        // 4. Laporan Harian Menunggu Verifikasi
        $laporanPending = DailyReport::where('status', 'submitted')->count();

        return view('dashboard', compact('omzetHariIni', 'saldoKas', 'stokKritis', 'laporanPending'));
    }
}