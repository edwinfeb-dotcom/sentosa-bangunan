<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::latest()->paginate(10);
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
            'is_active' => $request->has('is_active') ? true : false,
        ]);

        return redirect()->route('master.customers.index')->with('success', 'Pelanggan berhasil ditambahkan.');
    }

    public function edit(Customer $customer)
    {
        return view('master.customers.form', compact('customer'));
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
            'is_active' => $request->has('is_active') ? true : false,
        ]);

        return redirect()->route('master.customers.index')->with('success', 'Pelanggan berhasil diperbarui.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return redirect()->route('master.customers.index')->with('success', 'Pelanggan berhasil dihapus.');
    }
}