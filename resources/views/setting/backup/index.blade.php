@extends('layouts.app')

@section('title', 'Backup & Export Data')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #1E293B;">Backup & Export Data</h4>
        <p class="text-muted mb-0" style="font-size: 0.875rem;">Unduh data penting toko Anda untuk cadangan (backup).</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4 text-center">
                <div class="bg-success bg-opacity-10 text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                    <i class="ph ph-file-xls fs-1"></i>
                </div>
                <h5 class="fw-bold">Export Laporan Harian</h5>
                <p class="text-muted mb-4">Unduh semua riwayat laporan harian toko (Piket, Kasir, Absensi, dll) ke dalam format Excel (.xlsx).</p>
                <a href="{{ route('setting.backup.export-reports') }}" class="btn btn-success w-100 py-2">
                    Download Excel Laporan
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4 text-center">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                    <i class="ph ph-database fs-1"></i>
                </div>
                <h5 class="fw-bold">Backup Database</h5>
                <p class="text-muted mb-4">Unduh seluruh data aplikasi (Produk, Penjualan, Karyawan) dalam bentuk file SQL.</p>
                <button class="btn btn-primary w-100 py-2" onclick="alert('Fitur Backup Database (SQL) akan segera hadir.')">
                    Download Database
                </button>
            </div>
        </div>
    </div>
</div>
@endsection