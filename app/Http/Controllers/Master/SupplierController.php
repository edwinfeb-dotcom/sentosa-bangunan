<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::latest()->paginate(10);
        return view('master.suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('master.suppliers.form');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        Supplier::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'address' => $request->address,
            'is_active' => $request->has('is_active') ? true : false,
        ]);

        return redirect()->route('master.suppliers.index')->with('success', 'Supplier berhasil ditambahkan.');
    }

    public function show(Supplier $supplier)
    {
        $products = \App\Models\Product::withStock()
            ->where('supplier_id', $supplier->id)
            ->orderBy('name')
            ->get();

        $purchaseOrders = \App\Models\PurchaseOrder::where('supplier_id', $supplier->id)
            ->latest()
            ->limit(15)
            ->get();

        $invoices = \App\Models\SupplierInvoice::where('supplier_id', $supplier->id)
            ->latest()
            ->limit(15)
            ->get();

        $totalDebt = (float) \App\Models\SupplierInvoice::where('supplier_id', $supplier->id)
            ->where('status', '!=', 'paid')
            ->sum(\Illuminate\Support\Facades\DB::raw('total_amount - paid_amount'));

        return view('master.suppliers.show', compact('supplier', 'products', 'purchaseOrders', 'invoices', 'totalDebt'));
    }

    public function edit(Supplier $supplier)
    {
        return view('master.suppliers.form', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        $supplier->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'address' => $request->address,
            'is_active' => $request->has('is_active') ? true : false,
        ]);

        return redirect()->route('master.suppliers.index')->with('success', 'Supplier berhasil diperbarui.');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return redirect()->route('master.suppliers.index')->with('success', 'Supplier berhasil dihapus.');
    }
}