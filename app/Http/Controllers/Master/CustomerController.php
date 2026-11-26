<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Imports\CustomersImport;
use App\Models\CashLedger;
use App\Models\Customer;
use App\Models\CustomerDebtLedger;
use App\Models\DepositLedger;
use App\Models\PublicOrder;
use App\Models\Sale;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::latest();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->boolean('has_debt')) {
            $query->where('debt_balance', '>', 0);
        }

        $totalCustomersDebt = (float) Customer::sum('debt_balance');
        $totalCustomersDeposit = (float) Customer::sum('deposit_balance');

        $customers = $query->paginate(10)->withQueryString();
        return view('master.customers.index', compact('customers', 'totalCustomersDebt', 'totalCustomersDeposit'));
    }

    public function create()
    {
        return view('master.customers.form');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:reguler,toko,kontraktor',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        Customer::create([
            'name' => $request->name,
            'type' => $request->type,
            'phone' => $request->phone,
            'address' => $request->address,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('master.customers.index')->with('success', 'Pelanggan berhasil ditambahkan.');
    }

    public function edit(Customer $customer)
    {
        return view('master.customers.form', compact('customer'));
    }

    public function show(Customer $customer)
    {
        $depositLedgers = DepositLedger::with('user')
            ->where('customer_id', $customer->id)
            ->latest()
            ->paginate(10, ['*'], 'deposit_page');

        $debtLedgers = CustomerDebtLedger::with('user')
            ->where('customer_id', $customer->id)
            ->latest()
            ->paginate(10, ['*'], 'debt_page');

        $sales = Sale::with(['cashier', 'payments'])
            ->where('customer_id', $customer->id)
            ->latest()
            ->paginate(10, ['*'], 'sales_page');

        $onlineOrders = collect();
        if ($customer->phone) {
            $onlineOrders = PublicOrder::where('phone', $customer->phone)
                ->latest()
                ->limit(10)
                ->get();
        }

        $totalSpentPos = (float) Sale::where('customer_id', $customer->id)
            ->where('status', 'completed')
            ->sum('total_amount');

        $wallets = Wallet::where('is_active', true)->orderBy('type')->orderBy('name')->get();

        return view('master.customers.show', compact(
            'customer',
            'depositLedgers',
            'debtLedgers',
            'sales',
            'onlineOrders',
            'totalSpentPos',
            'wallets'
        ));
    }

    public function update(Request $request, Customer $customer)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:reguler,toko,kontraktor',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        $customer->update([
            'name' => $request->name,
            'type' => $request->type,
            'phone' => $request->phone,
            'address' => $request->address,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('master.customers.index')->with('success', 'Pelanggan berhasil diperbarui.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return redirect()->route('master.customers.index')->with('success', 'Pelanggan berhasil dihapus.');
    }

    public function addDeposit(Request $request, Customer $customer)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($request, $customer) {
            $customer->increment('deposit_balance', $request->amount);

            DepositLedger::create([
                'customer_id' => $customer->id,
                'user_id' => auth()->id(),
                'type' => 'in',
                'amount' => $request->amount,
                'description' => $request->description ?? 'Top Up Deposit',
            ]);
        });

        return back()->with('success', 'Saldo deposit berhasil ditambahkan.');
    }

    public function recordDebt(Request $request, Customer $customer)
    {
        $request->validate([
            'action_type' => 'required|in:in,out',
            'amount' => 'required|numeric|min:1',
            'wallet_id' => 'nullable|exists:wallets,id',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            DB::transaction(function () use ($request, $customer) {
                $locked = Customer::lockForUpdate()->findOrFail($customer->id);
                $amount = (float) $request->amount;

                if ($request->action_type === 'out') {
                    if ((float) $locked->debt_balance < $amount) {
                        throw new \RuntimeException('Nominal pelunasan melebihi sisa bon pelanggan.');
                    }
                    $locked->decrement('debt_balance', $amount);

                    CashLedger::create([
                        'user_id' => auth()->id(),
                        'wallet_id' => $request->wallet_id ?: Wallet::where('is_active', true)->value('id'),
                        'type' => 'in',
                        'category' => 'pelunasan_bon_pelanggan',
                        'amount' => $amount,
                        'description' => ($request->description ?: 'Pelunasan Bon') . ' — ' . $locked->name,
                        'reference_type' => Customer::class,
                        'reference_id' => $locked->id,
                        'date' => date('Y-m-d'),
                    ]);
                } else {
                    $locked->increment('debt_balance', $amount);
                }

                CustomerDebtLedger::create([
                    'customer_id' => $locked->id,
                    'user_id' => auth()->id(),
                    'type' => $request->action_type,
                    'amount' => $amount,
                    'description' => $request->description ?: ($request->action_type === 'out' ? 'Pembayaran / Pelunasan Bon' : 'Tambah Catatan Bon'),
                ]);
            });

            return back()->with('success', $request->action_type === 'out' ? 'Pelunasan bon pelanggan berhasil dicatat & masuk kas.' : 'Catatan bon pelanggan berhasil ditambahkan.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,xlsm,csv,txt|max:25600',
        ]);

        try {
            @set_time_limit(180);
            $importer = new CustomersImport();
            $ext = strtolower($request->file('file')->getClientOriginalExtension());
            DB::transaction(function () use ($importer, $request, $ext) {
                $importer->import($request->file('file')->getRealPath(), $ext);
            });

            if ($importer->createdCount === 0 && $importer->updatedCount === 0) {
                return back()->with('error', 'Tidak ada data pelanggan yang terbaca. Pastikan file memiliki kolom Nama Pelanggan / NAMA.');
            }

            return back()->with('success', "Import pelanggan selesai: {$importer->createdCount} pelanggan baru, {$importer->updatedCount} diperbarui.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal mengimport Excel pelanggan: ' . $e->getMessage());
        }
    }
}
