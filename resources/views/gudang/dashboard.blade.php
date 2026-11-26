@extends('layouts.app')

@section('title', 'Beranda Gudang')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Workspace Operasional Gudang</h2>
        <p class="page-subtitle">Petugas: <strong>{{ auth()->user()->name }}</strong> • {{ \Carbon\Carbon::now('Asia/Pontianak')->translatedFormat('l, d F Y') }}</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('inventory.stock.index') }}" class="btn btn-light border">
            <i class="ph ph-stack"></i> Kartu Stok &amp; Opname
        </a>
        <a href="{{ route('inventory.po.create') }}" class="btn btn-primary">
            <i class="ph ph-plus"></i> Buat PO Barang
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-label">Produk Aktif</div>
            <div class="stat-value">{{ $totalProducts }} Item</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-label">Stok Menipis</div>
            <div class="stat-value {{ $stokKritis->count() > 0 ? 'text-danger' : 'text-success' }}">{{ $stokKritis->count() }} Item</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-label">PO Menunggu Datang</div>
            <div class="stat-value text-warning">{{ $orderedPoCount }} PO</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-label">Pesanan Online Masuk</div>
            <div class="stat-value text-accent">{{ $pendingOnlineOrders }} Order</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="ph ph-warning-circle text-danger me-2"></i>Barang Stok Kritis / Perlu Restock</span>
                <a href="{{ route('inventory.stock.index') }}" class="small text-decoration-none fw-semibold text-primary">Stock Opname</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Lokasi Rak</th>
                                <th class="text-end">Sisa Stok</th>
                                <th class="text-end">Minimal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stokKritis as $p)
                                <tr>
                                    <td class="fw-semibold">{{ $p->name }}</td>
                                    <td class="text-muted small">{{ $p->rack_location ?? '-' }}</td>
                                    <td class="text-end"><span class="badge bg-danger bg-opacity-10 text-danger">{{ $p->current_stock }} {{ $p->unit }}</span></td>
                                    <td class="text-end text-muted small">{{ $p->stock_minimum }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center py-4 text-success">Semua stok gudang dalam kondisi aman.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="ph ph-shopping-cart text-accent me-2"></i>PO Menunggu Penerimaan</span>
                <a href="{{ route('inventory.po.index') }}" class="small text-decoration-none fw-semibold text-primary">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                <table class="table">
                    <tbody>
                        @forelse($pendingPOs as $po)
                            <tr>
                                <td>
                                    <div class="fw-bold font-monospace">{{ $po->po_number }}</div>
                                    <small class="text-muted">{{ $po->supplier->name ?? '-' }}</small>
                                </td>
                                <td class="text-end">
                                    <form action="{{ route('inventory.po.receive', $po->id) }}" method="POST" onsubmit="return confirm('Terima semua barang untuk PO ini?')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">Terima</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td class="text-center py-4 text-muted small">Tidak ada PO menunggu penerimaan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="ph ph-fingerprint text-accent me-2"></i>Absensi Hari Ini</div>
            <div class="card-body p-3 text-center">
                @if(!$attendanceToday)
                    <a href="{{ route('hr.attendance.index') }}" class="btn btn-dark w-100">
                        <i class="ph ph-qr-code"></i> Scan QR / Clock In
                    </a>
                @elseif(!$attendanceToday->clock_out)
                    <div class="small text-success fw-bold mb-2">Sudah Clock In: {{ $attendanceToday->clock_in }} WIB</div>
                    <a href="{{ route('hr.attendance.index') }}" class="btn btn-outline-danger btn-sm w-100">Buka Halaman Absensi</a>
                @else
                    <div class="small text-success fw-bold">Absensi Selesai ({{ $attendanceToday->clock_in }} - {{ $attendanceToday->clock_out }})</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
