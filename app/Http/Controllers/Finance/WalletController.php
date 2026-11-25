<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\CashLedger;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    public function index()
    {
        $wallets = Wallet::orderBy('type')->orderBy('name')->get();
        $totalBalance = $wallets->sum(fn ($w) => $w->current_balance);

        return view('finance.wallets.index', compact('wallets', 'totalBalance'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:cash,bank,ewallet',
            'account_number' => 'nullable|string|max:100',
            'initial_balance' => 'required|numeric|min:0',
        ]);

        Wallet::create([
            'name' => $request->name,
            'type' => $request->type,
            'account_number' => $request->account_number,
            'initial_balance' => $request->initial_balance,
            'is_active' => true,
        ]);

        return back()->with('success', 'Dompet / Rekening baru berhasil ditambahkan.');
    }

    public function update(Request $request, Wallet $wallet)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:cash,bank,ewallet',
            'account_number' => 'nullable|string|max:100',
            'initial_balance' => 'required|numeric|min:0',
        ]);

        $wallet->update([
            'name' => $request->name,
            'type' => $request->type,
            'account_number' => $request->account_number,
            'initial_balance' => $request->initial_balance,
            'is_active' => $request->has('is_active'),
        ]);

        return back()->with('success', 'Data dompet berhasil diperbarui.');
    }

    public function transfer(Request $request)
    {
        $request->validate([
            'from_wallet_id' => 'required|exists:wallets,id|different:to_wallet_id',
            'to_wallet_id' => 'required|exists:wallets,id',
            'amount' => 'required|numeric|min:1',
            'date' => 'required|date',
            'notes' => 'nullable|string|max:255',
        ], [
            'from_wallet_id.different' => 'Dompet asal dan tujuan tidak boleh sama.',
        ]);

        $from = Wallet::findOrFail($request->from_wallet_id);
        $to = Wallet::findOrFail($request->to_wallet_id);
        $desc = $request->notes ? "Transfer Kas ({$from->name} -> {$to->name}): {$request->notes}" : "Transfer Kas ({$from->name} -> {$to->name})";

        DB::transaction(function () use ($request, $from, $to, $desc) {
            CashLedger::create([
                'user_id' => auth()->id(),
                'wallet_id' => $from->id,
                'type' => 'out',
                'category' => 'transfer_kas',
                'amount' => $request->amount,
                'description' => $desc,
                'date' => $request->date,
            ]);

            CashLedger::create([
                'user_id' => auth()->id(),
                'wallet_id' => $to->id,
                'type' => 'in',
                'category' => 'transfer_kas',
                'amount' => $request->amount,
                'description' => $desc,
                'date' => $request->date,
            ]);
        });

        return back()->with('success', "Transfer saldo dari {$from->name} ke {$to->name} berhasil dicatat.");
    }
}
