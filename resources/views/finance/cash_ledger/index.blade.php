@extends('layouts.app')

@section('title', 'Buku Kas')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Buku Kas</h2>
        <p class="page-subtitle">Catatan transaksi kas masuk dan kas keluar seluruh dompet toko.</p>
    </div>
    <div class="d-flex flex-wrap gap-2 d-print-none">
        <a href="{{ route('finance.cash-ledger.export', request()->query()) }}" class="btn btn-outline-success">
            <i class="ph ph-file-xls"></i> Export CSV
        </a>
        <button type="button" onclick="window.print()" class="btn btn-light border">
            <i class="ph ph-printer"></i> Cetak
        </button>
        @if(in_array(auth()->user()->role, ['owner', 'admin']))
        <a href="{{ route('finance.cash-flow.index') }}" class="btn btn-outline-primary">
            <i class="ph ph-arrows-left-right"></i> Laporan Arus Kas
        </a>
        @endif
        <a href="{{ route('finance.cash-ledger.create') }}" class="btn btn-primary">
            <i class="ph ph-plus"></i> Catat Kas Baru
        </a>
    </div>
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body p-3">
        <form action="{{ route('finance.cash-ledger.index') }}" method="GET" class="row g-2 align-items-end">
            <div class="col-6 col-md-2">
                <label class="form-label">Dari Tanggal</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label">Dompet / Rekening</label>
                <select name="wallet_id" class="form-select">
                    <option value="">Semua Dompet</option>
                    @foreach($wallets as $w)
                        <option value="{{ $w->id }}" {{ request('wallet_id') == $w->id ? 'selected' : '' }}>{{ $w->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Tipe</label>
                <select name="type" class="form-select">
                    <option value="">Semua</option>
                    <option value="in" {{ request('type') == 'in' ? 'selected' : '' }}>Kas Masuk</option>
                    <option value="out" {{ request('type') == 'out' ? 'selected' : '' }}>Kas Keluar</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
                <a href="{{ route('finance.cash-ledger.index') }}" class="btn btn-light border">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="row mb-4 g-3">
    <div class="col-md-4">
        <div class="stat-card" style="border-left: 4px solid #10B981;">
            <div class="stat-label text-success">Total Kas Masuk</div>
            <div class="stat-value">Rp {{ number_format($totalIn, 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card" style="border-left: 4px solid #EF4444;">
            <div class="stat-label text-danger">Total Kas Keluar</div>
            <div class="stat-value">Rp {{ number_format($totalOut, 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card" style="border-left: 4px solid #1E3A8A;">
            <div class="stat-label text-primary">Selisih Periode</div>
            <div class="stat-value">Rp {{ number_format($balance, 0, ',', '.') }}</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Keterangan</th>
                        <th>Dompet</th>
                        <th>Tipe</th>
                        <th>Nominal</th>
                        <th>Oleh</th>
                        <th class="text-end d-print-none">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ledgers as $ledger)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($ledger->date)->format('d/m/Y') }}</td>
                        <td>
                            <div class="fw-medium text-dark">{{ $ledger->description }}</div>
                            @if($ledger->category)
                                <small class="text-muted text-capitalize">{{ str_replace('_', ' ', $ledger->category) }}</small>
                            @endif
                        </td>
                        <td><span class="badge bg-light text-dark border">{{ $ledger->wallet->name ?? 'Umum' }}</span></td>
                        <td>
                            @if($ledger->type === 'in')
                                <span class="badge bg-success bg-opacity-10 text-success">Kas Masuk</span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger">Kas Keluar</span>
                            @endif
                        </td>
                        <td class="fw-bold {{ $ledger->type === 'in' ? 'text-success' : 'text-danger' }}">
                            {{ $ledger->type === 'in' ? '+' : '-' }} Rp {{ number_format($ledger->amount, 0, ',', '.') }}
                        </td>
                        <td class="text-muted">{{ $ledger->user->name ?? '-' }}</td>
                        <td class="text-end d-print-none">
                            @if($ledger->reference_type === null)
                                <form action="{{ route('finance.cash-ledger.destroy', $ledger->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus catatan kas manual ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light border text-danger" title="Hapus Catatan Manual">
                                        <i class="ph ph-trash"></i>
                                    </button>
                                </form>
                            @else
                                <small class="text-muted">Otomatis</small>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="ph ph-wallet fs-1 d-block mb-2 opacity-50"></i>
                            Belum ada catatan kas.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($ledgers->hasPages())
        <div class="card-footer bg-white">{{ $ledgers->links() }}</div>
    @endif
</div>
@endsection
