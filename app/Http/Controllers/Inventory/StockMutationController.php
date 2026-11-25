<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockMutationController extends Controller
{
    public function index(Request $request)
    {
        $query = StockLedger::with(['product.category', 'user'])->latest('id');

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $ledgers = $query->paginate(20)->withQueryString();

        $allProducts = Product::withStock()->where('is_active', true)->orderBy('name')->get();
        $criticalProducts = $allProducts->filter(fn ($p) => $p->current_stock <= $p->stock_minimum)->values();

        $summary = [
            'total_products' => $allProducts->count(),
            'critical_count' => $criticalProducts->count(),
            'total_in' => (int) StockLedger::where('type', 'in')
                ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->product_id))
                ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
                ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date_to))
                ->sum('qty'),
            'total_out' => (int) StockLedger::where('type', 'out')
                ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->product_id))
                ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
                ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date_to))
                ->sum('qty'),
        ];

        return view('inventory.stock.index', compact('ledgers', 'allProducts', 'criticalProducts', 'summary'));
    }

    public function opname(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'actual_stock' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:255',
        ]);

        $message = DB::transaction(function () use ($request) {
            $product = Product::withStock()->findOrFail($request->product_id);
            $currentStock = (int) $product->current_stock;
            $actualStock = (int) $request->actual_stock;
            $diff = $actualStock - $currentStock;

            if ($diff === 0) {
                return "Stok fisik {$product->name} sudah sesuai sistem ({$currentStock} {$product->unit}).";
            }

            $noteText = trim((string) $request->notes);
            $desc = 'Stock Opname' . ($noteText !== '' ? ': ' . $noteText : ' (Penyesuaian Fisik)');

            StockLedger::create([
                'product_id' => $product->id,
                'user_id' => auth()->id(),
                'type' => $diff > 0 ? 'in' : 'out',
                'qty' => abs($diff),
                'stock_before' => $currentStock,
                'stock_after' => $actualStock,
                'reference_type' => 'stock_opname',
                'reference_id' => $product->id,
                'description' => $desc,
            ]);

            $sign = $diff > 0 ? '+' : '-';
            return "Stock Opname {$product->name} berhasil ({$currentStock} → {$actualStock} {$product->unit}, selisih {$sign}" . abs($diff) . ').';
        });

        return back()->with('success', $message);
    }
}
