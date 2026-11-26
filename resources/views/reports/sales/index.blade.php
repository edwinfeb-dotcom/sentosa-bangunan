@extends('layouts.app')

@section('title', 'Laporan Penjualan Detail')

@section('content')
<div class="page-header d-print-none">
    <div>
        <h2 class="page-title">Laporan Penjualan Detail</h2>
        <p class="page-subtitle">Rekap omzet, HPP, dan margin keuntungan per produk & kategori.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('reports.sales.export', request()->query()) }}" class="btn btn-outline-success">
            <i class="ph ph-file-xls"></i> Export CSV/Excel
        </a>
        <button type="button" onclick="window.print()" class="btn btn-outline-primary">
            <i class="ph ph-printer"></i> Cetak Laporan
        </button>
    </div>
</div>

<!-- Filter -->
<div class="card mb-4 d-print-none">
    <div class="card-body p-3">
        <form action="{{ route('reports.sales.index') }}" method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Dari Tanggal</label>
                <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Kategori Produk</label>
                <select name="category_id" class="form-select">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Tampilkan</button>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-label">Total Unit Terjual</div>
            <div class="stat-value">{{ number_format($summary['total_qty'], 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-label">Total Omzet Penjualan</div>
            <div class="stat-value text-primary">Rp {{ number_format($summary['total_omzet'], 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-label">Total HPP (Modal)</div>
            <div class="stat-value">Rp {{ number_format($summary['total_hpp'], 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-label">Estimasi Laba Kotor</div>
            <div class="stat-value text-success">Rp {{ number_format($summary['total_profit'], 0, ',', '.') }}</div>
        </div>
    </div>
</div>

@if(($byPayment ?? collect())->isNotEmpty() || ($summary['online_count'] ?? 0) > 0)
<div class="row g-3 mb-4">
    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-body py-2 px-3 d-flex flex-wrap align-items-center gap-3 small">
                <strong class="text-muted">Per Metode Bayar:</strong>
                @foreach($byPayment ?? [] as $pm)
                    <span class="badge bg-light text-dark border px-2 py-1 text-uppercase">
                        {{ str_replace('_', ' ', $pm->method) }} ({{ $pm->trx_count }}x): <strong>Rp {{ number_format($pm->total_amount, 0, ',', '.') }}</strong>
                    </span>
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center small">
                <span class="text-muted fw-semibold">Pesanan Online ({{ $summary['online_count'] ?? 0 }}):</span>
                <strong class="text-primary">Rp {{ number_format($summary['online_omzet'] ?? 0, 0, ',', '.') }}</strong>
            </div>
        </div>
    </div>
</div>
@endif

<div class="row g-4">
    <!-- Rekap Per Kategori -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">Rekap Per Kategori</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Kategori</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Omzet</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($byCategory as $cat)
                                <tr>
                                    <td class="fw-semibold">{{ $cat['category_name'] }}</td>
                                    <td class="text-end">{{ number_format($cat['qty'], 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold text-primary">Rp {{ number_format($cat['omzet'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center py-4 text-muted">Tidak ada data penjualan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Rekap Per Produk -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header">Rekap Per Produk ({{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }})</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Kategori</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Omzet</th>
                                <th class="text-end">Laba</th>
                                <th class="text-end">Margin</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($byProduct as $row)
                                <tr>
                                    <td class="fw-semibold">{{ $row['product_name'] }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $row['category_name'] }}</span></td>
                                    <td class="text-end">{{ number_format($row['qty'], 0, ',', '.') }} {{ $row['unit'] }}</td>
                                    <td class="text-end fw-semibold">Rp {{ number_format($row['omzet'], 0, ',', '.') }}</td>
                                    <td class="text-end text-success fw-semibold">Rp {{ number_format($row['profit'], 0, ',', '.') }}</td>
                                    <td class="text-end">{{ $row['margin_pct'] }}%</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center py-5 text-muted">Belum ada penjualan pada periode ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
