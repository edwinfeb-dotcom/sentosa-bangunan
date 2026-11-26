@extends('layouts.app')

@section('title', 'Pusat Kendali')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Pusat Kendali Toko</h2>
        <p class="page-subtitle">Ringkasan omzet, arus kas dompet, stok barang, dan target penjualan • {{ \Carbon\Carbon::now('Asia/Pontianak')->translatedFormat('l, d F Y') }}</p>
    </div>

    <form action="{{ route('dashboard') }}" method="GET" class="d-flex align-items-center bg-white border rounded-3 px-2 py-1">
        <i class="ph ph-calendar-blank ms-2 text-accent"></i>
        <select name="filter" class="form-select form-select-sm border-0 shadow-none fw-bold text-dark" onchange="this.form.submit()" style="cursor: pointer; min-width: 170px;">
            <option value="today" {{ $filter == 'today' ? 'selected' : '' }}>Periode: Hari Ini</option>
            <option value="this_month" {{ $filter == 'this_month' ? 'selected' : '' }}>Periode: Bulan Ini</option>
            <option value="all" {{ $filter == 'all' ? 'selected' : '' }}>Semua Periode</option>
        </select>
    </form>
</div>

@if($pendingOnlineOrders > 0)
    <div class="alert border mb-4 d-flex flex-wrap justify-content-between align-items-center gap-2" style="background-color: var(--sb-accent-soft); border-color: var(--sb-accent-border) !important; color: var(--sb-ink);">
        <div class="d-flex align-items-center gap-2">
            <i class="ph ph-bell-ringing fs-4 text-accent"></i>
            <span>Ada <strong>{{ $pendingOnlineOrders }} pesanan online baru</strong> dari Katalog Publik menunggu konfirmasi.</span>
        </div>
        <a href="{{ route('public-orders.index', ['status' => 'pending']) }}" class="btn btn-dark btn-sm">
            Proses Sekarang <i class="ph ph-arrow-right"></i>
        </a>
    </div>
@endif

<!-- KPI Row 1: Primary Precision Metrics -->
<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3">
        <div class="stat-card d-flex justify-content-between align-items-start gap-2">
            <div>
                <div class="stat-label">Total Omzet</div>
                <div class="stat-value text-dark">Rp {{ number_format($omzet, 0, ',', '.') }}</div>
            </div>
            <div class="stat-icon text-accent">
                <i class="ph ph-currency-circle-dollar"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card d-flex justify-content-between align-items-start gap-2">
            <div>
                <div class="stat-label">Margin Bersih</div>
                <div class="stat-value text-success">Rp {{ number_format($netMargin, 0, ',', '.') }}</div>
            </div>
            <div class="stat-icon text-success">
                <i class="ph ph-trend-up"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card d-flex justify-content-between align-items-start gap-2">
            <div>
                <div class="stat-label">Transaksi ({{ $recordedDays }} hari)</div>
                <div class="stat-value">{{ number_format($totalTransactions, 0, ',', '.') }}</div>
            </div>
            <div class="stat-icon text-dark">
                <i class="ph ph-shopping-cart"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card d-flex justify-content-between align-items-start gap-2">
            <div>
                <div class="stat-label">Total Pengeluaran</div>
                <div class="stat-value text-danger">Rp {{ number_format($totalExpenses, 0, ',', '.') }}</div>
            </div>
            <div class="stat-icon text-danger">
                <i class="ph ph-wallet"></i>
            </div>
        </div>
    </div>
</div>

<!-- KPI Row 2: Secondary Channel & Audit Readout -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="stat-card" style="background-color: var(--sb-sidebar-bg);">
            <div class="stat-label">Penjualan Tunai (POS)</div>
            <div class="stat-value fs-5">Rp {{ number_format($kasTunai, 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card" style="background-color: var(--sb-sidebar-bg);">
            <div class="stat-label">Penjualan Transfer (POS)</div>
            <div class="stat-value fs-5">Rp {{ number_format($kasBank, 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card" style="background-color: var(--sb-sidebar-bg);">
            <div class="stat-label">Laporan Harian Valid</div>
            <div class="stat-value fs-5 text-success">{{ $verifiedReports }} Laporan</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card" style="background-color: var(--sb-sidebar-bg);">
            <div class="stat-label">Belanja PO Hari Ini</div>
            <div class="stat-value fs-5">Rp {{ number_format($belanjaHariIni, 0, ',', '.') }}</div>
        </div>
    </div>
</div>

<!-- Progress Target Penjualan + Tren 7 Hari -->
<div class="row g-4 mb-4">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="ph ph-target text-accent me-2"></i>Strategi &amp; Target Penjualan</span>
                @if(in_array(auth()->user()->role, ['owner', 'admin']))
                    <a href="{{ route('setting.targets.index') }}" class="btn btn-light border btn-sm">Atur Target</a>
                @endif
            </div>
            <div class="card-body p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-end mb-2">
                        <div>
                            <div class="small text-muted">Realisasi Bulan Ini</div>
                            <div class="fs-4 fw-bold text-dark tabular-nums">Rp {{ number_format($monthlyOmzet, 0, ',', '.') }}</div>
                        </div>
                        <div class="text-end">
                            <div class="small text-muted">Target</div>
                            <div class="fw-bold text-dark tabular-nums">
                                {{ $targetAmount > 0 ? 'Rp ' . number_format($targetAmount, 0, ',', '.') : 'Belum Diatur' }}
                            </div>
                        </div>
                    </div>

                    <div class="progress mb-2" style="height: 10px; border-radius: 999px; background-color: var(--sb-surface-muted);">
                        <div class="progress-bar {{ $targetPercentage >= 100 ? 'bg-success' : 'bg-primary' }}"
                             role="progressbar"
                             style="width: {{ $targetPercentage }}%;"
                             aria-valuenow="{{ $targetPercentage }}" aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div>
                    <div class="d-flex justify-content-between small tabular-nums">
                        <span class="fw-bold {{ $targetPercentage >= 100 ? 'text-success' : 'text-accent' }}">{{ $targetPercentage }}% Tercapai</span>
                        @if($targetAmount > $monthlyOmzet)
                            <span class="text-muted">Sisa: Rp {{ number_format($targetAmount - $monthlyOmzet, 0, ',', '.') }}</span>
                        @elseif($targetAmount > 0)
                            <span class="text-success fw-semibold">Target terlampaui!</span>
                        @endif
                    </div>
                    @if($dailyTargetNeeded > 0 || ($salesTarget && $salesTarget->focusCategory))
                        <div class="mt-2 pt-2 border-top d-flex flex-wrap justify-content-between gap-1 small text-muted">
                            @if($dailyTargetNeeded > 0)
                                <span class="tabular-nums">Pace ideal: <strong class="text-dark">Rp {{ number_format($dailyTargetNeeded, 0, ',', '.') }}/hr</strong></span>
                            @endif
                            @if($salesTarget && $salesTarget->focusCategory)
                                <span class="badge bg-light text-dark border">Fokus: {{ $salesTarget->focusCategory->name }}</span>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="mt-4 pt-3 border-top">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 0.68rem; letter-spacing: 0.08em;">Saldo Dompet &amp; Rekening</span>
                        @if(in_array(auth()->user()->role, ['owner', 'admin']))
                            <a href="{{ route('finance.wallets.index') }}" class="small text-decoration-none fw-semibold text-primary">Kelola</a>
                        @endif
                    </div>
                    <div class="d-flex flex-column gap-2">
                        @foreach($wallets as $w)
                            <div class="d-flex justify-content-between align-items-center border rounded-3 px-3 py-2 small" style="background-color: var(--sb-sidebar-bg);">
                                <span class="fw-semibold text-dark">{{ $w->name }}</span>
                                <span class="fw-bold text-dark tabular-nums">Rp {{ number_format($w->current_balance, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="ph ph-chart-bar text-accent me-2"></i>Tren Omzet 7 Hari Terakhir</span>
                <a href="{{ route('reports.sales.index') }}" class="small text-decoration-none fw-semibold text-primary">Laporan Detail</a>
            </div>
            <div class="card-body p-4 d-flex flex-column justify-content-end">
                <div class="row g-2 align-items-end text-center pt-4" style="min-height: 220px;">
                    @foreach($salesTrend as $day)
                        @php $heightPct = round(($day['total'] / $maxTrend) * 100); @endphp
                        <div class="col trend-bar-col">
                            <div class="trend-tooltip">Rp {{ number_format($day['total'], 0, ',', '.') }}</div>
                            <div class="small fw-semibold text-muted mb-1 tabular-nums" style="font-size: 0.68rem;">
                                {{ $day['total'] > 0 ? number_format($day['total'] / 1000, 0, ',', '.') . 'k' : '0' }}
                            </div>
                            <div class="trend-bar-track rounded-top d-flex align-items-end justify-content-center mx-auto" style="height: 145px; width: 34px;">
                                <div class="trend-bar-fill rounded-top w-100" style="height: {{ max(6, $heightPct) }}%; opacity: {{ $day['total'] > 0 ? '1' : '0.2' }};"></div>
                            </div>
                            <div class="fw-bold small mt-2 text-dark">{{ $day['label'] }}</div>
                            <div class="text-muted tabular-nums" style="font-size: 0.7rem;">{{ $day['date'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="ph ph-package me-2 text-accent"></i>Informasi Persediaan</span>
                <a href="{{ route('master.products.index') }}" class="small text-decoration-none fw-semibold text-primary">Lihat Semua</a>
            </div>
            <div class="card-body p-4">
                <div class="row g-3 text-center mb-4">
                    <div class="col-6">
                        <div class="p-3 rounded-3 border" style="background-color: var(--sb-sidebar-bg);">
                            <div class="text-muted small">Total Supplier</div>
                            <div class="fs-4 fw-bold text-dark tabular-nums">{{ $totalSuppliers }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded-3 border" style="background-color: var(--sb-sidebar-bg);">
                            <div class="text-muted small">Total Produk</div>
                            <div class="fs-4 fw-bold text-dark tabular-nums">{{ $totalProducts }}</div>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold text-danger mb-3 text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.06em;">
                    <i class="ph ph-warning-circle me-1"></i>Stok Menipis / Perlu Reorder
                </h6>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <tbody>
                            @forelse($stokKritis as $product)
                                <tr>
                                    <td class="ps-0 fw-medium">{{ $product->name }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-danger bg-opacity-10 text-danger">{{ $product->current_stock }} {{ $product->unit }}</span>
                                    </td>
                                    <td class="text-end pe-0 text-muted small">Min: {{ $product->stock_minimum }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-success py-3">Semua stok dalam kondisi aman.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <i class="ph ph-lightning me-2 text-accent"></i>Akses Cepat Operasional
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-6 col-sm-4">
                        @if(in_array(auth()->user()->role, ['owner', 'admin', 'kasir']))
                            <a href="{{ route('pos.session.index') }}" class="quick-tile">
                                <i class="ph ph-desktop fs-2 text-accent mb-2 d-block"></i>
                                <div class="fw-semibold text-dark small">Buka POS</div>
                            </a>
                        @else
                            <a href="{{ route('master.products.index') }}" class="quick-tile">
                                <i class="ph ph-package fs-2 text-accent mb-2 d-block"></i>
                                <div class="fw-semibold text-dark small">Data Produk</div>
                            </a>
                        @endif
                    </div>
                    <div class="col-6 col-sm-4">
                        <a href="{{ route('inventory.po.create') }}" class="quick-tile">
                            <i class="ph ph-shopping-cart fs-2 text-dark mb-2 d-block"></i>
                            <div class="fw-semibold text-dark small">Buat PO</div>
                        </a>
                    </div>
                    <div class="col-6 col-sm-4">
                        <a href="{{ route('daily-reports.create') }}" class="quick-tile">
                            <i class="ph ph-clipboard-text fs-2 text-dark mb-2 d-block"></i>
                            <div class="fw-semibold text-dark small">Laporan Harian</div>
                        </a>
                    </div>
                    <div class="col-6 col-sm-4">
                        <a href="{{ route('public-orders.index') }}" class="quick-tile">
                            <i class="ph ph-globe fs-2 text-dark mb-2 d-block"></i>
                            <div class="fw-semibold text-dark small">Pesanan Online</div>
                        </a>
                    </div>
                    <div class="col-6 col-sm-4">
                        <a href="{{ route('finance.cash-ledger.create') }}" class="quick-tile">
                            <i class="ph ph-wallet fs-2 text-dark mb-2 d-block"></i>
                            <div class="fw-semibold text-dark small">Catat Kas</div>
                        </a>
                    </div>
                    <div class="col-6 col-sm-4">
                        @if(in_array(auth()->user()->role, ['owner', 'admin']))
                            <a href="{{ route('hr.payroll.index') }}" class="quick-tile">
                                <i class="ph ph-money fs-2 text-dark mb-2 d-block"></i>
                                <div class="fw-semibold text-dark small">Payroll</div>
                            </a>
                        @else
                            <a href="{{ route('hr.attendance.index') }}" class="quick-tile">
                                <i class="ph ph-qr-code fs-2 text-dark mb-2 d-block"></i>
                                <div class="fw-semibold text-dark small">Absensi QR</div>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pelanggan Favorit -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="ph ph-star text-warning me-2"></i>Pelanggan Favorit (Bintang Gold)</span>
        <a href="{{ route('master.customers.index') }}" class="small text-decoration-none fw-semibold text-primary">Lihat Pelanggan</a>
    </div>
    <div class="card-body p-4">
        <div class="row g-3">
            @forelse($topCustomers as $cust)
                <div class="col-md-4">
                    <div class="d-flex align-items-center gap-3 p-3 border rounded-3" style="background-color: var(--sb-sidebar-bg);">
                        <div class="bg-dark text-white rounded-3 d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 42px; height: 42px;">
                            {{ strtoupper(substr($cust->name, 0, 1)) }}
                        </div>
                        <div>
                            <div class="fw-bold text-dark">{{ $cust->name }}</div>
                            <div class="text-warning-emphasis fw-bold small tabular-nums">
                                <i class="ph-fill ph-star text-warning"></i> {{ $cust->points }} Bintang
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center text-muted py-3">Belum ada pelanggan dengan poin loyalitas.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
