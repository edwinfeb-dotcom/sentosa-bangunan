<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\CashLedger;
use App\Models\SupplierInvoice;
use App\Models\SupplierPayment;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierInvoiceController extends Controller
{
    public function index()
    {
        $invoices = SupplierInvoice::with('supplier')->latest()->paginate(15);
        $wallets = Wallet::where('is_active', true)->get();
        $totalDebt = SupplierInvoice::where('status', '!=', 'paid')->sum(DB::raw('total_amount - paid_amount'));

        return view('inventory.invoices.index', compact('invoices', 'wallets', 'totalDebt'));
    }

    public function pay(Request $request, SupplierInvoice $invoice)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1|max:' . $invoice->remaining_amount,
            'payment_date' => 'required|date',
            'wallet_id' => 'nullable|exists:wallets,id',
        ]);

        DB::transaction(function () use ($request, $invoice) {
            SupplierPayment::create([
                'supplier_invoice_id' => $invoice->id,
                'user_id' => auth()->id(),
                'amount' => $request->amount,
                'payment_date' => $request->payment_date,
                'notes' => $request->notes,
            ]);

            $newPaidAmount = $invoice->paid_amount + $request->amount;
            $status = $newPaidAmount >= $invoice->total_amount ? 'paid' : 'partial';

            $invoice->update([
                'paid_amount' => $newPaidAmount,
                'status' => $status,
            ]);

            $walletId = $request->wallet_id ?: Wallet::where('is_active', true)->value('id');

            CashLedger::create([
                'user_id' => auth()->id(),
                'wallet_id' => $walletId,
                'type' => 'out',
                'category' => 'pembelian_supplier',
                'amount' => $request->amount,
                'description' => 'Bayar Nota Supplier: ' . ($invoice->invoice_number ?? $invoice->supplier->name),
                'reference_type' => get_class($invoice),
                'reference_id' => $invoice->id,
                'date' => $request->payment_date,
            ]);
        });

        return back()->with('success', 'Pembayaran hutang supplier berhasil dicatat.');
    }
}
