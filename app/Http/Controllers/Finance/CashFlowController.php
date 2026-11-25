<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\CashLedger;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CashFlowController extends Controller
{
    public function index(Request $request)
    {
        $now = Carbon::now('Asia/Pontianak');
        $startDate = $request->get('start_date', $now->copy()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', $now->toDateString());
        $walletId = $request->get('wallet_id');

        $baseQuery = CashLedger::whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate);

        if ($walletId) {
            $baseQuery->where('wallet_id', $walletId);
        }

        $entries = (clone $baseQuery)->with('wallet')->get();

        $categoryLabels = [
            'penjualan' => 'Penjualan Kasir (POS)',
            'pesanan_online' => 'Pesanan Online (Katalog)',
            'pembelian_supplier' => 'Pembayaran Nota Supplier',
            'retur_pembelian' => 'Refund Retur Supplier',
            'gaji' => 'Pembayaran Gaji / Payroll',
            'operasional' => 'Biaya & Kas Operasional',
            'transfer_kas' => 'Mutasi / Transfer Antar Dompet',
            'lainnya' => 'Lain-lain',
        ];

        $inflowsByCategory = $entries->where('type', 'in')
            ->groupBy(fn ($e) => $e->category ?: 'lainnya')
            ->map(fn ($rows, $key) => [
                'label' => $categoryLabels[$key] ?? ucfirst(str_replace('_', ' ', $key)),
                'count' => $rows->count(),
                'amount' => (float) $rows->sum('amount'),
            ])
            ->values();

        $outflowsByCategory = $entries->where('type', 'out')
            ->groupBy(fn ($e) => $e->category ?: 'operasional')
            ->map(fn ($rows, $key) => [
                'label' => $categoryLabels[$key] ?? ucfirst(str_replace('_', ' ', $key)),
                'count' => $rows->count(),
                'amount' => (float) $rows->sum('amount'),
            ])
            ->values();

        $totalIn = (float) $entries->where('type', 'in')->sum('amount');
        $totalOut = (float) $entries->where('type', 'out')->sum('amount');
        $netCashFlow = $totalIn - $totalOut;

        $wallets = Wallet::where('is_active', true)->get();

        return view('finance.cash_flow.index', compact(
            'startDate', 'endDate', 'walletId', 'wallets',
            'inflowsByCategory', 'outflowsByCategory',
            'totalIn', 'totalOut', 'netCashFlow'
        ));
    }
}
