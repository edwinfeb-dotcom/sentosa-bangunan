@extends('layouts.app')

@section('title', 'Buku Kas')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #1E293B;">Buku Kas</h4>
        <p class="text-muted mb-0" style="font-size: 0.875rem;">Catatan arus kas masuk dan keluar toko.</p>
    </div>
    <a href="{{ route('finance.cash-ledger.create') }}" class="btn btn-primary d-flex align-items-center gap-2">
        <i class="ph ph-plus"></i> Catat Kas Baru
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="ph ph-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Ringkasan Saldo (Card Modern) -->
<div class="row mb-4 g-3">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100" style="background-color: #ECFDF5; border-left: 4px solid #10B981 !important;">
            <div class="card-body">
                <h6 class="text-success mb-2 fw-semibold">Total Kas Masuk</h6>
                <h4 class="fw-bold text-dark mb-0">Rp {{ number_format($totalIn, 0, ',', '.') }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100" style="background-color: #FEF2F2; border-left: 4px solid #EF4444 !important;">
            <div class="card-body">
                <h6 class="text-danger mb-2 fw-semibold">Total Kas Keluar</h6>
                <h4 class="fw-bold text-dark mb-0">Rp {{ number_format($totalOut, 0, ',', '.') }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100" style="background-color: #EFF6FF; border-left: 4px solid #1E3A8A !important;">
            <div class="card-body">
                <h6 class="text-primary mb-2 fw-semibold">Saldo Akhir Kas</h6>
                <h4 class="fw-bold text-dark mb-0">Rp {{ number_format($balance, 0, ',', '.') }}</h4>
            </div>
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
                        <th>Tipe</th>
                        <th>Nominal</th>
                        <th>Oleh</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ledgers as $ledger)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($ledger->date)->format('d/m/Y') }}</td>
                        <td class="fw-medium" style="color: #1E293B;">{{ $ledger->description }}</td>
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
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="ph ph-wallet fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            Belum ada catatan kas.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($ledgers->hasPages())
    <div class="card-footer bg-white border-top pt-3">
        {{ $ledgers->links() }}
    </div>
    @endif
</div>
@endsection