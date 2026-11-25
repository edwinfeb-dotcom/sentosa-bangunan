<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\CashLedger;
use App\Models\EmployeeDebt;
use App\Models\Payroll;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $query = Payroll::with('user')->latest('year')->latest('month');

        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }
        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        $payrolls = $query->paginate(15)->withQueryString();
        $wallets = Wallet::where('is_active', true)->get();
        $employees = User::whereNotIn('role', ['owner', 'pelanggan'])->orderBy('name')->get();

        return view('hr.payroll.index', compact('payrolls', 'wallets', 'employees'));
    }

    public function updateSalaryConfig(Request $request, User $employee)
    {
        $request->validate([
            'monthly_salary' => 'required|numeric|min:0',
            'absence_deduction_per_day' => 'nullable|numeric|min:0',
            'daily_salary' => 'nullable|numeric|min:0',
        ]);

        $monthlySalary = (float) $request->monthly_salary;
        $dailySalary = $request->filled('daily_salary')
            ? (float) $request->daily_salary
            : ($monthlySalary > 0 ? round($monthlySalary / 26) : (float) ($employee->daily_salary ?? 100000));

        $employee->update([
            'monthly_salary' => $monthlySalary,
            'absence_deduction_per_day' => (float) ($request->absence_deduction_per_day ?? 0),
            'daily_salary' => $dailySalary,
        ]);

        return back()->with('success', 'Pengaturan gaji full & potongan bolos untuk ' . $employee->name . ' berhasil diperbarui.');
    }

    public function generate(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020',
            'working_days' => 'nullable|integer|min:1|max:31',
        ]);

        $month = (int) $request->month;
        $year = (int) $request->year;
        $workingDays = (int) ($request->working_days ?? 26);

        $users = User::whereNotIn('role', ['owner', 'pelanggan'])->get();
        $generatedCount = 0;

        foreach ($users as $user) {
            $existing = Payroll::where('user_id', $user->id)->where('month', $month)->where('year', $year)->first();
            if ($existing && $existing->status === 'paid') {
                continue;
            }

            $attendances = Attendance::where('user_id', $user->id)
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->get();

            $presentDays = $attendances->where('status', 'hadir')->count();
            $alphaDays = $attendances->where('status', 'alpha')->count();
            $totalHok = (float) $attendances->where('status', 'hadir')->sum('hok');
            $totalOvertimePay = (float) $attendances->sum('overtime_pay');

            $monthlyBase = (float) ($user->monthly_salary ?? 0);
            $dailySalary = (float) ($user->daily_salary ?? 100000);

            if ($monthlyBase > 0) {
                // Gaji Full Bulanan dipasang Owner + Lembur
                $basicSalary = $monthlyBase + $totalOvertimePay;
                $penaltyPerDay = (float) ($user->absence_deduction_per_day ?? 0) > 0
                    ? (float) $user->absence_deduction_per_day
                    : round($monthlyBase / $workingDays);
            } else {
                // Mode Harian (HOK) + Lembur
                $basicSalary = ($totalHok * $dailySalary) + $totalOvertimePay;
                $penaltyPerDay = (float) ($user->absence_deduction_per_day ?? 0) > 0
                    ? (float) $user->absence_deduction_per_day
                    : $dailySalary;
            }

            $alphaDeduction = $alphaDays * $penaltyPerDay;

            $unpaidDebts = EmployeeDebt::where('user_id', $user->id)->where('status', 'unpaid')->get();
            $allowance = $existing ? (float) $existing->allowance : 0;
            $previousDebtDeduction = $existing ? (float) $existing->debt_deduction : 0;
            $debtDeduction = $previousDebtDeduction + (float) $unpaidDebts->sum('amount');
            $totalDeduction = $alphaDeduction + $debtDeduction;

            $netSalary = max(0, $basicSalary + $allowance - $totalDeduction);

            Payroll::updateOrCreate(
                ['user_id' => $user->id, 'month' => $month, 'year' => $year],
                [
                    'basic_salary' => $basicSalary,
                    'monthly_salary_base' => $monthlyBase,
                    'working_days' => $workingDays,
                    'present_days' => $presentDays,
                    'alpha_days' => $alphaDays,
                    'alpha_deduction' => $alphaDeduction,
                    'debt_deduction' => $debtDeduction,
                    'allowance' => $allowance,
                    'deduction' => $totalDeduction,
                    'net_salary' => $netSalary,
                    'status' => $existing?->status ?? 'draft',
                ]
            );

            foreach ($unpaidDebts as $debt) {
                $debt->update(['status' => 'paid']);
            }

            $generatedCount++;
        }

        return back()->with('success', "$generatedCount slip gaji berhasil digenerate/diperbarui (termasuk potongan bolos/alpha).");
    }

    public function updateBonus(Request $request, Payroll $payroll)
    {
        $request->validate([
            'allowance' => 'required|numeric|min:0',
            'bonus_notes' => 'nullable|string|max:255',
        ]);

        $netSalary = max(0, (float) $payroll->basic_salary + (float) $request->allowance - (float) $payroll->deduction);

        $payroll->update([
            'allowance' => $request->allowance,
            'bonus_notes' => $request->bonus_notes,
            'net_salary' => $netSalary,
        ]);

        return back()->with('success', 'Bonus / insentif manual untuk ' . $payroll->user->name . ' berhasil diperbarui.');
    }

    public function markPaid(Request $request, Payroll $payroll)
    {
        if ($payroll->status === 'paid') {
            return back()->with('error', 'Slip gaji ini sudah berstatus Dibayar.');
        }

        $request->validate([
            'wallet_id' => 'nullable|exists:wallets,id',
        ]);

        DB::transaction(function () use ($request, $payroll) {
            $payroll->update(['status' => 'paid']);

            if ($payroll->net_salary > 0) {
                $walletId = $request->wallet_id ?: Wallet::where('is_active', true)->value('id');
                CashLedger::create([
                    'user_id' => auth()->id(),
                    'wallet_id' => $walletId,
                    'type' => 'out',
                    'category' => 'gaji',
                    'amount' => $payroll->net_salary,
                    'description' => "Pembayaran Gaji {$payroll->user->name} ({$payroll->month}/{$payroll->year})",
                    'reference_type' => Payroll::class,
                    'reference_id' => $payroll->id,
                    'date' => date('Y-m-d'),
                ]);
            }
        });

        return back()->with('success', 'Gaji ' . $payroll->user->name . ' ditandai Dibayar dan tercatat ke Buku Kas.');
    }

    public function slip(Payroll $payroll)
    {
        $payroll->load('user');
        return view('hr.payroll.slip', compact('payroll'));
    }
}
