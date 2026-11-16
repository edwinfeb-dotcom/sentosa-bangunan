<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\User;
use App\Models\Attendance;
use App\Models\EmployeeDebt;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PayrollController extends Controller
{
    public function index()
    {
        $payrolls = Payroll::with('user')->latest('year')->latest('month')->paginate(15);
        return view('hr.payroll.index', compact('payrolls'));
    }

        public function generate(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020',
        ]);

        $month = $request->month;
        $year = $request->year;

        $users = User::where('role', '!=', 'owner')->get();
        $generatedCount = 0;

        foreach ($users as $user) {
            // 1. Hitung Total HOK & Lembur di bulan tersebut
            $attendances = Attendance::where('user_id', $user->id)
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->get();

            $totalHok = $attendances->sum('hok');
            $totalOvertimePay = $attendances->sum('overtime_pay');

            // Pastikan gaji harian tidak null
            $dailySalary = $user->daily_salary ?? 100000;

            // Gaji Pokok = (Total HOK * Gaji Harian) + Uang Lembur
            $basicSalary = ($totalHok * $dailySalary) + $totalOvertimePay;

            // 2. Hitung Potongan (Utang Belanja Karyawan)
            $unpaidDebts = EmployeeDebt::where('user_id', $user->id)->where('status', 'unpaid')->get();
            $totalDeduction = $unpaidDebts->sum('amount');

            // Gaji Bersih
            $netSalary = $basicSalary - $totalDeduction;

            // 3. Simpan/Update ke tabel Payroll
            Payroll::updateOrCreate(
                ['user_id' => $user->id, 'month' => $month, 'year' => $year],
                [
                    'basic_salary' => $basicSalary,
                    'allowance' => 0,
                    'deduction' => $totalDeduction,
                    'net_salary' => $netSalary,
                    'status' => 'draft',
                ]
            );

            // Tandai utang sudah terpotong (paid)
            foreach ($unpaidDebts as $debt) {
                $debt->update(['status' => 'paid']);
            }

            $generatedCount++;
        }

        return back()->with('success', "$generatedCount slip gaji berhasil digenerate/diperbarui.");
    }

    public function slip(Payroll $payroll)
    {
        $payroll->load('user');
        return view('hr.payroll.slip', compact('payroll'));
    }
}