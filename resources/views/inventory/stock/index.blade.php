@extends('layouts.app')

@section('title', 'Kartu Stok & Stock Opname')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Kartu Stok &amp; Stock Opname</h2>
        <p class="page-subtitle">Pantau seluruh mutasi stok masuk/keluar, cek barang menipis, dan sesuaikan stok fisik gudang.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('inventory.stock.export', request()->query()) }}" class="btn btn-outline-primary">
            <i class="ph ph-file-xls"></i> Export Kartu Stok
        </a>
        <a href="{{ route('master.products.index') }}" class="btn btn-light border">
            <i class="ph ph-package"></i> Master Produk
        </a>
        <button type="button" class="btn btn-outline-dark" data-bs-toggle="modal" data-bs-target="#batchOpnameModal">
            <i class="ph ph-list-checks"></i> Opname Massal
        </button>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#stockOpnameModal">
            <i class="ph ph-clipboard-text"></i> Input Stock Opname
        </button>
    </div>
</div>

<!-- Ringkasan Stok -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-label">Produk Aktif</div>
            <div class="stat-value">{{ number_format($summary['total_products'], 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-label">Stok Menipis / Kritis</div>
            <div class="stat-value {{ $summary['critical_count'] > 0 ? 'text-danger' : 'text-success' }}">
                {{ number_format($summary['critical_count'], 0, ',', '.') }} Item
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-label">Total Qty Masuk</div>
            <div class="stat-value text-success">+{{ number_format($summary['total_in'], 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-label">Total Qty Keluar</div>
            <div class="stat-value text-danger">-{{ number_format($summary['total_out'], 0, ',', '.') }}</div>
        </div>
    </div>
</div>

@if($criticalProducts->isNotEmpty())
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="text-danger"><i class="ph ph-warning-circle me-2"></i>Daftar Barang Stok Menipis ({{ $criticalProducts->count() }} Item)</span>
        <a href="{{ route('inventory.po.create') }}" class="btn btn-sm btn-outline-primary">Buat PO Restock</a>
    </div>
    <div class="card-body p-3">
        <div class="d-flex flex-wrap gap-2">
            @foreach($criticalProducts->take(12) as $cp)
                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-3 py-2">
                    {{ $cp->name }}: <strong>{{ $cp->current_stock }}</strong> / min {{ $cp->stock_minimum }} {{ $cp->unit }}
                </span>
            @endforeach
        </div>
    </div>
</div>
@endif

<!-- Filter Mutasi -->
<div class="card mb-4">
    <div class="card-body p-3">
        <form action="{{ route('inventory.stock.index') }}" method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Filter Produk</label>
                <select name="product_id" class="form-select">
                    <option value="">Semua Produk</option>
                    @foreach($allProducts as $prod)
                        <option value="{{ $prod->id }}" {{ request('product_id') == $prod->id ? 'selected' : '' }}>
                            {{ $prod->name }} (Stok: {{ $prod->current_stock }} {{ $prod->unit }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Tipe</label>
                <select name="type" class="form-select">
                    <option value="">Semua</option>
                    <option value="in" {{ request('type') === 'in' ? 'selected' : '' }}>Stok Masuk</option>
                    <option value="out" {{ request('type') === 'out' ? 'selected' : '' }}>Stok Keluar</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Dari Tanggal</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            <div class="col-6 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-dark w-100">Filter</button>
                <a href="{{ route('inventory.stock.index') }}" class="btn btn-light border">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Tabel Kartu Stok -->
<div class="card">
    <div class="card-header">Riwayat Mutasi Kartu Stok</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Produk</th>
                        <th>Tipe</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Sebelum &rarr; Sesudah</th>
                        <th>Keterangan</th>
                        <th>Petugas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ledgers as $row)
                    <tr>
                        <td class="text-muted small">{{ $row->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $row->product->name ?? 'Produk Terhapus' }}</div>
                            <small class="text-muted">{{ $row->product->category->name ?? '-' }}</small>
                        </td>
                        <td>
                            @if($row->type === 'in')
                                <span class="badge bg-success bg-opacity-10 text-success">Masuk</span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger">Keluar</span>
                            @endif
                        </td>
                        <td class="text-end fw-bold {{ $row->type === 'in' ? 'text-success' : 'text-danger' }}">
                            {{ $row->type === 'in' ? '+' : '-' }}{{ $row->qty }}
                        </td>
                        <td class="text-end">
                            <span class="text-muted">{{ $row->stock_before }}</span>
                            <i class="ph ph-arrow-right mx-1 text-muted"></i>
                            <strong class="text-dark">{{ $row->stock_after }}</strong>
                        </td>
                        <td class="small">{{ $row->description ?? '-' }}</td>
                        <td class="text-muted small">{{ $row->user->name ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="ph ph-stack fs-1 d-block mb-2 opacity-50"></i>
                            Belum ada catatan mutasi stok.
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

<!-- Modal Input Stock Opname -->
<div class="modal fade" id="stockOpnameModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Input Stock Opname (Penyesuaian Fisik)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('inventory.stock.opname') }}" method="POST" x-data="{ systemStock: 0, actualStock: 0 }">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Pilih Produk</label>
                        <select name="product_id" class="form-select" required @change="systemStock = parseInt($event.target.options[$event.target.selectedIndex].getAttribute('data-stock')) || 0; actualStock = systemStock;">
                            <option value="">-- Pilih Produk --</option>
                            @foreach($allProducts as $prod)
                                <option value="{{ $prod->id }}" data-stock="{{ $prod->current_stock }}">
                                    {{ $prod->name }} (Sistem: {{ $prod->current_stock }} {{ $prod->unit }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label">Stok di Sistem</label>
                            <input type="number" class="form-control bg-light tabular-nums" :value="systemStock" readonly>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Stok Fisik Terhitung</label>
                            <input type="number" name="actual_stock" class="form-control tabular-nums" x-model.number="actualStock" min="0" required>
                        </div>
                    </div>
                    <div class="mb-3 small fw-semibold" x-show="actualStock !== systemStock">
                        Selisih penyesuaian:
                        <span :class="actualStock > systemStock ? 'text-success' : 'text-danger'" x-text="(actualStock > systemStock ? '+' : '') + (actualStock - systemStock)"></span>
                    </div>
                    <div>
                        <label class="form-label">Catatan / Alasan Selisih</label>
                        <input type="text" name="notes" class="form-control" placeholder="Contoh: Hasil hitung fisik gudang rak A1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Stock Opname</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Stock Opname Massal -->
<div class="modal fade" id="batchOpnameModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Stock Opname Massal (Banyak Produk Sekaligus)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('inventory.stock.batch-opname') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Catatan Opname</label>
                        <input type="text" name="notes" class="form-control" value="Opname Massal {{ date('d/m/Y') }}">
                    </div>
                    <div class="table-responsive" style="max-height: 360px;">
                        <table class="table table-sm mb-0">
                            <thead class="sticky-top bg-white">
                                <tr>
                                    <th>Produk</th>
                                    <th class="text-end">Stok Sistem</th>
                                    <th style="width: 140px;">Stok Fisik</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($allProducts->take(80) as $prod)
                                <tr>
                                    <td class="small fw-medium">{{ $prod->name }}</td>
                                    <td class="text-end small text-muted">{{ $prod->current_stock }} {{ $prod->unit }}</td>
                                    <td>
                                        <input type="number" name="actual_stocks[{{ $prod->id }}]" class="form-control form-control-sm tabular-nums" min="0" placeholder="{{ $prod->current_stock }}">
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <small class="text-muted d-block mt-2">Kosongkan kolom Stok Fisik jika tidak ada perubahan.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-dark">Simpan Semua Penyesuaian</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
