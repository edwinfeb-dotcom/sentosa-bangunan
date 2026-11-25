@extends('layouts.app')

@section('title', 'Retur Barang ke Supplier')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Retur Barang ke Supplier</h2>
        <p class="page-subtitle">Catat pengembalian barang rusak atau salah kirim ke pemasok.</p>
    </div>
    <a href="{{ route('inventory.returns.create') }}" class="btn btn-primary">
        <i class="ph ph-plus"></i> Buat Retur Baru
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>No. Retur</th>
                        <th>Tanggal</th>
                        <th>Supplier</th>
                        <th>Penyelesaian</th>
                        <th>Total Nilai</th>
                        <th>Petugas</th>
                        <th class="text-end">Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($returns as $ret)
                        <tr>
                            <td class="fw-bold text-primary">{{ $ret->return_number }}</td>
                            <td>{{ $ret->return_date->format('d/m/Y') }}</td>
                            <td>{{ $ret->supplier->name ?? '-' }}</td>
                            <td>
                                @if($ret->resolution === 'cut_debt')
                                    <span class="badge bg-info bg-opacity-10 text-info">Potong Hutang Bon</span>
                                @elseif($ret->resolution === 'cash_refund')
                                    <span class="badge bg-success bg-opacity-10 text-success">Refund Masuk Kas</span>
                                @else
                                    <span class="badge bg-warning bg-opacity-10 text-warning">Tukar Barang</span>
                                @endif
                            </td>
                            <td class="fw-bold">Rp {{ number_format($ret->total_amount, 0, ',', '.') }}</td>
                            <td>{{ $ret->user->name ?? '-' }}</td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#returnDetailModal{{ $ret->id }}">
                                    <i class="ph ph-eye"></i> Item
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="ph ph-arrow-u-up-left fs-1 d-block mb-2 opacity-50"></i>
                                Belum ada catatan retur ke supplier.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($returns->hasPages())
        <div class="card-footer bg-white">{{ $returns->links() }}</div>
    @endif
</div>

@foreach($returns as $ret)
<div class="modal fade" id="returnDetailModal{{ $ret->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Detail Retur: {{ $ret->return_number }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-muted mb-3">Catatan: {{ $ret->notes ?? '-' }}</p>
                <table class="table table-sm border">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Alasan</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Harga</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ret->items as $item)
                            <tr>
                                <td>{{ $item->product->name ?? '-' }}</td>
                                <td>{{ $item->reason ?? '-' }}</td>
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
