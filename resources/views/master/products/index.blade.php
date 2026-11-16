@extends('layouts.app')

@section('title', 'Master Produk')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color: #1E293B;">Master Produk</h4>
        <p class="text-muted mb-0" style="font-size: 0.875rem;">Kelola daftar produk, harga, dan stok barang.</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-success d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="ph ph-upload-simple"></i> Import Excel
        </button>
        <a href="{{ route('master.products.create') }}" class="btn btn-primary d-flex align-items-center gap-2">
            <i class="ph ph-plus"></i> Tambah Produk
        </a>
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

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th>Barcode</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th>Harga Jual</th>
                        <th>Stok</th>
                        <th>Status</th>
                        <th width="15%" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $index => $product)
                    <tr>
                        <td class="text-muted">{{ $products->firstItem() + $index }}</td>
                        <td>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary font-monospace">
                                {{ $product->barcode ?? '-' }}
                            </span>
                        </td>
                        <td class="fw-semibold" style="color: #1E293B;">
                            {{ $product->name }} <span class="text-muted fw-normal" style="font-size: 0.8rem;">({{ $product->unit }})</span>
                        </td>
                        <td>{{ $product->category->name ?? '-' }}</td>
                        <td class="fw-medium">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</td>
                        <td>
                            <span class="fw-bold {{ $product->current_stock <= $product->stock_minimum ? 'text-danger' : 'text-success' }}">
                                {{ $product->current_stock }}
                            </span>
                        </td>
                        <td>
                            @if($product->is_active)
                                <span class="badge bg-success bg-opacity-10 text-success">Aktif</span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger">Nonaktif</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('master.products.edit', $product->id) }}" class="btn btn-sm btn-light border" title="Edit">
                                <i class="ph ph-pencil-simple text-primary"></i>
                            </a>
                            <form action="{{ route('master.products.destroy', $product->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus produk ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-light border" title="Hapus">
                                    <i class="ph ph-trash text-danger"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="ph ph-package fs-1 d-block mb-2 text-secondary opacity-50"></i>
                            Belum ada data produk.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($products->hasPages())
    <div class="card-footer bg-white border-top pt-3">
        {{ $products->links() }}
    </div>
    @endif
</div>

<!-- Modal Import -->
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="importModalLabel">Import Data Produk</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('master.products.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 bg-info bg-opacity-10 text-info">
                        <small>Pastikan format kolom Excel Anda sesuai. Urutan kolom: <br>
                        <b>nama_produk | kategori | barcode | satuan | harga_beli | harga_jual | minimal_stok</b></small>
                    </div>
                    <div class="mb-3">
                        <label for="file" class="form-label fw-medium">Pilih File Excel (.xlsx, .csv)</label>
                        <input type="file" class="form-control" id="file" name="file" accept=".xlsx, .xls, .csv" required>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Mulai Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection