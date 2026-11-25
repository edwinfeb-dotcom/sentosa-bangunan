@extends('layouts.app')

@section('title', 'Beranda Kasir')

@section('content')
<div class="page-header">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h2 class="page-title mb-0">Workspace Khusus Kasir</h2>
            <span class="badge {{ $activeSession ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger' }}">
                {{ $activeSession ? 'Shift Aktif sejak ' . $activeSession->start_time->format('H:i') : 'Belum Buka Sesi' }}
            </span>
        </div>
        <p class="page-subtitle">Petugas: <strong>{{ auth()->user()->name }}</strong> • {{ \Carbon\Carbon::now('Asia/Pontianak')->translatedFormat('l, d F Y') }}</p>
    </div>

    <div>
        @if($activeSession)
            <a href="{{ route('pos.index') }}" class="btn btn-primary btn-lg">
                <i class="ph ph-desktop"></i> Masuk Layar Kasir (POS)
            </a>
        @else
            <a href="{{ route('pos.session.index') }}" class="btn btn-primary btn-lg">
                <i class="ph ph-lock-key-open"></i> Buka Sesi Kasir
            </a>
        @endif
    </div>
</div>

@if($pendingOnlineOrders > 0)
    <div class="alert border mb-4 d-flex flex-wrap justify-content-between align-items-center gap-2" style="background-color: var(--sb-accent-soft); border-color: var(--sb-accent-border) !important; color: var(--sb-ink);">
        <div class="d-flex align-items-center gap-2">
            <i class="ph ph-bell-ringing fs-4 text-accent"></i>
            <span>Terdapat <strong>{{ $pendingOnlineOrders }} pesanan online baru</strong> dari Katalog Pembeli yang perlu segera dikemas.</span>
        </div>
        <a href="{{ route('public-orders.index', ['status' => 'pending']) }}" class="btn btn-dark btn-sm">
            Proses Pesanan <i class="ph ph-arrow-right"></i>
        </a>
    </div>
