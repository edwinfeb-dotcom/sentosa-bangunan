<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DepositLedger;
use App\Models\PublicOrder;
use App\Models\Sale;
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

        $customers = $query->paginate(10)->withQueryString();
        return view('master.customers.index', compact('customers'));
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

        return view('master.customers.show', compact(
            'customer',
            'depositLedgers',
            'sales',
            'onlineOrders',
            'totalSpentPos'
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
}
