<?php

namespace App\Http\Controllers\Hr;

use App\Exports\MonthlyAttendanceExport;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceQrSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $now = Carbon::now('Asia/Pontianak');
        $today = $now->format('Y-m-d');

        $attendanceToday = Attendance::where('user_id', auth()->id())
            ->where('date', $today)
            ->first();

        $history = Attendance::where('user_id', auth()->id())
            ->latest('date')
            ->paginate(10);

        $isManager = in_array(auth()->user()->role, ['owner', 'admin']);
        $teamAttendances = null;
        $filterDate = $request->get('date', $today);
        $recapMonth = (int) $request->get('month', $now->month);
        $recapYear = (int) $request->get('year', $now->year);
        $activeQrSession = null;
        $permanentQrSession = null;
        $monthlySummary = [];
        $staffUsers = collect();

        if ($isManager) {
            $permanentQrSession = $this->getOrCreatePermanentQrSession($today);
            $activeQrSession = $this->getOrCreateTodayQrSession($today);

            $teamAttendances = Attendance::with('user')
                ->whereDate('date', $filterDate)
                ->orderBy('clock_in')
                ->get();

            $staffUsers = User::whereNotIn('role', ['owner', 'pelanggan'])
                ->orderBy('name')
                ->get();

            $monthlySummary = $this->buildMonthlySummary($staffUsers, $recapMonth, $recapYear);
        }

        return view('hr.attendance.index', compact(
            'attendanceToday',
            'history',
            'teamAttendances',
            'filterDate',
            'recapMonth',
            'recapYear',
            'activeQrSession',
            'permanentQrSession',
            'monthlySummary',
            'staffUsers'
        ));
    }

    public function qrKiosk(Request $request)
    {
        $today = Carbon::today('Asia/Pontianak')->format('Y-m-d');
        $mode = $request->get('mode', 'permanent') === 'daily' ? 'daily' : 'permanent';
        $permanentQrSession = $this->getOrCreatePermanentQrSession($today);
        $dailyQrSession = $this->getOrCreateTodayQrSession($today);
        $activeQrSession = $mode === 'daily' ? $dailyQrSession : $permanentQrSession;

        $todayCheckins = Attendance::with('user')
            ->whereDate('date', $today)
            ->orderByDesc('updated_at')
            ->get();

        return view('hr.attendance.qr-kiosk', compact(
            'activeQrSession',
            'permanentQrSession',
            'dailyQrSession',
            'mode',
            'todayCheckins',
            'today'
        ));
    }

    public function refreshQr(Request $request)
    {
        $today = Carbon::today('Asia/Pontianak')->format('Y-m-d');
        $type = $request->get('type', 'permanent');

        if ($type === 'daily') {
            $this->createFreshQrSession($today, 'daily');
            return back()->with('success', 'QR Code Harian baru berhasil dibuat. QR harian lama dinonaktifkan.');
        }

        $this->createFreshQrSession($today, 'permanent');

        return back()->with('success', 'QR Poster Permanen baru berhasil dibuat & QR poster lama dinonaktifkan. Silakan cetak ulang untuk ditempel di area toko.');
    }

    public function scanQr(Request $request)
    {
        $request->validate([
            'qr_token' => 'required|string|max:150',
        ]);

        $token = trim((string) $request->qr_token);
        $now = Carbon::now('Asia/Pontianak');
        $today = $now->format('Y-m-d');

        $session = AttendanceQrSession::where('token', $token)
            ->where('is_active', true)
            ->where(function ($q) use ($today) {
                $q->where('type', 'permanent')
                  ->orWhereDate('date', $today);
            })
            ->first();

        if (!$session || ($session->expires_at && $now->greaterThan($session->expires_at))) {
            $msg = 'QR Code absensi tidak valid atau sudah kedaluwarsa. Silakan scan QR terbaru dari kasir / admin.';
            return $this->respondScan($request, false, $msg, 422);
        }

        $user = auth()->user();
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        if (!$attendance) {
            Attendance::create([
                'user_id' => $user->id,
                'status' => 'hadir',
                'date' => $today,
                'clock_in' => $now->format('H:i:s'),
                'check_in_method' => 'qr',
                'notes' => 'Absen Masuk via Scan QR',
            ]);

            return $this->respondScan(
                $request,
                true,
                'Scan QR berhasil! Absen Masuk tercatat pada pukul ' . $now->format('H:i') . ' WIB.'
            );
        }

        if ($attendance->status !== 'hadir') {
            return $this->respondScan(
                $request,
                false,
                'Status absensi Anda hari ini sudah tercatat sebagai ' . strtoupper($attendance->status) . '.',
                422
            );
        }

        if ($attendance->clock_out) {
            return $this->respondScan(
                $request,
                true,
                'Absensi QR Anda hari ini sudah lengkap (Masuk ' . $attendance->clock_in . ' & Pulang ' . $attendance->clock_out . ').'
            );
        }

        $calc = $this->calculateClockOutMetrics($user, $today, $attendance->clock_in, $now);

        $attendance->update([
            'clock_out' => $now->format('H:i:s'),
            'check_in_method' => $attendance->check_in_method === 'qr' ? 'qr' : 'manual+qr',
            'hok' => $calc['hok'],
            'overtime_hours' => $calc['overtime_hours'],
            'overtime_pay' => $calc['overtime_pay'],
            'notes' => trim(($attendance->notes ? $attendance->notes . ' | ' : '') . 'Pulang via Scan QR'),
        ]);

        return $this->respondScan(
            $request,
            true,
            'Scan QR berhasil! Absen Pulang tercatat pada ' . $now->format('H:i') . ' WIB (HOK: ' . $calc['hok'] . ').'
        );
    }

    public function clockIn()
    {
        $now = Carbon::now('Asia/Pontianak');
        $today = $now->format('Y-m-d');

        $exists = Attendance::where('user_id', auth()->id())->whereDate('date', $today)->exists();
        if ($exists) {
            return back()->with('error', 'Anda sudah melakukan Clock In hari ini.');
        }

        Attendance::create([
            'user_id' => auth()->id(),
            'status' => 'hadir',
            'date' => $today,
            'clock_in' => $now->format('H:i:s'),
            'check_in_method' => 'manual',
            'notes' => 'Clock In manual',
        ]);

        return back()->with('success', 'Clock In berhasil. Selamat bekerja!');
    }

    public function clockOut()
    {
        $now = Carbon::now('Asia/Pontianak');
        $today = $now->format('Y-m-d');
        $attendance = Attendance::where('user_id', auth()->id())->whereDate('date', $today)->first();

        if (!$attendance || $attendance->clock_out || $attendance->status !== 'hadir') {
            return back()->with('error', 'Anda belum Clock In atau sudah Clock Out.');
        }

        $calc = $this->calculateClockOutMetrics(auth()->user(), $today, $attendance->clock_in, $now);

        $attendance->update([
            'clock_out' => $now->format('H:i:s'),
            'hok' => $calc['hok'],
            'overtime_hours' => $calc['overtime_hours'],
            'overtime_pay' => $calc['overtime_pay'],
        ]);

        return back()->with('success', 'Clock Out berhasil. Terima kasih atas kerja kerasnya hari ini!');
    }

    public function markStatus(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'status' => 'required|in:hadir,izin,sakit,alpha',
            'clock_in' => 'nullable|date_format:H:i',
            'clock_out' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string|max:255',
        ]);

        $targetUser = User::findOrFail($request->user_id);
        $clockIn = $request->status === 'hadir' ? ($request->clock_in ? $request->clock_in . ':00' : '08:00:00') : null;
        $clockOut = $request->status === 'hadir' ? ($request->clock_out ? $request->clock_out . ':00' : '18:00:00') : null;

        $hok = 0;
        $overtimeHours = 0;
        $overtimePay = 0;

        if ($request->status === 'hadir' && $clockIn && $clockOut) {
            $calc = $this->calculateClockOutMetrics(
                $targetUser,
                $request->date,
                $clockIn,
                Carbon::createFromFormat('Y-m-d H:i:s', $request->date . ' ' . $clockOut, 'Asia/Pontianak')
            );
            $hok = $calc['hok'];
            $overtimeHours = $calc['overtime_hours'];
            $overtimePay = $calc['overtime_pay'];
        } elseif ($request->status === 'hadir') {
            $hok = 1;
        }

        Attendance::updateOrCreate(
            ['user_id' => $targetUser->id, 'date' => $request->date],
            [
                'status' => $request->status,
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'check_in_method' => 'admin',
                'hok' => $hok,
                'overtime_hours' => $overtimeHours,
                'overtime_pay' => $overtimePay,
                'notes' => $request->notes ?: ('Dicatat Admin/Owner sebagai ' . strtoupper($request->status)),
            ]
        );

        return back()->with('success', "Status absensi {$targetUser->name} tanggal {$request->date} berhasil disimpan sebagai " . strtoupper($request->status) . '.');
    }

    public function syncAlpha(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
        ]);

        $month = (int) $request->month;
        $year = (int) $request->year;
        $today = Carbon::today('Asia/Pontianak');
        $startOfMonth = Carbon::createFromDate($year, $month, 1, 'Asia/Pontianak')->startOfDay();
        $endOfPeriod = $startOfMonth->copy()->endOfMonth()->startOfDay();

        // Jangan menandai tanggal hari ini atau masa depan sebagai bolos otomatis
        if ($endOfPeriod->greaterThanOrEqualTo($today)) {
            $endOfPeriod = $today->copy()->subDay();
        }

        if ($endOfPeriod->lessThan($startOfMonth)) {
            return back()->with('error', 'Belum ada hari kerja lampau pada periode tersebut untuk disinkronkan.');
        }

        $staffUsers = User::whereNotIn('role', ['owner', 'pelanggan'])
            ->where(function ($q) {
                $q->whereNull('employee_status')->orWhere('employee_status', '!=', 'nonaktif');
            })
            ->get();

        $flaggedCount = 0;

        foreach ($staffUsers as $user) {
            $existingDates = Attendance::where('user_id', $user->id)
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->pluck('date')
                ->map(fn ($d) => Carbon::parse($d)->format('Y-m-d'))
                ->flip()
                ->all();

            $cursor = $startOfMonth->copy();
            $joinDate = $user->join_date ? Carbon::parse($user->join_date)->startOfDay() : null;

            while ($cursor->lessThanOrEqualTo($endOfPeriod)) {
                $dateStr = $cursor->format('Y-m-d');
                // Hari kerja toko bangunan Senin–Sabtu (kecuali Minggu) dan setelah join_date
                $isWorkDay = $cursor->dayOfWeek !== Carbon::SUNDAY;
                $hasJoined = !$joinDate || $cursor->greaterThanOrEqualTo($joinDate);

                if ($isWorkDay && $hasJoined && !isset($existingDates[$dateStr])) {
                    Attendance::create([
                        'user_id' => $user->id,
                        'status' => 'alpha',
                        'date' => $dateStr,
                        'check_in_method' => 'system',
                        'hok' => 0,
                        'overtime_hours' => 0,
                        'overtime_pay' => 0,
                        'notes' => 'Tidak ada absensi (Otomatis Alpha / Bolos)',
                    ]);
                    $flaggedCount++;
                }

                $cursor->addDay();
            }
        }

        return back()->with('success', "Sinkronisasi selesai: {$flaggedCount} catatan Alpha / Bolos ditambahkan untuk hari kerja tanpa absensi.");
    }

    public function exportMonthly(Request $request)
    {
        $now = Carbon::now('Asia/Pontianak');
        $month = (int) $request->get('month', $now->month);
        $year = (int) $request->get('year', $now->year);

        $staffUsers = User::whereNotIn('role', ['owner', 'pelanggan'])
            ->orderBy('name')
            ->get();

        $summaryRows = $this->buildMonthlySummary($staffUsers, $month, $year);

        return Excel::download(
            new MonthlyAttendanceExport($month, $year, $summaryRows),
            sprintf('Rekap_Absensi_%02d_%d.xlsx', $month, $year)
        );
    }

    private function getOrCreatePermanentQrSession(string $today): AttendanceQrSession
    {
        $session = AttendanceQrSession::where('type', 'permanent')
            ->where('is_active', true)
            ->latest('id')
            ->first();

        if ($session) {
            return $session;
        }

        return $this->createFreshQrSession($today, 'permanent');
    }

    private function getOrCreateTodayQrSession(string $today): AttendanceQrSession
    {
        $session = AttendanceQrSession::where('type', 'daily')
            ->whereDate('date', $today)
            ->where('is_active', true)
            ->latest('id')
            ->first();

        if ($session) {
            return $session;
        }

        return $this->createFreshQrSession($today, 'daily');
    }

    private function createFreshQrSession(string $today, string $type = 'daily'): AttendanceQrSession
    {
        if ($type === 'permanent') {
            AttendanceQrSession::where('type', 'permanent')
                ->where('is_active', true)
                ->update(['is_active' => false]);

            return AttendanceQrSession::create([
                'date' => $today,
                'type' => 'permanent',
                'label' => 'QR Poster Tempel Toko',
                'token' => 'SB-POSTER-' . strtoupper(Str::random(10)),
                'created_by' => auth()->id(),
                'is_active' => true,
                'expires_at' => null,
            ]);
        }

        AttendanceQrSession::where('type', 'daily')
            ->whereDate('date', $today)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $token = 'SB-ABSEN-' . str_replace('-', '', $today) . '-' . strtoupper(Str::random(8));

        return AttendanceQrSession::create([
            'date' => $today,
            'type' => 'daily',
            'label' => 'QR Harian Toko',
            'token' => $token,
            'created_by' => auth()->id(),
            'is_active' => true,
            'expires_at' => Carbon::parse($today, 'Asia/Pontianak')->endOfDay(),
        ]);
    }

    private function buildMonthlySummary($staffUsers, int $month, int $year): array
    {
        $records = Attendance::whereIn('user_id', $staffUsers->pluck('id'))
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->get()
            ->groupBy('user_id');

        $summary = [];
        foreach ($staffUsers as $user) {
            $userLogs = $records->get($user->id, collect());
            $summary[] = [
                'user' => $user,
                'hadir_days' => $userLogs->where('status', 'hadir')->count(),
                'izin_days' => $userLogs->where('status', 'izin')->count(),
                'sakit_days' => $userLogs->where('status', 'sakit')->count(),
                'alpha_days' => $userLogs->where('status', 'alpha')->count(),
                'total_hok' => (float) $userLogs->where('status', 'hadir')->sum('hok'),
                'total_overtime_hours' => (float) $userLogs->sum('overtime_hours'),
                'qr_count' => $userLogs->filter(fn ($a) => str_contains((string) $a->check_in_method, 'qr'))->count(),
            ];
        }

        return $summary;
    }

    private function calculateClockOutMetrics(User $user, string $date, string $clockInStr, Carbon $clockOutTime): array
    {
        $clockInTime = Carbon::createFromFormat('Y-m-d H:i:s', $date . ' ' . $clockInStr, 'Asia/Pontianak');
        $durationInHours = max(0, $clockInTime->diffInMinutes($clockOutTime, false) / 60);
        $hok = min($durationInHours / 10, 1);
        $overtimeHours = max(0, $durationInHours - 10);

        $dailySalary = (float) ($user->monthly_salary > 0 ? round($user->monthly_salary / 26) : ($user->daily_salary ?? 100000));
        $overtimePay = ($dailySalary / 10) * 2 * $overtimeHours;

        return [
            'hok' => round($hok, 2),
            'overtime_hours' => round($overtimeHours, 2),
            'overtime_pay' => round($overtimePay, 2),
        ];
    }

    private function respondScan(Request $request, bool $ok, string $message, int $status = 200)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => $ok,
                'message' => $message,
                'redirect' => route('hr.attendance.index'),
            ], $status);
        }

        return back()->with($ok ? 'success' : 'error', $message);
    }
}
