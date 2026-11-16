@extends('layouts.app')

@section('title', 'Pusat Kendali')

@section('content')
<!-- Header & Filter -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1" style="color: #1E293B;">Ringkasan Toko</h4>
        <p class="text-muted mb-0" style="font-size: 0.875rem;">Pantau performa dan operasional toko Anda.</p>
    </div>
    
    <form action="{{ route('dashboard') }}" method="GET" class="d-flex align-items-center bg-white border rounded px-2 py-1 shadow-sm">
        <i class="ph ph-calendar-blank ms-2 text-muted"></i>
        <select name="filter" class="form-select form-select-sm border-0 shadow-none fw-medium" onchange="this.form.submit()" style="cursor: pointer; width: 180px;">
            <option value="today" {{ $filter == 'today' ? 'selected' : '' }}>Hari Ini</option>
            <option value="this_month" {{ $filter == 'this_month' ? 'selected' : '' }}>Bulan Ini</option>
            <option value="all" {{ $filter == 'all' ? 'selected' : '' }}>Semua Periode</option>
        </select>
    </form>
</div>

<!-- Kartu Baris 1: Transaksi & Laporan -->
<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100" style="background-color: #F0F9FF;">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <small class="text-primary fw-semibold">Total Transaksi</small>
                    <i class="ph ph-shopping-cart text-primary fs-5"></i>
                </div>
                <h4 class="fw-bold mb-0 text-dark">{{ number_format($totalTransactions, 0, ',', '.') }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100" style="background-color: #F5F3FF;">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <small class="text-purple fw-semibold" style="color: #8B5CF6;">Hari Tercatat</small>
                    <i class="ph ph-calendar-check fs-5" style="color: #8B5CF6;"></i>
                </div>
                <h4 class="fw-bold mb-0 text-dark">{{ $recordedDays }} hari</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100" style="background-color: #F0FDF4;">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <small class="text-success fw-semibold">Laporan Tervalidasi</small>
                    <i class="ph ph-check-circle text-success fs-5"></i>
                </div>
                <h4 class="fw-bold mb-0 text-dark">{{ $verifiedReports }} Laporan</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100" style="background-color: #FEF2F2;">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <small class="text-danger fw-semibold">Total Pengeluaran</small>
                    <i class="ph ph-wallet text-danger fs-5"></i>
                </div>
                <h4 class="fw-bold mb-0 text-dark">Rp {{ number_format($totalExpenses, 0, ',', '.') }}</h4>
            </div>
        </div>
    </div>
</div>

<!-- Kartu Baris 2: Keuangan & Kas -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <small class="text-muted fw-semibold d-block mb-1">Total Omzet</small>
                <h5 class="fw-bold text-dark mb-0">Rp {{ number_format($omzet, 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <small class="text-muted fw-semibold d-block mb-1">Margin Bersih</small>
                <h5 class="fw-bold text-success mb-0">Rp {{ number_format($netMargin, 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <small class="text-muted fw-semibold d-block mb-1">Kas Tunai</small>
                <h5 class="fw-bold text-dark mb-0">Rp {{ number_format($kasTunai, 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
                <small class="text-muted fw-semibold d-block mb-1">Kas Bank / Transfer</small>
                <h5 class="fw-bold text-dark mb-0">Rp {{ number_format($kasBank, 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Kiri: Informasi Persediaan -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom pt-3 pb-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0" style="color: #1E293B;"><i class="ph ph-package me-2"></i> Informasi Persediaan</h6>
                <a href="{{ route('master.products.index') }}" class="text-decoration-none small">Lihat Produk</a>
            </div>
            <div class="card-body p-4">
                <div class="row g-3 text-center mb-4">
                    <div class="col-4 border-end">
                        <h6 class="text-muted mb-1" style="font-size: 0.8rem;">Supplier</h6>
                        <h4 class="fw-bold text-dark mb-0">{{ $totalSuppliers }}</h4>
                    </div>
                    <div class="col-4 border-end">
                        <h6 class="text-muted mb-1" style="font-size: 0.8rem;">Jenis Barang</h6>
                        <h4 class="fw-bold text-dark mb-0">{{ $totalProducts }}</h4>
                    </div>
                    <div class="col-4">
                        <h6 class="text-muted mb-1" style="font-size: 0.8rem;">Belanja Hari Ini</h6>
                        <h5 class="fw-bold text-dark mb-0">Rp {{ number_format($belanjaHariIni, 0, ',', '.') }}</h5>
                    </div>
                </div>
                
                <h6 class="fw-bold text-danger mb-3 mt-4"><i class="ph ph-warning-circle me-1"></i> Stok Menipis / Perlu Dibeli</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <tbody>
                            @forelse($stokKritis as $product)
                            <tr>
                                <td class="ps-0 fw-medium border-0">{{ $product->name }}</td>
                                <td class="text-center border-0">
                                    <span class="badge bg-danger rounded-pill">{{ $product->current_stock }} Tersisa</span>
                                </td>
                                <td class="text-end border-0">
                                    <small class="text-muted">Min: {{ $product->stock_minimum }}</small>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-success border-0 py-3">Semua stok aman.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Kanan: Akses Cepat & Status -->
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
                                <div class="fw-semibold text-dark" style="font-size: 0.9rem;">Buat PO</div>
                            </div>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('daily-reports.index') }}" class="card border text-decoration-none h-100 hover-shadow transition" style="background-color: #F8FAFC;">
                            <div class="card-body text-center p-3">
                                <i class="ph ph-clipboard-text fs-1 text-warning mb-2"></i>
                                <div class="fw-semibold text-dark" style="font-size: 0.9rem;">Laporan Harian</div>
                            </div>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('hr.payroll.index') }}" class="card border text-decoration-none h-100 hover-shadow transition" style="background-color: #F8FAFC;">
                            <div class="card-body text-center p-3">
                                <i class="ph ph-users fs-1 text-info mb-2"></i>
                                <div class="fw-semibold text-dark" style="font-size: 0.9rem;">Payroll</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- PELANGGAN FAVORIT (BINTANG GOLD) -->
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white border-bottom pt-3 pb-3">
        <h6 class="fw-bold mb-0" style="color: #1E293B;"><i class="ph ph-star text-warning me-2"></i> Pelanggan Favorit (Bintang Gold)</h6>
    </div>
    <div class="card-body p-4">
        <div class="row g-3">
            @forelse($topCustomers as $cust)
            <div class="col-md-4">
                <div class="d-flex align-items-center gap-3 p-3 border rounded bg-light">
                    <div class="bg-warning text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 45px; height: 45px;">
                        {{ substr($cust->name, 0, 1) }}
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">{{ $cust->name }}</h6>
                        <div class="text-warning fw-bold" style="font-size: 0.85rem;">
                            <i class="ph-fill ph-star"></i> {{ $cust->points }} Bintang
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12 text-center text-muted py-3">Belum ada pelanggan dengan poin.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection