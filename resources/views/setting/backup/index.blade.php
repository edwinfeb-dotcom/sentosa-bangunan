@extends('layouts.app')

@section('title', 'Backup & Export Data')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Backup & Export Data</h2>
        <p class="page-subtitle">Unduh data laporan harian ke Excel atau cadangkan seluruh database SQL.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body p-4 text-center d-flex flex-column justify-content-between">
                <div>
                    <div class="bg-success bg-opacity-10 text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 76px; height: 76px;">
                        <i class="ph ph-file-xls fs-1"></i>
                    </div>
                    <h5 class="fw-bold">Export Laporan Harian</h5>
                    <p class="text-muted small mb-4">Unduh semua riwayat laporan harian toko (Piket, Kasir, Absensi, Ceklis Sore) ke dalam format Excel (.xlsx).</p>
                </div>
                <a href="{{ route('setting.backup.export-reports') }}" class="btn btn-success w-100 py-2">
                    <i class="ph ph-download-simple"></i> Download Excel Laporan
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body p-4 text-center d-flex flex-column justify-content-between">
                <div>
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 76px; height: 76px;">
                        <i class="ph ph-database fs-1"></i>
                    </div>
                    <h5 class="fw-bold">Backup Database (SQL)</h5>
                    <p class="text-muted small mb-4">Unduh seluruh struktur dan data aplikasi (Produk, Penjualan, Keuangan, Karyawan) dalam format file <code>.sql</code>.</p>
                </div>
                <a href="{{ route('setting.backup.download-sql') }}" class="btn btn-primary w-100 py-2">
                    <i class="ph ph-download-simple"></i> Download Backup SQL
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
