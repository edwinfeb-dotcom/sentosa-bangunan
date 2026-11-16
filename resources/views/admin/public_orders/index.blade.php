@extends('layouts.app')

@section('title', 'Pesanan Online')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #1E293B;">Pesanan Online (Katalog)</h4>
        <p class="text-muted mb-0" style="font-size: 0.875rem;">Daftar pesanan yang masuk dari pembeli publik.</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="ph ph-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="ph ph-warning-circle me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th class="ps-4">Kode Pesanan</th>
                        <th>Pembeli</th>
                        <th>Pengiriman</th>
                        <th>Pembayaran</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold" style="color: #1E3A8A;">{{ $order->order_code }}</div>
                            <small class="text-muted">{{ $order->created_at->format('d/m/Y H:i') }}</small>
                        </td>
                        <td>
                            <div class="fw-semibold" style="color: #1E293B;">{{ $order->customer_name }}</div>
                            <small class="text-muted">{{ $order->phone }}</small>
                        </td>
                        <td>
                            @if($order->shipping_method === 'antar')
                                <span class="badge bg-primary bg-opacity-10 text-primary">Antar</span>
                                <div class="small text-muted mt-1" style="max-width: 150px;">{{ Str::limit($order->address, 30) }}</div>
                            @else
                                <span class="badge bg-info bg-opacity-10 text-info">Ambil di Toko</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $order->payment_method === 'cash' ? 'bg-warning text-dark' : 'bg-success' }}">
                                {{ strtoupper($order->payment_method) }}
                            </span>
                        </td>
                        <td class="fw-bold">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                        <td>
                            @if($order->status === 'pending')
                                <span class="badge bg-secondary bg-opacity-10 text-secondary">Menunggu</span>
                            @elseif($order->status === 'processed')
                                <span class="badge bg-success bg-opacity-10 text-success">Diproses</span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger">Batal</span>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            @if($order->status === 'pending')
                                <form action="{{ route('public-orders.process', $order->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Proses pesanan ini? Pastikan pembayaran sudah dicek. Stok dan Kas akan otomatis diperbarui.')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="ph ph-check-circle me-1"></i> Proses Pesanan
                                    </button>
                                </form>
                            @else
                                <small class="text-muted">Selesai</small>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="ph ph-globe fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            Belum ada pesanan online yang masuk.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($orders->hasPages())
    <div class="card-footer bg-white border-top pt-3">
        {{ $orders->links() }}
    </div>
    @endif
</div>
@endsection