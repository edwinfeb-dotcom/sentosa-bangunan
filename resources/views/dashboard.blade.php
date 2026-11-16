@extends('layouts.app')

@section('title', 'Pusat Kendali')

@section('content')
<!-- Banner Header Gelap -->
<div class="card border-0 shadow-sm mb-4" style="background-color: #0F172A; border-radius: 16px; overflow: hidden; position: relative;">
    <div class="card-body p-4 position-relative z-1">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h3 class="fw-bold text-white mb-2">Kontrol Toko dalam Satu Ruang Kerja</h3>
                <p class="text-white-50 mb-0" style="font-size: 0.9rem;">Persetujuan, tutup buku, kredit, pembelian, gudang, hantaran, profitabilitas, aset, dan kesehatan sistem terintegrasi.</p>
            </div>
            <div class="col-md-4 text-end d-none d-md-block">
                <div class="bg-success bg-opacity-25 text-success rounded-circle d-inline-flex align-items-center justify-content-center fw-bold fs-3" style="width: 70px; height: 70px; border: 2px solid #10B981;">
                    100%
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Baris Ringkasan (Kartu Gradient) -->
<div class="row mb-4 g-3">
    <!-- Omzet Hari Ini -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #1E3A8A 0%, #3B82F6 100%); color: white;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-white-50 mb-0 fw-semibold">Omzet Hari Ini</h6>
                    <i class="ph ph-chart-line-up fs-3 opacity-50"></i>
                </div>
                <h3 class="fw-bold mb-0">Rp {{ number_format($omzetHariIni, 0, ',', '.') }}</h3>
            </div>
        </div>
    </div>

    <!-- Saldo Kas -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #059669 0%, #10B981 100%); color: white;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-white-50 mb-0 fw-semibold">Total Saldo Kas</h6>
                    <i class="ph ph-wallet fs-3 opacity-50"></i>
                </div>
                <h3 class="fw-bold mb-0">Rp {{ number_format($saldoKas, 0, ',', '.') }}</h3>
            </div>
        </div>
    </div>

    <!-- Laporan Pending -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #D97706 0%, #F59E0B 100%); color: white;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-white-50 mb-0 fw-semibold">Laporan Harian Pending</h6>
                    <i class="ph ph-clipboard-text fs-3 opacity-50"></i>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                    <h3 class="fw-bold mb-0">{{ $laporanPending }} Laporan</h3>
                    <a href="{{ route('daily-reports.index') }}" class="btn btn-sm btn-light text-warning fw-bold px-3">Cek Laporan</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Alert Stok Kritis -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom pt-3 pb-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-danger"><i class="ph ph-warning-circle me-2"></i> Alert Stok Kritis</h6>
                <a href="{{ route('master.products.index') }}" class="text-decoration-none small fw-medium">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="ps-4">Produk</th>
                                <th class="text-center">Sisa Stok</th>
                                <th class="text-center pe-4">Minimal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stokKritis as $product)
                            <tr>
                                <td class="ps-4 fw-medium" style="color: #1E293B;">{{ $product->name }}</td>
                                <td class="text-center"><span class="badge bg-danger rounded-pill px-3">{{ $product->current_stock }}</span></td>
                                <td class="text-center pe-4">{{ $product->stock_minimum }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center py-5 text-success">
                                    <i class="ph ph-check-circle fs-2 d-block mb-2"></i>
                                    Semua stok dalam kondisi aman.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Akses Cepat (Shortcut) -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom pt-3 pb-3">
                <h6 class="fw-bold mb-0" style="color: #1E293B;"><i class="ph ph-lightning me-2"></i> Akses Cepat</h6>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-6">
                        <a href="{{ route('pos.session.index') }}" class="card border text-decoration-none h-100 hover-shadow transition" style="background-color: #F8FAFC;">
                            <div class="card-body text-center p-3">
                                <i class="ph ph-desktop fs-1 text-primary mb-2"></i>
                                <div class="fw-semibold text-dark" style="font-size: 0.9rem;">Buka POS</div>
                            </div>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('inventory.po.create') }}" class="card border text-decoration-none h-100 hover-shadow transition" style="background-color: #F8FAFC;">
                            <div class="card-body text-center p-3">
                                <i class="ph ph-shopping-cart fs-1 text-success mb-2"></i>
                                <div class="fw-semibold text-dark" style="font-size: 0.9rem;">Buat PO Baru</div>
                            </div>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('finance.cash-ledger.create') }}" class="card border text-decoration-none h-100 hover-shadow transition" style="background-color: #F8FAFC;">
                            <div class="card-body text-center p-3">
                                <i class="ph ph-money fs-1 text-info mb-2"></i>
                                <div class="fw-semibold text-dark" style="font-size: 0.9rem;">Catat Kas</div>
                            </div>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('hr.payroll.index') }}" class="card border text-decoration-none h-100 hover-shadow transition" style="background-color: #F8FAFC;">
                            <div class="card-body text-center p-3">
                                <i class="ph ph-users fs-1 text-warning mb-2"></i>
                                <div class="fw-semibold text-dark" style="font-size: 0.9rem;">Payroll Gaji</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection