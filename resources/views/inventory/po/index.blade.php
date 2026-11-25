@extends('layouts.app')

@section('title', 'Purchase Order')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Purchase Order (PO)</h2>
        <p class="page-subtitle">Kelola pesanan pembelian ke supplier dan penerimaan stok barang.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('inventory.returns.index') }}" class="btn btn-outline-secondary">
            <i class="ph ph-arrow-u-up-left"></i> Retur Supplier
        </a>
        <a href="{{ route('inventory.po.create') }}" class="btn btn-primary">
            <i class="ph ph-plus"></i> Buat PO Baru
        </a>
    </div>
</div>

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
                        <td class="fw-bold text-primary">{{ $po->po_number }}</td>
                        <td>{{ \Carbon\Carbon::parse($po->order_date)->format('d/m/Y') }}</td>
                        <td>{{ $po->supplier->name ?? '-' }}</td>
                        <td class="fw-semibold">Rp {{ number_format($po->total_amount, 0, ',', '.') }}</td>
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
                            <div class="d-inline-flex gap-1">
                                <button type="button" class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#poDetailModal{{ $po->id }}">
                                    <i class="ph ph-eye"></i> Detail
                                </button>
                                @if($po->status === 'ordered')
                                    <form action="{{ route('inventory.po.receive', $po->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Terima semua barang untuk PO ini? Stok dan Nota Hutang otomatis tercatat.')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="ph ph-check-circle"></i> Terima Barang
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="ph ph-shopping-cart fs-1 d-block mb-2 opacity-50"></i>
                            Belum ada data Purchase Order.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($purchaseOrders->hasPages())
        <div class="card-footer bg-white">{{ $purchaseOrders->links() }}</div>
    @endif
</div>

<!-- PO Detail Modals -->
@foreach($purchaseOrders as $po)
<div class="modal fade" id="poDetailModal{{ $po->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Detail PO: {{ $po->po_number }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-2 mb-3 small">
                    <div class="col-6"><strong>Supplier:</strong> {{ $po->supplier->name ?? '-' }}</div>
                    <div class="col-6"><strong>Tanggal:</strong> {{ \Carbon\Carbon::parse($po->order_date)->format('d/m/Y') }}</div>
                    @if($po->notes)
                        <div class="col-12"><strong>Catatan:</strong> {{ $po->notes }}</div>
                    @endif
                </div>
                <table class="table table-sm border">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Harga Beli</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($po->items as $item)
                            <tr>
                                <td>{{ $item->product->name ?? '-' }}</td>
                                <td class="text-end">{{ $item->qty }}</td>
                                <td class="text-end">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                <td class="text-end fw-semibold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endforeach
@endsection