@endif

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="stat-card d-flex justify-content-between align-items-start gap-2">
            <div>
                <div class="stat-label">Status Shift Kasir</div>
                <div class="stat-value {{ $activeSession ? 'text-success' : 'text-danger' }}">
                    {{ $activeSession ? 'Sesi Terbuka' : 'Belum Buka' }}
                </div>
            </div>
            <div class="stat-icon {{ $activeSession ? 'text-success' : 'text-danger' }}">
                <i class="ph ph-cash-register"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card d-flex justify-content-between align-items-start gap-2">
            <div>
                <div class="stat-label">Estimasi Laci Shift Ini</div>
                <div class="stat-value text-dark">Rp {{ number_format($expectedDrawer, 0, ',', '.') }}</div>
            </div>
            <div class="stat-icon text-accent">
                <i class="ph ph-currency-circle-dollar"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card d-flex justify-content-between align-items-start gap-2">
            <div>
                <div class="stat-label">Omzet Toko Hari Ini</div>
                <div class="stat-value">Rp {{ number_format($todayOmzet, 0, ',', '.') }}</div>
            </div>
            <div class="stat-icon text-dark">
                <i class="ph ph-shopping-bag"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card d-flex justify-content-between align-items-start gap-2">
            <div>
                <div class="stat-label">Transaksi Hari Ini</div>
                <div class="stat-value">{{ $todayCount }} Struk</div>
            </div>
            <div class="stat-icon text-dark">
                <i class="ph ph-receipt"></i>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header">
                <i class="ph ph-squares-four text-accent me-2"></i>Menu Cepat Operasional Kasir
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-6 col-sm-4 col-md-3">
                        <a href="{{ route('pos.session.index') }}" class="quick-tile">
                            <i class="ph ph-desktop fs-2 text-accent mb-1 d-block"></i>
                            <div class="fw-semibold text-dark small">Kasir (POS)</div>
                        </a>
                    </div>
                    <div class="col-6 col-sm-4 col-md-3">
                        <a href="{{ route('public-orders.index') }}" class="quick-tile">
                            <i class="ph ph-globe fs-2 text-dark mb-1 d-block"></i>
                            <div class="fw-semibold text-dark small">Pesanan Online</div>
                        </a>
                    </div>
                    <div class="col-6 col-sm-4 col-md-3">
                        <a href="{{ route('master.products.index') }}" class="quick-tile">
                            <i class="ph ph-package fs-2 text-dark mb-1 d-block"></i>
                            <div class="fw-semibold text-dark small">Produk &amp; Harga</div>
                        </a>
                    </div>
                    <div class="col-6 col-sm-4 col-md-3">
                        <a href="{{ route('reports.sales.index') }}" class="quick-tile">
                            <i class="ph ph-chart-line-up fs-2 text-dark mb-1 d-block"></i>
                            <div class="fw-semibold text-dark small">Lap. Penjualan</div>
                        </a>
                    </div>
                    <div class="col-6 col-sm-4 col-md-3">
                        <a href="{{ route('inventory.invoices.index') }}" class="quick-tile">
                            <i class="ph ph-receipt fs-2 text-dark mb-1 d-block"></i>
                            <div class="fw-semibold text-dark small">Nota Supplier</div>
                        </a>
                    </div>
                    <div class="col-6 col-sm-4 col-md-3">
                        <a href="{{ route('inventory.returns.index') }}" class="quick-tile">
                            <i class="ph ph-arrow-u-up-left fs-2 text-dark mb-1 d-block"></i>
                            <div class="fw-semibold text-dark small">Retur Supplier</div>
                        </a>
                    </div>
                    <div class="col-6 col-sm-4 col-md-3">
                        <a href="{{ route('daily-reports.create') }}" class="quick-tile">
                            <i class="ph ph-clipboard-text fs-2 text-dark mb-1 d-block"></i>
                            <div class="fw-semibold text-dark small">Laporan Harian</div>
                        </a>
                    </div>
                    <div class="col-6 col-sm-4 col-md-3">
                        <a href="{{ route('finance.cash-ledger.create') }}" class="quick-tile">
                            <i class="ph ph-wallet fs-2 text-dark mb-1 d-block"></i>
                            <div class="fw-semibold text-dark small">Catat Kas Kecil</div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="ph ph-fingerprint text-accent me-2"></i>Absensi Shift Anda</div>
            <div class="card-body p-4 text-center d-flex flex-column justify-content-center">
                @if(!$attendanceToday)
                    <p class="text-muted small mb-3">Anda belum melakukan Clock In hari ini.</p>
                    <form action="{{ route('hr.attendance.clock-in') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-dark w-100">
                            <i class="ph ph-sign-in"></i> Clock In Sekarang
                        </button>
                    </form>
                @elseif(!$attendanceToday->clock_out)
                    <div class="badge bg-success bg-opacity-10 text-success px-3 py-2 mb-3 mx-auto">
                        Masuk: {{ $attendanceToday->clock_in }} WIB
                    </div>
                    <form action="{{ route('hr.attendance.clock-out') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="ph ph-sign-out"></i> Clock Out (Pulang)
                        </button>
                    </form>
                @else
                    <div class="p-3 rounded-3 border bg-success bg-opacity-10 text-success">
                        <div class="fw-bold mb-1">Absensi Hari Ini Selesai</div>
                        <div class="small tabular-nums">Masuk: {{ $attendanceToday->clock_in }} • Pulang: {{ $attendanceToday->clock_out }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="ph ph-receipt text-accent me-2"></i>Transaksi POS Hari Ini</span>
                <a href="{{ route('reports.sales.index') }}" class="small text-decoration-none fw-semibold text-primary">Lihat Rekap</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>No. Invoice</th>
                                <th>Jam</th>
                                <th>Total</th>
                                <th>Metode</th>
                                <th class="text-end">Struk</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentSales as $sale)
                                <tr>
                                    <td class="fw-bold text-dark font-monospace">{{ $sale->invoice_number }}</td>
                                    <td class="text-muted small">{{ $sale->created_at->format('H:i') }}</td>
                                    <td class="fw-bold">Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</td>
                                    <td><span class="badge bg-light text-dark border text-uppercase">{{ $sale->payments->first()?->method ?? '-' }}</span></td>
                                    <td class="text-end">
                                        <a href="{{ route('pos.receipt', $sale->id) }}" class="btn btn-sm btn-light border">
                                            <i class="ph ph-printer"></i> Cetak
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada transaksi hari ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="ph ph-warning-circle text-danger me-2"></i>Stok Menipis di Toko</span>
                <a href="{{ route('master.products.index') }}" class="small text-decoration-none fw-semibold text-primary">Cek Produk</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th class="text-end">Sisa Stok</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stokKritis as $p)
                                <tr>
                                    <td class="fw-semibold">{{ $p->name }}</td>
                                    <td class="text-end">
                                        <span class="badge bg-danger bg-opacity-10 text-danger">{{ $p->current_stock }} {{ $p->unit }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-center py-4 text-success">Semua stok produk aman.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
