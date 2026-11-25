@extends('layouts.app')

@section('title', 'Nota Supplier (Bon)')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Nota Supplier (Hutang Dagang)</h2>
        <p class="page-subtitle">Pantau tagihan jatuh tempo dan catat pembayaran cicilan atau pelunasan bon supplier.</p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-5">
        <div class="stat-card" style="border-left: 4px solid #EF4444;">
            <div class="stat-label text-danger">Total Sisa Bon (Hutang Berjalan)</div>
            <div class="stat-value fs-4">Rp {{ number_format($totalDebt, 0, ',', '.') }}</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Supplier & No. Nota</th>
                        <th>Total Tagihan</th>
                        <th>Sudah Dibayar</th>
                        <th>Sisa Bon</th>
                        <th>Jatuh Tempo</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                    <tr>
                        <td>
                            <div class="fw-semibold text-dark">{{ $invoice->supplier->name ?? '-' }}</div>
                            <small class="text-muted">{{ $invoice->invoice_number ?? 'Tanpa No. Nota' }}</small>
                        </td>
                        <td class="fw-medium">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                        <td class="text-success">Rp {{ number_format($invoice->paid_amount, 0, ',', '.') }}</td>
                        <td class="fw-bold text-danger">Rp {{ number_format($invoice->remaining_amount, 0, ',', '.') }}</td>
                        <td>{{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '-' }}</td>
                        <td>
                            @if($invoice->status === 'paid')
                                <span class="badge bg-success bg-opacity-10 text-success">Lunas</span>
                            @elseif($invoice->status === 'partial')
                                <span class="badge bg-warning bg-opacity-10 text-warning">Sebagian</span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger">Belum Bayar</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($invoice->status !== 'paid')
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#payModal{{ $invoice->id }}">
                                    <i class="ph ph-wallet"></i> Bayar
                                </button>
                            @else
                                <small class="text-muted fw-semibold">Lunas</small>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="ph ph-receipt fs-1 d-block mb-2 opacity-50"></i>
                            Belum ada nota supplier.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($invoices->hasPages())
        <div class="card-footer bg-white">{{ $invoices->links() }}</div>
    @endif
</div>

<!-- Modals rendered cleanly outside table -->
@foreach($invoices as $invoice)
@if($invoice->status !== 'paid')
<div class="modal fade" id="payModal{{ $invoice->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Bayar Hutang: {{ $invoice->supplier->name ?? '-' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('inventory.invoices.pay', $invoice->id) }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 mb-3">
                        Sisa Bon saat ini: <strong>Rp {{ number_format($invoice->remaining_amount, 0, ',', '.') }}</strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nominal Bayar (Rp)</label>
                        <input type="number" name="amount" class="form-control form-control-lg" required min="1" max="{{ $invoice->remaining_amount }}" value="{{ $invoice->remaining_amount }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sumber Dompet / Rekening</label>
                        <select name="wallet_id" class="form-select">
                            @foreach($wallets as $wallet)
                                <option value="{{ $wallet->id }}">{{ $wallet->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tanggal Bayar</label>
                        <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div>
                        <label class="form-label">Catatan (Opsional)</label>
                        <input type="text" name="notes" class="form-control" placeholder="Contoh: Transfer BCA">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Proses Pembayaran</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endforeach
@endsection
