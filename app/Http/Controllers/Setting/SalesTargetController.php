<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\CashLedger;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalesTarget;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesTargetController extends Controller
{
    public function index(Request $request)
    {
        $now = Carbon::now('Asia/Pontianak');
        $activeMonth = (int) $request->get('month', $now->month);
        $activeYear = (int) $request->get('year', $now->year);

        $targets = SalesTarget::with('focusCategory')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get()
            ->map(function ($target) {
                $actual = (float) Sale::where('status', 'completed')
                    ->whereMonth('created_at', $target->month)
                    ->whereYear('created_at', $target->year)
                    ->sum('total_amount');

                $unitCostExpr = SaleItem::sqlUnitCostExpression();
                $actualProfit = (float) SaleItem::query()
                    ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                    ->join('products', 'sale_items.product_id', '=', 'products.id')
                    ->where('sales.status', 'completed')
                    ->whereMonth('sales.created_at', $target->month)
                    ->whereYear('sales.created_at', $target->year)
                    ->selectRaw("COALESCE(SUM(sale_items.subtotal - (({$unitCostExpr}) * sale_items.qty)), 0) as profit")
                    ->value('profit');

                $actualExpense = (float) CashLedger::where('type', 'out')
                    ->whereMonth('date', $target->month)
                    ->whereYear('date', $target->year)
                    ->sum('amount');

                $target->actual_amount = $actual;
                $target->actual_profit = $actualProfit;
                $target->actual_expense = $actualExpense;
                $target->percentage = $target->target_amount > 0
                    ? min(100, round(($actual / $target->target_amount) * 100, 1))
                    : 0;

                return $target;
            });

        $currentTarget = $targets->first(fn ($t) => (int) $t->month === $activeMonth && (int) $t->year === $activeYear);

        $currentOmzet = $currentTarget
            ? (float) $currentTarget->actual_amount
            : (float) Sale::where('status', 'completed')
                ->whereMonth('created_at', $activeMonth)
                ->whereYear('created_at', $activeYear)
                ->sum('total_amount');

        $unitCostExprActive = SaleItem::sqlUnitCostExpression();
        $currentProfit = $currentTarget
            ? (float) $currentTarget->actual_profit
            : (float) SaleItem::query()
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->join('products', 'sale_items.product_id', '=', 'products.id')
                ->where('sales.status', 'completed')
                ->whereMonth('sales.created_at', $activeMonth)
                ->whereYear('sales.created_at', $activeYear)
                ->selectRaw("COALESCE(SUM(sale_items.subtotal - (({$unitCostExprActive}) * sale_items.qty)), 0) as profit")
                ->value('profit');

        $currentExpense = $currentTarget
            ? (float) $currentTarget->actual_expense
            : (float) CashLedger::where('type', 'out')
                ->whereMonth('date', $activeMonth)
                ->whereYear('date', $activeYear)
                ->sum('amount');

        $workingDays = max(1, (int) ($currentTarget->working_days ?? 26));
        $targetAmount = (float) ($currentTarget->target_amount ?? 0);
        $targetProfit = (float) ($currentTarget->target_profit ?? 0);
        $maxExpense = (float) ($currentTarget->max_expense ?? 0);

        $dailyTargetBase = $targetAmount > 0 ? ($targetAmount / $workingDays) : 0;

        // Sisa hari kerja (Senin-Sabtu) bulan berjalan
        $remainingWorkDays = 0;
        if ($activeMonth === $now->month && $activeYear === $now->year) {
            $cursor = $now->copy()->startOfDay();
            $endOfMonth = $now->copy()->endOfMonth()->startOfDay();
            while ($cursor->lte($endOfMonth)) {
                if (!$cursor->isSunday()) {
                    $remainingWorkDays++;
                }
                $cursor->addDay();
            }
        }
        $remainingWorkDays = max(1, $remainingWorkDays);
        $remainingOmzet = max(0, $targetAmount - $currentOmzet);
        $dailyCatchupNeeded = $targetAmount > 0 ? ($remainingOmzet / $remainingWorkDays) : 0;

        // Top 5 Produk Terlaris (30 hari terakhir)
        $since30 = $now->copy()->subDays(30)->startOfDay();
        $fastMoving = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.status', 'completed')
            ->where('sales.created_at', '>=', $since30)
            ->groupBy('products.id', 'products.name', 'products.unit')
            ->select(
                'products.id',
                'products.name',
                'products.unit',
                DB::raw('SUM(sale_items.qty) as total_qty'),
                DB::raw('SUM(sale_items.subtotal) as total_omzet')
            )
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        // Top 5 Produk Mengendap (Stok > 0 namun tidak terjual dalam 30 hari terakhir)
        $soldProductIds30 = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.status', 'completed')
            ->where('sales.created_at', '>=', $since30)
            ->distinct()
            ->pluck('sale_items.product_id')
            ->all();

        $slowMoving = Product::withStock()
            ->where('is_active', true)
            ->when(!empty($soldProductIds30), fn ($q) => $q->whereNotIn('id', $soldProductIds30))
            ->get()
            ->filter(fn ($p) => $p->current_stock > 0)
            ->sortByDesc(fn ($p) => $p->current_stock * (float) $p->purchase_price)
            ->take(5)
            ->values();

        $categories = Category::where('is_active', true)->orderBy('name')->get();

        $strategyMetrics = [
            'month' => $activeMonth,
            'year' => $activeYear,
            'target_amount' => $targetAmount,
            'actual_omzet' => $currentOmzet,
            'omzet_pct' => $targetAmount > 0 ? min(100, round(($currentOmzet / $targetAmount) * 100, 1)) : 0,
            'target_profit' => $targetProfit,
            'actual_profit' => $currentProfit,
            'profit_pct' => $targetProfit > 0 ? min(100, round(($currentProfit / $targetProfit) * 100, 1)) : 0,
            'max_expense' => $maxExpense,
            'actual_expense' => $currentExpense,
            'expense_pct' => $maxExpense > 0 ? min(100, round(($currentExpense / $maxExpense) * 100, 1)) : 0,
            'working_days' => $workingDays,
            'remaining_work_days' => $remainingWorkDays,
            'daily_target_base' => $dailyTargetBase,
            'daily_catchup_needed' => $dailyCatchupNeeded,
        ];

        return view('setting.targets.index', compact(
            'targets',
            'currentTarget',
            'strategyMetrics',
            'fastMoving',
            'slowMoving',
            'categories'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
            'target_amount' => 'required|numeric|min:0',
            'target_profit' => 'nullable|numeric|min:0',
            'max_expense' => 'nullable|numeric|min:0',
            'working_days' => 'nullable|integer|min:1|max:31',
            'focus_category_id' => 'nullable|exists:categories,id',
            'notes' => 'nullable|string|max:255',
            'strategy_plan' => 'nullable|string|max:2000',
        ]);

        SalesTarget::updateOrCreate(
            ['month' => $request->month, 'year' => $request->year],
            [
                'target_amount' => $request->target_amount,
                'target_profit' => $request->input('target_profit', 0) ?? 0,
                'max_expense' => $request->input('max_expense', 0) ?? 0,
                'working_days' => $request->input('working_days', 26) ?? 26,
                'focus_category_id' => $request->filled('focus_category_id') ? $request->focus_category_id : null,
                'notes' => $request->notes,
                'strategy_plan' => $request->strategy_plan,
            ]
        );

        return back()->with('success', 'Strategi & target penjualan bulanan berhasil disimpan.');
    }

    public function destroy(SalesTarget $target)
    {
        $target->delete();
        return back()->with('success', 'Target penjualan berhasil dihapus.');
    }
}
