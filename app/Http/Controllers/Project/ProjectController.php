<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Imports\ProjectsImport;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectMaterial;
use App\Models\StockLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::with('customer')->withSum('materials', 'subtotal')->latest()->paginate(10);
        return view('project.projects.index', compact('projects'));
    }

    public function create()
    {
        $customers = Customer::where('is_active', true)
            ->orderByRaw("CASE WHEN type = 'kontraktor' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();
        return view('project.projects.form', compact('customers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'name' => 'required|string|max:255',
            'budget' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:active,completed,cancelled',
        ]);

        Project::create([
            'customer_id' => $request->filled('customer_id') ? $request->customer_id : null,
            'name' => $request->name,
            'budget' => $request->budget,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => $request->status,
        ]);

        return redirect()->route('project.projects.index')->with('success', 'Proyek berhasil ditambahkan.');
    }

    public function show(Project $project)
    {
        $project->load(['customer', 'materials.product', 'materials.user', 'payments.user', 'payments.wallet']);
        $products = Product::withStock()->where('is_active', true)->orderBy('name')->get();
        $wallets = \App\Models\Wallet::where('is_active', true)->orderBy('type')->orderBy('name')->get();

        $netTakenMaterials = $project->materials
            ->groupBy('product_id')
            ->map(function ($rows) {
                $first = $rows->first();
                return [
                    'product_id' => $first->product_id,
                    'product_name' => $first->product->name ?? 'Produk',
                    'unit' => $first->product->unit ?? '',
                    'net_qty' => (int) $rows->sum('qty'),
                    'last_price' => (float) $first->unit_price,
                ];
            })
            ->filter(fn ($row) => $row['net_qty'] > 0)
            ->values();

        return view('project.projects.show', compact('project', 'products', 'netTakenMaterials', 'wallets'));
    }

    public function recordPayment(Request $request, Project $project)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_date' => 'required|date',
            'wallet_id' => 'nullable|exists:wallets,id',
            'notes' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($request, $project) {
            $walletId = $request->wallet_id ?: \App\Models\Wallet::where('is_active', true)->value('id');
            $payment = \App\Models\ProjectPayment::create([
                'project_id' => $project->id,
                'user_id' => auth()->id(),
                'wallet_id' => $walletId,
                'amount' => $request->amount,
                'payment_date' => $request->payment_date,
                'notes' => $request->notes,
            ]);

            $project->increment('paid_amount', $request->amount);

            \App\Models\CashLedger::create([
                'user_id' => auth()->id(),
                'wallet_id' => $walletId,
                'type' => 'in',
                'category' => 'pembayaran_proyek',
                'amount' => $request->amount,
                'description' => "Pembayaran Proyek: {$project->name}" . ($request->notes ? " ({$request->notes})" : ''),
                'reference_type' => \App\Models\ProjectPayment::class,
                'reference_id' => $payment->id,
                'date' => $request->payment_date,
            ]);
        });

        return back()->with('success', 'Pembayaran / termin proyek berhasil dicatat ke Buku Kas.');
    }

    public function printInvoice(Project $project)
    {
        $project->load(['customer', 'materials.product', 'payments.wallet']);
        return view('project.projects.invoice', compact('project'));
    }

    public function storeMaterial(Request $request, Project $project)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'used_date' => 'required|date',
            'notes' => 'nullable|string|max:255',
        ]);

        try {
            DB::transaction(function () use ($request, $project) {
                $product = Product::withStock()->findOrFail($request->product_id);
                $stockBefore = $product->current_stock;
                if ($stockBefore < $request->qty) {
                    throw new \RuntimeException("Stok {$product->name} tidak mencukupi (tersisa {$stockBefore} {$product->unit}).");
                }

                $subtotal = $request->qty * $request->unit_price;

                $material = ProjectMaterial::create([
                    'project_id' => $project->id,
                    'product_id' => $product->id,
                    'user_id' => auth()->id(),
                    'qty' => $request->qty,
                    'unit_price' => $request->unit_price,
                    'subtotal' => $subtotal,
                    'used_date' => $request->used_date,
                    'notes' => $request->notes,
                ]);

                StockLedger::create([
                    'product_id' => $product->id,
                    'user_id' => auth()->id(),
                    'type' => 'out',
                    'qty' => $request->qty,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockBefore - $request->qty,
                    'reference_type' => ProjectMaterial::class,
                    'reference_id' => $material->id,
                    'description' => "Realisasi Material Proyek: {$project->name}",
                ]);
            });

            return back()->with('success', 'Penggunaan material proyek berhasil dicatat dan stok otomatis terpotong.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function returnMaterial(Request $request, Project $project)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|integer|min:1',
            'used_date' => 'required|date',
            'notes' => 'nullable|string|max:255',
        ]);

        try {
            DB::transaction(function () use ($request, $project) {
                $product = Product::withStock()->findOrFail($request->product_id);
                $netTaken = (int) ProjectMaterial::where('project_id', $project->id)
                    ->where('product_id', $product->id)
                    ->sum('qty');

                if ($netTaken < $request->qty) {
                    throw new \RuntimeException("Jumlah retur melebihi total material terpakai pada proyek ini ({$netTaken} {$product->unit}).");
                }

                $lastPrice = (float) (ProjectMaterial::where('project_id', $project->id)
                    ->where('product_id', $product->id)
                    ->where('qty', '>', 0)
                    ->latest('id')
                    ->value('unit_price') ?? $product->selling_price);

                $returnQty = (int) $request->qty;
                $material = ProjectMaterial::create([
                    'project_id' => $project->id,
                    'product_id' => $product->id,
                    'user_id' => auth()->id(),
                    'qty' => -$returnQty,
                    'unit_price' => $lastPrice,
                    'subtotal' => -($returnQty * $lastPrice),
                    'used_date' => $request->used_date,
                    'notes' => 'Retur Sisa Material' . ($request->notes ? ': ' . $request->notes : ''),
                ]);

                $stockBefore = $product->current_stock;
                StockLedger::create([
                    'product_id' => $product->id,
                    'user_id' => auth()->id(),
                    'type' => 'in',
                    'qty' => $returnQty,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockBefore + $returnQty,
                    'reference_type' => ProjectMaterial::class,
                    'reference_id' => $material->id,
                    'description' => "Retur Sisa Material Proyek: {$project->name}",
                ]);
            });

            return back()->with('success', 'Retur sisa material proyek berhasil dicatat dan stok toko kembali bertambah.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function edit(Project $project)
    {
        $customers = Customer::where('is_active', true)
            ->orderByRaw("CASE WHEN type = 'kontraktor' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();
        return view('project.projects.form', compact('project', 'customers'));
    }

    public function update(Request $request, Project $project)
    {
        $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'name' => 'required|string|max:255',
            'budget' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:active,completed,cancelled',
        ]);

        $project->update([
            'customer_id' => $request->filled('customer_id') ? $request->customer_id : null,
            'name' => $request->name,
            'budget' => $request->budget,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => $request->status,
        ]);

        return redirect()->route('project.projects.index')->with('success', 'Proyek berhasil diperbarui.');
    }

    public function destroy(Project $project)
    {
        $project->delete();
        return redirect()->route('project.projects.index')->with('success', 'Proyek berhasil dihapus.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,xlsm,csv,txt|max:25600',
        ]);

        try {
            @set_time_limit(180);
            $importer = new ProjectsImport();
            $ext = strtolower($request->file('file')->getClientOriginalExtension());

            DB::transaction(function () use ($importer, $request, $ext) {
                $importer->import($request->file('file')->getRealPath(), $ext);
            });

            if ($importer->createdCount === 0 && $importer->updatedCount === 0) {
                return back()->with('error', 'Tidak ada data proyek yang terbaca. Pastikan sheet MASTER memiliki kolom NAMA / SUMBER / DEPO.');
            }

            return back()->with('success', "Import proyek selesai: {$importer->createdCount} proyek baru, {$importer->updatedCount} diperbarui.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal mengimport Excel proyek: ' . $e->getMessage());
        }
    }
}
