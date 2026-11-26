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

<!-- Jadwal Otomatis & Riwayat Backup di Server -->
<div class="card mt-4">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <i class="ph ph-clock-clockwise text-primary me-2"></i>
            <strong>Backup Otomatis Harian (Pukul 00:00 WIB/WITA — Retensi 14 Hari)</strong>
        </div>
        <form action="{{ route('setting.backup.run') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-dark btn-sm">
                <i class="ph ph-play"></i> Buat Snapshot Backup Sekarang
            </button>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="p-3 bg-light border-bottom small text-muted">
            Jadwal Laravel (<code>backup:database --keep=14</code>) berjalan otomatis jam <code>00:00</code> setiap hari jika scheduler server (<code>php artisan schedule:run</code>) diaktifkan. Anda juga bisa membuat snapshot atau mengunduhnya di sini kapan saja.
        </div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>Nama File Backup</th>
                        <th>Waktu Dibuat</th>
                        <th class="text-end">Ukuran</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($backupFiles ?? [] as $bf)
                    <tr>
                        <td class="font-monospace small fw-semibold">{{ $bf['name'] }}</td>
                        <td class="small text-muted">{{ $bf['modified_at'] }}</td>
                        <td class="text-end small tabular-nums">{{ number_format($bf['size'] / 1024, 1, ',', '.') }} KB</td>
                        <td class="text-end">
                            <a href="{{ route('setting.backup.download-file', $bf['name']) }}" class="btn btn-sm btn-light border text-primary py-0 px-2">
                                <i class="ph ph-download-simple"></i> Unduh
                            </a>
                            <form action="{{ route('setting.backup.delete-file', $bf['name']) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus file backup ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-light border text-danger py-0 px-2">
                                    <i class="ph ph-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center py-4 text-muted small">Belum ada file snapshot tersimpan di server.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
