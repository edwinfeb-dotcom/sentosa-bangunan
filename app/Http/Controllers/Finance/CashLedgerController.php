<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\CashLedger;
use Illuminate\Http\Request;

class CashLedgerController extends Controller
{
    public function index()
    {
        $ledgers = CashLedger::with('user')->latest('date')->latest('id')->paginate(15);
        
        // Hitung total kas masuk dan keluar
        $totalIn = CashLedger::where('type', 'in')->sum('amount');
        $totalOut = CashLedger::where('type', 'out')->sum('amount');
        $balance = $totalIn - $totalOut;

        return view('finance.cash_ledger.index', compact('ledgers', 'totalIn', 'totalOut', 'balance'));
    }

    public function create()
    {
        return view('finance.cash_ledger.form');
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:in,out',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:255',
            'date' => 'required|date',
        ]);

        CashLedger::create([
            'user_id' => auth()->id(),
            'type' => $request->type,
            'amount' => $request->amount,
            'description' => $request->description,
            'date' => $request->date,
        ]);

        return redirect()->route('finance.cash-ledger.index')->with('success', 'Catatan kas berhasil ditambahkan.');
    }
}