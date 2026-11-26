<?php

namespace App\Http\Controllers;

use App\Exports\DailyReportsExport;
use App\Models\ChecklistTemplate;
use App\Models\DailyReport;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class DailyReportController extends Controller
{
    public function index()
    {
        $reports = DailyReport::with(['user', 'verifier'])->latest()->paginate(10);
        return view('daily_reports.index', compact('reports'));
    }

    public function create()
    {
        $templates = ChecklistTemplate::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('section');

        return view('daily_reports.form', compact('templates'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'piket' => 'required|string',
            'uang_kecil' => 'required|numeric|min:0',
            'uang_besar' => 'required|numeric|min:0',
            'transfer' => 'required|numeric|min:0',
            'total_kasir' => 'required|numeric',
            'barang_datang' => 'required|string',
            'cek_aset' => 'required|string',
            'absensi' => 'required|string',
            'foto1' => 'nullable|image|max:2048',
            'foto2' => 'nullable|image|max:2048',
            'foto3' => 'nullable|image|max:2048',
            'ceklis_sore' => 'required|string',
        ]);

        $fotoPaths = [];
        for ($i = 1; $i <= 3; $i++) {
            $field = 'foto' . $i;
            if ($request->hasFile($field)) {
                $fotoPaths[$field] = $request->file($field)->store('reports', 'public');
            }
        }

        $totalKasir = (float) $request->uang_kecil + (float) $request->uang_besar + (float) $request->transfer;

        $reportData = [
            'piket' => $request->piket,
            'laporan_kasir' => [
                'uang_kecil' => $request->uang_kecil,
                'uang_besar' => $request->uang_besar,
                'transfer' => $request->transfer,
                'total_kasir' => $totalKasir,
            ],
            'barang_datang' => $request->barang_datang,
            'cek_aset' => $request->cek_aset,
            'absensi' => $request->absensi,
            'foto' => $fotoPaths,
            'ceklis_sore' => $request->ceklis_sore,
            'checked_items' => $request->input('checked_items', []),
        ];

        DailyReport::create([
            'user_id' => auth()->id(),
            'date' => $request->date,
            'verification_code' => strtoupper(Str::random(8)),
            'report_data' => $reportData,
        ]);

        return redirect()->route('daily-reports.index')->with('success', 'Laporan harian berhasil dikirim. Tunggu verifikasi Admin.');
    }

    public function verify(Request $request, DailyReport $dailyReport)
    {
        $request->validate(['code' => 'required|string']);

        if (strtoupper(trim($request->code)) !== $dailyReport->verification_code) {
            return back()->with('error', 'Kode verifikasi salah!');
        }

        $dailyReport->update([
            'status' => 'verified',
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        return back()->with('success', 'Laporan berhasil diverifikasi.');
    }

    public function export()
    {
        return Excel::download(new DailyReportsExport, 'Laporan_Harian_' . date('Y-m-d') . '.xlsx');
    }

    public function posSummary(Request $request)
    {
        $date = $request->get('date', date('Y-m-d'));

        $openingCash = (float) \App\Models\CashSession::whereDate('start_time', $date)->sum('opening_balance');
        $cashSales = (float) \App\Models\Payment::where('method', 'cash')
            ->whereHas('sale', fn ($q) => $q->where('status', 'completed')->whereDate('created_at', $date))
            ->sum('amount');
        $transferSales = (float) \App\Models\Payment::where('method', 'transfer')
            ->whereHas('sale', fn ($q) => $q->where('status', 'completed')->whereDate('created_at', $date))
            ->sum('amount');

        return response()->json([
            'uang_kecil' => $openingCash,
            'uang_besar' => $cashSales,
            'transfer' => $transferSales,
        ]);
    }

    public function destroy(DailyReport $dailyReport)
    {
        if ($dailyReport->status === 'verified') {
            return back()->with('error', 'Laporan yang sudah diverifikasi tidak dapat dihapus.');
        }

        $dailyReport->delete();
        return back()->with('success', 'Draft laporan harian berhasil dihapus.');
    }
}
