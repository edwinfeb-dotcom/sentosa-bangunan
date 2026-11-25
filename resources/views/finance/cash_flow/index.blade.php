@extends('layouts.app')

@section('title', 'Laporan Arus Kas')

@section('content')
<div class="page-header d-print-none">
    <div>
        <h2 class="page-title">Laporan Arus Kas (Cash Flow)</h2>
        <p class="page-subtitle">Ringkasan arus kas masuk & keluar berdasarkan kategori transaksi.</p>
    </div>
    <button type="button" onclick="window.print()" class="btn btn-outline-primary">
        <i class="ph ph-printer"></i> Cetak Laporan
    </button>
</div>

<div class="card mb-4 d-print-none">
    <div class="card-body p-3">
        <form action="{{ route('finance.cash-flow.index') }}" method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Dari Tanggal</label>
                <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Dompet / Rekening</label>
                <select name="wallet_id" class="form-select">
                    <option value="">Semua Dompet</option>
                    @foreach($wallets as $w)
                        <option value="{{ $w->id }}" {{ $walletId == $w->id ? 'selected' : '' }}>{{ $w->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Tampilkan</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card" style="border-left: 4px solid #10B981;">
            <div class="stat-label text-success">Total Arus Masuk</div>
            <div class="stat-value">Rp {{ number_format($totalIn, 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card" style="border-left: 4px solid #EF4444;">
            <div class="stat-label text-danger">Total Arus Keluar</div>
            <div class="stat-value">Rp {{ number_format($totalOut, 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card" style="border-left: 4px solid #1E3A8A;">
            <div class="stat-label text-primary">Arus Kas Bersih (Surplus/Defisit)</div>
            <div class="stat-value {{ $netCashFlow >= 0 ? 'text-primary' : 'text-danger' }}">
                Rp {{ number_format($netCashFlow, 0, ',', '.') }}
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header text-success"><i class="ph ph-arrow-down-left me-1"></i>Rincian Pemasukan</div>
            <div class="card-body p-0">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Kategori</th>
                            <th class="text-center">Transaksi</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($inflowsByCategory as $row)
                            <tr>
                                <td class="fw-semibold">{{ $row['label'] }}</td>
                                <td class="text-center">{{ $row['count'] }}</td>
                                <td class="text-end fw-bold text-success">Rp {{ number_format($row['amount'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center py-4 text-muted">Tidak ada pemasukan pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header text-danger"><i class="ph ph-arrow-up-right me-1"></i>Rincian Pengeluaran</div>
            <div class="card-body p-0">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Kategori</th>
                            <th class="text-center">Transaksi</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($outflowsByCategory as $row)
                            <tr>
                                <td class="fw-semibold">{{ $row['label'] }}</td>
                                <td class="text-center">{{ $row['count'] }}</td>
                                <td class="text-end fw-bold text-danger">Rp {{ number_format($row['amount'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center py-4 text-muted">Tidak ada pengeluaran pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
