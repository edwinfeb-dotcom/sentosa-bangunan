<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\CashSession;
use Illuminate\Http\Request;

class CashSessionController extends Controller
{
    public function index()
    {
        // Cek apakah kasir ini sedang punya sesi yang terbuka
        $activeSession = CashSession::where('user_id', auth()->id())
                                    ->where('status', 'open')
                                    ->first();

        if ($activeSession) {
            // Jika sudah buka, arahkan ke halaman POS
            return redirect()->route('pos.index');
        }

        return view('pos.session.index');
    }

    public function open(Request $request)
    {
        $request->validate([
            'opening_balance' => 'required|numeric|min:0',
        ]);

        CashSession::create([
            'user_id' => auth()->id(),
            'start_time' => now(),
            'opening_balance' => $request->opening_balance,
            'status' => 'open',
        ]);

        return redirect()->route('pos.index')->with('success', 'Sesi Kasir berhasil dibuka. Selamat bekerja!');
    }

        public function close(Request $request)
    {
        $request->validate([
            'actual_closing_balance' => 'required|numeric|min:0',
        ]);

        $session = CashSession::where('user_id', auth()->id())->where('status', 'open')->first();
        
        if (!$session) {
            return back()->with('error', 'Tidak ada sesi kasir yang terbuka.');
        }

        // 1. Hitung total penjualan TUNAI (cash) di sesi ini
        $cashSales = \App\Models\Payment::whereHas('sale', function($q) use ($session) {
            $q->where('cash_session_id', $session->id)->where('status', 'completed');
        })->where('method', 'cash')->sum('amount');

        // 2. Hitung mutasi kas laci manual (kas kecil / pelunasan masuk dompet tunai selama shift)
        $cashWalletIds = \App\Models\Wallet::where('type', 'cash')->pluck('id');
        $shiftPettyCashNet = (float) \App\Models\CashLedger::whereIn('wallet_id', $cashWalletIds)
            ->whereNull('reference_type')
            ->where('created_at', '>=', $session->start_time)
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'in' THEN amount ELSE -amount END), 0) as net")
            ->value('net');

        // 3. Uang yang seharusnya ada di laci = Saldo Awal + Penjualan Tunai + Mutasi Laci Manual
        $expectedBalance = $session->opening_balance + $cashSales + $shiftPettyCashNet;

        // 3. Selisih = Uang Fisik - Uang Sistem
        $difference = $request->actual_closing_balance - $expectedBalance;

        // 4. Update sesi menjadi tutup
        $session->update([
            'end_time' => now(),
            'expected_closing_balance' => $expectedBalance,
            'actual_closing_balance' => $request->actual_closing_balance,
            'difference' => $difference,
            'status' => 'closed',
        ]);

        return redirect()->route('dashboard')->with('success', 'Kasir berhasil ditutup. Selisih: Rp ' . number_format($difference, 0, ',', '.'));
    }
}