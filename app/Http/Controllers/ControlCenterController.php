<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\CashLedger;
use App\Models\CashSession;
use App\Models\Customer;
use App\Models\DailyReport;
use App\Models\Payment;
use App\Models\Product;
use App\Models\PublicOrder;
use App\Models\PurchaseOrder;
use App\Models\Sale;
use App\Models\SalesTarget;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\TransportJob;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ControlCenterController extends Controller
{
    public function index(Request $request)
    {
        if (auth()->user()->role === 'kasir') {
            return $this->kasirDashboard();
        }
        if (auth()->user()->role === 'gudang') {
            return $this->gudangDashboard();
        }
        if (auth()->user()->role === 'driver') {
            return $this->driverDashboard();
        }
        if (auth()->user()->role === 'karyawan') {
            return redirect()->route('hr.attendance.index');
        }

        $now = Carbon::now('Asia/Pontianak');
        $filter = $request->get('filter', 'today');

        $queryDate = function ($query, $column = 'created_at') use ($filter, $now) {
            if ($filter === 'today') {
                return $query->whereDate($column, $now->toDateString());
            } elseif ($filter === 'this_month') {
                return $query->whereMonth($column, $now->month)
                             ->whereYear($column, $now->year);
            }
            return $query;
        };

        $totalTransactions = $queryDate(Sale::where('status', 'completed'))->count();
        $omzet = (float) $queryDate(Sale::where('status', 'completed'))->sum('total_amount');

        $recordedDays = $queryDate(Sale::where('status', 'completed'))
            ->select(DB::raw('DATE(created_at) as date'))
            ->distinct()
            ->count();

        $verifiedReports = $queryDate(DailyReport::where('status', 'verified'), 'date')->count();

        $totalMargin = 0;
        $sales = $queryDate(Sale::with('items.product')->where('status', 'completed'))->get();
        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                if ($item->product) {
                    $totalMargin += ((float) $item->subtotal) - $item->hpp_total;
                }
            }
            $totalMargin -= (float) ($sale->discount_amount ?? 0) + (float) ($sale->manual_discount ?? 0);
        }

        $totalExpenses = (float) $queryDate(CashLedger::where('type', 'out'), 'date')->sum('amount');
        $netMargin = $totalMargin - $totalExpenses;

        $kasTunai = (float) $queryDate(Payment::where('method', 'cash'))->sum('amount');
        $kasBank = (float) $queryDate(Payment::where('method', 'transfer'))->sum('amount');

        $salesTarget = SalesTarget::with('focusCategory')->where('month', $now->month)->where('year', $now->year)->first();
        $targetAmount = (float) ($salesTarget->target_amount ?? 0);
        $monthlyOmzet = (float) Sale::where('status', 'completed')
            ->whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->sum('total_amount');
        $targetPercentage = $targetAmount > 0 ? min(100, round(($monthlyOmzet / $targetAmount) * 100, 1)) : 0;
        $dailyTargetNeeded = $targetAmount > 0
            ? round($targetAmount / max(1, (int) ($salesTarget->working_days ?? 26)))
            : 0;

        $wallets = Wallet::where('is_active', true)->get();

        $totalSuppliers = Supplier::count();
        $totalProducts = Product::count();
        $belanjaHariIni = (float) PurchaseOrder::whereDate('created_at', $now->toDateString())->sum('total_amount');

        $stokKritis = Product::withStock()
            ->where('is_active', true)
            ->whereRaw("COALESCE((SELECT SUM(CASE WHEN type = 'in' THEN qty ELSE -qty END) FROM stock_ledgers WHERE stock_ledgers.product_id = products.id), 0) <= products.stock_minimum")
            ->orderBy('name')
            ->limit(5)
            ->get();

        $pendingOnlineOrders = PublicOrder::where('status', 'pending')->count();
        $topCustomers = Customer::orderBy('points', 'desc')->take(3)->get();

        $salesTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = $now->copy()->subDays($i);
            $dayTotal = (float) Sale::where('status', 'completed')
                ->whereDate('created_at', $day->toDateString())
                ->sum('total_amount');
            $salesTrend[] = [
                'label' => $day->translatedFormat('D'),
                'date' => $day->format('d/m'),
                'total' => $dayTotal,
            ];
        }
        $maxTrend = max(1, collect($salesTrend)->max('total'));

        return view('dashboard', compact(
            'filter', 'totalTransactions', 'omzet', 'recordedDays', 'verifiedReports',
            'totalMargin', 'totalExpenses', 'netMargin', 'kasTunai', 'kasBank',
            'totalSuppliers', 'totalProducts', 'belanjaHariIni', 'stokKritis', 'topCustomers',
            'targetAmount', 'monthlyOmzet', 'targetPercentage', 'salesTarget', 'dailyTargetNeeded', 'wallets',
            'pendingOnlineOrders', 'salesTrend', 'maxTrend'
        ));
    }

    private function kasirDashboard()
    {
        $now = Carbon::now('Asia/Pontianak');
        $user = auth()->user();

        $activeSession = CashSession::where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        $shiftCashSales = 0;
        $shiftTransactions = 0;
        $expectedDrawer = 0;

        if ($activeSession) {
            $shiftTransactions = Sale::where('cash_session_id', $activeSession->id)
                ->where('status', 'completed')
                ->count();
            $shiftCashSales = (float) Payment::whereHas('sale', function ($q) use ($activeSession) {
                $q->where('cash_session_id', $activeSession->id)->where('status', 'completed');
            })->where('method', 'cash')->sum('amount');
            $expectedDrawer = (float) $activeSession->opening_balance + $shiftCashSales;
        }

        $todaySales = Sale::where('status', 'completed')
            ->whereDate('created_at', $now->toDateString());
        $todayOmzet = (float) (clone $todaySales)->sum('total_amount');
        $todayCount = (clone $todaySales)->count();

        $pendingOnlineOrders = PublicOrder::where('status', 'pending')->count();
        $unpaidSupplierCount = SupplierInvoice::where('status', '!=', 'paid')->count();

        $recentSales = Sale::with(['payments', 'customer'])
            ->where('status', 'completed')
            ->whereDate('created_at', $now->toDateString())
            ->latest()
            ->take(8)
            ->get();

        $attendanceToday = Attendance::where('user_id', $user->id)
            ->where('date', $now->toDateString())
            ->first();

        $stokKritis = Product::withStock()
            ->where('is_active', true)
            ->whereRaw("COALESCE((SELECT SUM(CASE WHEN type = 'in' THEN qty ELSE -qty END) FROM stock_ledgers WHERE stock_ledgers.product_id = products.id), 0) <= products.stock_minimum")
            ->orderBy('name')
            ->limit(6)
            ->get();

        return view('kasir.dashboard', compact(
            'activeSession', 'shiftCashSales', 'shiftTransactions', 'expectedDrawer',
            'todayOmzet', 'todayCount', 'pendingOnlineOrders', 'unpaidSupplierCount',
            'recentSales', 'attendanceToday', 'stokKritis'
        ));
    }

    private function gudangDashboard()
    {
        $now = Carbon::now('Asia/Pontianak');
        $totalProducts = Product::where('is_active', true)->count();
        $orderedPoCount = PurchaseOrder::where('status', 'ordered')->count();
        $pendingOnlineOrders = PublicOrder::where('status', 'pending')->count();
        $stokKritis = Product::withStock()
            ->where('is_active', true)
            ->whereRaw("COALESCE((SELECT SUM(CASE WHEN type = 'in' THEN qty ELSE -qty END) FROM stock_ledgers WHERE stock_ledgers.product_id = products.id), 0) <= products.stock_minimum")
            ->orderBy('name')
            ->limit(10)
            ->get();
        $pendingPOs = PurchaseOrder::with('supplier')->where('status', 'ordered')->latest()->limit(6)->get();
        $attendanceToday = Attendance::where('user_id', auth()->id())->where('date', $now->toDateString())->first();

        return view('gudang.dashboard', compact(
            'totalProducts', 'orderedPoCount', 'pendingOnlineOrders',
            'stokKritis', 'pendingPOs', 'attendanceToday'
        ));
    }

    private function driverDashboard()
    {
        $now = Carbon::now('Asia/Pontianak');
        $user = auth()->user();

        $activeJobs = TransportJob::with('vehicle')
            ->where('driver_id', $user->id)
            ->where('status', 'on_trip')
            ->latest()
            ->get();

        $completedJobs = TransportJob::with('vehicle')
            ->where('driver_id', $user->id)
            ->where('status', 'completed')
            ->latest()
            ->limit(8)
            ->get();

        $totalCommissionMonth = (float) TransportJob::where('driver_id', $user->id)
            ->whereMonth('departure_date', $now->month)
            ->whereYear('departure_date', $now->year)
            ->sum('driver_commission');

        $attendanceToday = Attendance::where('user_id', $user->id)->where('date', $now->toDateString())->first();

        return view('driver.dashboard', compact(
            'activeJobs', 'completedJobs', 'totalCommissionMonth', 'attendanceToday'
        ));
    }
}
