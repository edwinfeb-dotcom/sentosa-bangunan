@extends('layouts.app')

@section('title', 'Detail Supplier')

@section('content')
<div class="page-header">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('master.suppliers.index') }}" class="btn btn-light border btn-sm"><i class="ph ph-arrow-left"></i></a>
        <div>
            <h2 class="page-title mb-0">{{ $supplier->name }}</h2>
            <p class="page-subtitle">{{ $supplier->phone ?? 'Tanpa Telp' }} {{ $supplier->address ? '• ' . $supplier->address : '' }}</p>
        </div>
    </div>
    <a href="{{ route('master.suppliers.edit', $supplier->id) }}" class="btn btn-light border">
        <i class="ph ph-pencil-simple text-primary"></i> Edit Supplier
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="stat-card">
            <div class="stat-label">Produk Disuplai</div>
            <div class="stat-value">{{ $products->count() }} Item</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="stat-card">
            <div class="stat-label">Total PO Terdaftar</div>
            <div class="stat-value text-primary">{{ $purchaseOrders->count() }} PO</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="stat-card">
            <div class="stat-label">Sisa Hutang Belum Lunas</div>
            <div class="stat-value {{ $totalDebt > 0 ? 'text-danger' : 'text-success' }}">Rp {{ number_format($totalDebt, 0, ',', '.') }}</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="ph ph-package text-accent me-2"></i>Daftar Barang dari Supplier Ini</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th class="text-end">Harga Beli</th>
                                <th class="text-end">Stok</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $prod)
                            <tr>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $prod->name }}</div>
                                    <small class="text-muted">{{ $prod->barcode ?? '-' }}</small>
                                </td>
                                <td class="text-end tabular-nums">Rp {{ number_format($prod->purchase_price, 0, ',', '.') }}</td>
                                <td class="text-end fw-semibold {{ $prod->current_stock <= $prod->stock_minimum ? 'text-danger' : 'text-success' }}">
                                    {{ $prod->current_stock }} {{ $prod->unit }}
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center py-4 text-muted">Belum ada produk terkait supplier ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header"><i class="ph ph-shopping-cart text-accent me-2"></i>Riwayat Purchase Order</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>No. PO</th>
                                <th>Tanggal</th>
                                <th class="text-end">Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($purchaseOrders as $po)
                            <tr>
                                <td class="fw-semibold text-primary">{{ $po->po_number }}</td>
                                <td class="small">{{ \Carbon\Carbon::parse($po->order_date)->format('d/m/Y') }}</td>
                                <td class="text-end tabular-nums">Rp {{ number_format($po->total_amount, 0, ',', '.') }}</td>
                                <td><span class="badge bg-light text-dark border text-uppercase">{{ $po->status }}</span></td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center py-4 text-muted">Belum ada PO.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="ph ph-receipt text-danger me-2"></i>Riwayat Nota Hutang Supplier</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>No. Nota</th>
                                <th class="text-end">Tagihan</th>
                                <th class="text-end">Sisa</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoices as $inv)
                            <tr>
                                <td class="fw-semibold">{{ $inv->invoice_number }}</td>
                                <td class="text-end tabular-nums">Rp {{ number_format($inv->total_amount, 0, ',', '.') }}</td>
                                <td class="text-end text-danger fw-bold tabular-nums">Rp {{ number_format($inv->remaining_amount, 0, ',', '.') }}</td>
                                <td><span class="badge bg-light text-dark border text-uppercase">{{ $inv->status }}</span></td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center py-4 text-muted">Belum ada nota hutang.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
