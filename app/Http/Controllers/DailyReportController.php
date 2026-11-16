<?php

namespace App\Http\Controllers;

use App\Models\DailyReport;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
            'laporan_kasir' => 'required|string',
            'barang_datang' => 'required|string',
            'cek_aset' => 'required|string',
            'absensi' => 'required|string',
            'foto' => 'nullable|image|max:2048',
            'ceklis_sore' => 'required|string',
        ]);

        // Upload foto jika ada
        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $fotoPath = $request->file('foto')->store('reports', 'public');
        }

        // Kumpulkan data menjadi Array JSON
        $reportData = [
            'piket' => $request->piket,
            'laporan_kasir' => $request->laporan_kasir,
            'barang_datang' => $request->barang_datang,
            'cek_aset' => $request->cek_aset,
            'absensi' => $request->absensi,
            'foto_path' => $fotoPath,
            'ceklis_sore' => $request->ceklis_sore,
        ];

        DailyReport::create([
            'user_id' => auth()->id(),
            'date' => $request->date,
            'verification_code' => strtoupper(Str::random(8)), // Generate kode unik
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
}