<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\CashLedger;
use App\Models\Wallet;
use Illuminate\Http\Request;

class CashLedgerController extends Controller
{
    public function index(Request $request)
    {
        $query = CashLedger::with(['user', 'wallet'])->latest('date')->latest('id');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('wallet_id')) {
            $query->where('wallet_id', $request->wallet_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        $totalIn = (clone $query)->where('type', 'in')->sum('amount');
        $totalOut = (clone $query)->where('type', 'out')->sum('amount');
        $balance = $totalIn - $totalOut;

        $ledgers = $query->paginate(15)->withQueryString();
        $wallets = Wallet::where('is_active', true)->get();

        return view('finance.cash_ledger.index', compact('ledgers', 'wallets', 'totalIn', 'totalOut', 'balance'));
    }

    public function create()
    {
        $wallets = Wallet::where('is_active', true)->get();
        return view('finance.cash_ledger.form', compact('wallets'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:in,out',
            'wallet_id' => 'nullable|exists:wallets,id',
            'category' => 'nullable|string|max:50',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:255',
            'date' => 'required|date',
        ]);

        CashLedger::create([
            'user_id' => auth()->id(),
            'wallet_id' => $request->wallet_id,
            'type' => $request->type,
            'category' => $request->category ?? 'operasional',
            'amount' => $request->amount,
            'description' => $request->description,
            'date' => $request->date,
        ]);

        return redirect()->route('finance.cash-ledger.index')->with('success', 'Catatan kas berhasil ditambahkan.');
    }

    public function destroy(CashLedger $cashLedger)
    {
        if ($cashLedger->reference_type !== null) {
            return back()->with('error', 'Transaksi kas otomatis dari POS/Payroll/Nota tidak boleh dihapus manual dari Buku Kas.');
        }

        $cashLedger->delete();
        return back()->with('success', 'Catatan kas manual berhasil dihapus.');
    }

    public function export(Request $request)
    {
        $query = CashLedger::with(['user', 'wallet'])->latest('date')->latest('id');
        if ($request->filled('type')) $query->where('type', $request->type);
        if ($request->filled('wallet_id')) $query->where('wallet_id', $request->wallet_id);
        if ($request->filled('date_from')) $query->whereDate('date', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->whereDate('date', '<=', $request->date_to);

        $rows = $query->limit(2000)->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, ['Tanggal', 'Keterangan', 'Kategori', 'Dompet', 'Tipe', 'Nominal', 'Petugas']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->date,
                    $r->description,
                    $r->category,
                    $r->wallet->name ?? 'Umum',
                    $r->type === 'in' ? 'MASUK' : 'KELUAR',
                    $r->amount,
                    $r->user->name ?? '-',
                ]);
            }
            fclose($out);
        }, 'Buku_Kas_' . date('Y-m-d') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
