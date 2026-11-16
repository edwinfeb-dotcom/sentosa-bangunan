@extends('layouts.app')

@section('title', 'Purchase Order')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #1E293B;">Purchase Order (PO)</h4>
        <p class="text-muted mb-0" style="font-size: 0.875rem;">Kelola pesanan pembelian barang ke supplier.</p>
    </div>
    <a href="{{ route('inventory.po.create') }}" class="btn btn-primary d-flex align-items-center gap-2">
        <i class="ph ph-plus"></i> Buat PO Baru
    </a>
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

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>No PO</th>
                        <th>Tanggal</th>
                        <th>Supplier</th>
                        <th>Total Belanja</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchaseOrders as $po)
                    <tr>
                        <td class="fw-semibold" style="color: #1E293B;">{{ $po->po_number }}</td>
                        <td>{{ \Carbon\Carbon::parse($po->order_date)->format('d/m/Y') }}</td>
                        <td>{{ $po->supplier->name }}</td>
                        <td class="fw-medium">Rp {{ number_format($po->total_amount, 0, ',', '.') }}</td>
                        <td>
                            @if($po->status === 'ordered')
                                <span class="badge bg-warning bg-opacity-10 text-warning">Dipesan</span>
                            @elseif($po->status === 'received')
                                <span class="badge bg-success bg-opacity-10 text-success">Diterima</span>
                            @else
                                <span class="badge bg-secondary bg-opacity-10 text-secondary text-capitalize">{{ $po->status }}</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($po->status === 'ordered')
                                <form action="{{ route('inventory.po.receive', $po->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Terima semua barang untuk PO ini? Stok akan otomatis bertambah.')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1">
                                        <i class="ph ph-check-circle"></i> Terima Barang
                                    </button>
                                </form>
                            @else
                                <button class="btn btn-sm btn-light border" disabled>Selesai</button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="ph ph-shopping-cart fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            Belum ada data Purchase Order.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($purchaseOrders->hasPages())
    <div class="card-footer bg-white border-top pt-3">
        {{ $purchaseOrders->links() }}
    </div>
    @endif
</div>
@endsection