<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function index()
    {
        $assets = Asset::latest()->paginate(12);
        $allAssets = Asset::all();

        $summary = [
            'total_acquisition' => $allAssets->sum('purchase_price'),
            'total_accumulated_depreciation' => $allAssets->sum(fn ($a) => $a->accumulated_depreciation),
            'total_book_value' => $allAssets->sum(fn ($a) => $a->book_value),
            'monthly_depreciation' => $allAssets->sum(fn ($a) => $a->monthly_depreciation),
        ];

        return view('fleet.assets.index', compact('assets', 'summary'));
    }

    public function create()
    {
        $seq = Asset::count() + 1;
        $suggestedCode = 'AST-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
        return view('fleet.assets.form', compact('suggestedCode'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:assets,code',
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'purchase_date' => 'required|date',
            'purchase_price' => 'required|numeric|min:0',
            'useful_life_years' => 'required|integer|min:1|max:50',
            'residual_value' => 'required|numeric|min:0|lte:purchase_price',
            'condition' => 'required|in:baik,perlu_perbaikan,rusak',
            'notes' => 'nullable|string',
        ]);

        Asset::create($request->all());

        return redirect()->route('fleet.assets.index')->with('success', 'Aset toko berhasil ditambahkan ke register.');
    }

    public function edit(Asset $asset)
    {
        return view('fleet.assets.form', compact('asset'));
    }

    public function update(Request $request, Asset $asset)
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:assets,code,' . $asset->id,
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'purchase_date' => 'required|date',
            'purchase_price' => 'required|numeric|min:0',
            'useful_life_years' => 'required|integer|min:1|max:50',
            'residual_value' => 'required|numeric|min:0|lte:purchase_price',
            'condition' => 'required|in:baik,perlu_perbaikan,rusak',
            'notes' => 'nullable|string',
        ]);

        $asset->update($request->all());

        return redirect()->route('fleet.assets.index')->with('success', 'Data aset berhasil diperbarui.');
    }

    public function destroy(Asset $asset)
    {
        $asset->delete();
        return redirect()->route('fleet.assets.index')->with('success', 'Aset berhasil dihapus.');
    }
}
