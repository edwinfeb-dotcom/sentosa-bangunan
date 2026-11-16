<?php

namespace App\Http\Controllers;

use App\Models\DailyReport;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Exports\DailyReportsExport;
use Maatwebsite\Excel\Facades\Excel;

class DailyReportController extends Controller
{
    public function index()
    {
        $reports = DailyReport::with('user')->latest()->paginate(10);
        return view('daily_reports.index', compact('reports'));
    }

    public function create()
    {
        return view('daily_reports.form');
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'piket' => 'required|string',
            'uang_kecil' => 'required|numeric|min:0',
            'uang_besar' => 'required|numeric|min:0',
            'transfer' => 'required|numeric|min:0',
            'total_kasir' => 'required|numeric', // Hasil hitungan otomatis
            'barang_datang' => 'required|string',
            'cek_aset' => 'required|string',
            'absensi' => 'required|string',
            'foto1' => 'nullable|image|max:2048',
            'foto2' => 'nullable|image|max:2048',
            'foto3' => 'nullable|image|max:2048',
            'ceklis_sore' => 'required|string',
        ]);

        // Upload maksimal 3 foto
        $fotoPaths = [];
        for ($i = 1; $i <= 3; $i++) {
            $field = 'foto' . $i;
            if ($request->hasFile($field)) {
                $fotoPaths[$field] = $request->file($field)->store('reports', 'public');
            }
        }

        // Kumpulkan data menjadi Array JSON
        $reportData = [
            'piket' => $request->piket,
            'laporan_kasir' => [
                'uang_kecil' => $request->uang_kecil,
                'uang_besar' => $request->uang_besar,
                'transfer' => $request->transfer,
                'total_kasir' => $request->total_kasir,
            ],
            'barang_datang' => $request->barang_datang,
            'cek_aset' => $request->cek_aset,
            'absensi' => $request->absensi,
            'foto' => $fotoPaths, // Simpan array path foto
            'ceklis_sore' => $request->ceklis_sore,
        ];

        DailyReport::create([
            'user_id' => auth()->id(),
            'date' => $request->date,
            'verification_code' => strtoupper(Str::random(8)),
            'report_data' => $reportData,
        ]);

        return redirect()->route('daily-reports.index')->with('success', 'Laporan harian berhasil dikirim. Tunggu verifikasi Admin.');
    }

    // Fitur Verifikasi oleh Admin/Owner
    public function verify(Request $request, DailyReport $dailyReport)
    {
        $request->validate(['code' => 'required|string']);

        if ($request->code !== $dailyReport->verification_code) {
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
}