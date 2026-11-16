@extends('layouts.app')

@section('title', 'Nota Supplier')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #1E293B;">Nota Supplier (Hutang Dagang)</h4>
        <p class="text-muted mb-0" style="font-size: 0.875rem;">Catat tagihan dan pembayaran (Bon) ke supplier.</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="ph ph-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card border-0 shadow-sm mb-4" style="background-color: #FEF2F2; border-left: 4px solid #EF4444 !important;">
    <div class="card-body p-4">
        <h6 class="text-danger mb-2 fw-semibold">Total Sisa Bon (Hutang Berjalan)</h6>
        <h3 class="fw-bold text-dark mb-0">Rp {{ number_format($totalDebt, 0, ',', '.') }}</h3>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th class="ps-4">Supplier & No. Nota</th>
                        <th>Total Tagihan</th>
                        <th>Sudah Dibayar</th>
                        <th>Sisa Bon</th>
                        <th>Jatuh Tempo</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-semibold" style="color: #1E293B;">{{ $invoice->supplier->name }}</div>
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
                        <td class="text-end pe-4">
                            @if($invoice->status !== 'paid')
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#payModal{{ $invoice->id }}">
                                    <i class="ph ph-wallet me-1"></i> Bayar
                                </button>
                            @else
                                <small class="text-muted">Selesai</small>
                            @endif
                        </td>
                    </tr>

                    <!-- Modal Pembayaran -->
                    <div class="modal fade" id="payModal{{ $invoice->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog text-start">
                            <div class="modal-content border-0 shadow">
                                <div class="modal-header border-bottom">
                                    <h5 class="modal-title fw-bold">Bayar Hutang: {{ $invoice->supplier->name }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form action="{{ route('inventory.invoices.pay', $invoice->id) }}" method="POST">
                                    @csrf
                                    <div class="modal-body p-4">
                                        <div class="alert alert-info border-0 mb-3">
                                            Sisa Bon saat ini: <strong>Rp {{ number_format($invoice->remaining_amount, 0, ',', '.') }}</strong>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-medium">Nominal Bayar (Rp)</label>
                                            <input type="number" name="amount" class="form-control form-control-lg" required min="1" max="{{ $invoice->remaining_amount }}" value="{{ $invoice->remaining_amount }}">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-medium">Tanggal Bayar</label>
                                            <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-medium">Catatan (Opsional)</label>
                                            <input type="text" name="notes" class="form-control" placeholder="Contoh: Transfer BCA">
                                        </div>
                                    </div>
                                    <div class="modal-footer border-top">
                                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-primary">Proses Pembayaran</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="ph ph-receipt fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            Belum ada nota supplier (bon).
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($invoices->hasPages())
    <div class="card-footer bg-white border-top pt-3">
        {{ $invoices->links() }}
    </div>
    @endif
</div>
@endsection