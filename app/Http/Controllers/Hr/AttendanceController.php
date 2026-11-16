<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index()
    {
        $today = Carbon::today('Asia/Pontianak')->format('Y-m-d');
        
        // Cek absensi hari ini
        $attendanceToday = Attendance::where('user_id', auth()->id())
                                     ->where('date', $today)
                                     ->first();

        // Riwayat absensi
        $history = Attendance::where('user_id', auth()->id())->latest('date')->paginate(10);

        return view('hr.attendance.index', compact('attendanceToday', 'history'));
    }

    public function clockIn()
    {
        $today = Carbon::today('Asia/Pontianak')->format('Y-m-d');
        
        // Cek apakah sudah clock in
        $exists = Attendance::where('user_id', auth()->id())->where('date', $today)->exists();
        if ($exists) {
            return back()->with('error', 'Anda sudah melakukan Clock In hari ini.');
        }

        Attendance::create([
            'user_id' => auth()->id(),
            'date' => $today,
            'clock_in' => Carbon::now('Asia/Pontianak')->format('H:i:s'),
        ]);

        return back()->with('success', 'Clock In berhasil. Selamat bekerja!');
    }

    public function clockOut()
    {
        $today = Carbon::today('Asia/Pontianak')->format('Y-m-d');
        $attendance = Attendance::where('user_id', auth()->id())->where('date', $today)->first();

        if (!$attendance || $attendance->clock_out) {
            return back()->with('error', 'Anda belum Clock In atau sudah Clock Out.');
        }

        $clockOutTime = Carbon::now('Asia/Pontianak');
        $clockInTime = Carbon::createFromFormat('Y-m-d H:i:s', $today . ' ' . $attendance->clock_in);

        // Hitung durasi kerja dalam jam (desimal)
        $durationInHours = $clockInTime->diffInMinutes($clockOutTime) / 60;

        // BR-04 (HOK): min(durasi_jam / 10, 1)
        $hok = min($durationInHours / 10, 1);

        // BR-05 (Lembur): Jam > 10 = Lembur
        $overtimeHours = max(0, $durationInHours - 10);
        
        // Rumus Lembur: (Gaji Harian / 10) * 2 * Jam Lembur
        $dailySalary = auth()->user()->daily_salary ?? 100000;
        $overtimePay = ($dailySalary / 10) * 2 * $overtimeHours;

        $attendance->update([
            'clock_out' => $clockOutTime->format('H:i:s'),
            'hok' => round($hok, 2),
            'overtime_hours' => round($overtimeHours, 2),
            'overtime_pay' => round($overtimePay, 2),
        ]);

        return back()->with('success', 'Clock Out berhasil. Terima kasih atas kerja kerasnya hari ini!');
    }
}