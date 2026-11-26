@extends('layouts.app')

@section('title', 'Master Produk')

@section('content')
<div class="page-header">
    <div>
        <h2 class="page-title">Master Produk</h2>
        <p class="page-subtitle">Kelola daftar produk, barcode, harga beli/jual, dan stok barang.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('master.products.barcode') }}" class="btn btn-outline-dark">
            <i class="ph ph-barcode"></i> Cetak Label
        </a>
        <a href="{{ route('master.products.export') }}" class="btn btn-outline-primary">
            <i class="ph ph-file-xls"></i> Export Excel
        </a>
        <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="ph ph-upload-simple"></i> Import Excel
        </button>
        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importImagesModal">
            <i class="ph ph-image"></i> Import Gambar (Excel)
        </button>
        <a href="{{ route('master.products.create') }}" class="btn btn-primary">
            <i class="ph ph-plus"></i> Tambah Produk
        </a>
    </div>
</div>

<!-- Search & Filter -->
<div class="card mb-4">
    <div class="card-body p-3">
        <form action="{{ route('master.products.index') }}" method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Cari Nama atau Barcode</label>
                <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Nama / barcode...">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label">Kategori</label>
                <select name="category_id" class="form-select">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Supplier</label>
                <select name="supplier_id" class="form-select">
                    <option value="">Semua Supplier</option>
                    @foreach($suppliers ?? [] as $sup)
                        <option value="{{ $sup->id }}" {{ request('supplier_id') == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Kondisi Stok</label>
                <select name="stock_status" class="form-select">
                    <option value="">Semua Stok</option>
                    <option value="critical" {{ request('stock_status') === 'critical' ? 'selected' : '' }}>Stok Kritis (&le; Min)</option>
                    <option value="empty" {{ request('stock_status') === 'empty' ? 'selected' : '' }}>Stok Habis (0)</option>
                </select>
            </div>
            <div class="col-6 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">Cari</button>
                <a href="{{ route('master.products.index') }}" class="btn btn-light border">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th>Produk</th>
                        <th>Barcode</th>
                        <th>Kategori</th>
                        <th>Harga Jual</th>
                        <th>Stok</th>
                        <th>Status</th>
                        <th width="14%" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $index => $product)
                    <tr>
                        <td class="text-muted">{{ $products->firstItem() + $index }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if($product->image)
                                    <img src="{{ asset('storage/' . $product->image) }}"
                                         alt="{{ $product->name }}"
                                         class="rounded border"
                                         style="width: 38px; height: 38px; object-fit: cover; cursor: zoom-in;"
                                         loading="lazy"
                                         decoding="async"
                                         onclick="showProductPreview('{{ asset('storage/' . $product->image) }}', '{{ addslashes($product->name) }}')">
                                @else
                                    <div class="bg-light rounded border d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                        <i class="ph ph-package text-muted"></i>
                                    </div>
                                @endif
                                <div>
                                    <div class="fw-semibold text-dark">{{ $product->name }}</div>
                                    <small class="text-muted">
                                        Satuan: {{ $product->unit }}
                                        @if($product->rack_location)
                                            &middot; Rak {{ $product->rack_location }}
                                        @endif
                                        @if($product->margin_code)
                                            &middot; <span class="badge bg-light text-dark border">{{ $product->margin_code }}</span>
                                        @endif
                                    </small>
                                    @if($product->secondary_unit && $product->secondary_price)
                                        <div class="text-muted" style="font-size: 0.75rem;">
                                            Ecer {{ $product->secondary_unit }}: Rp {{ number_format($product->secondary_price, 0, ',', '.') }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary font-monospace">
                                {{ $product->barcode ?? '-' }}
                            </span>
                        </td>
                        <td>{{ $product->category->name ?? '-' }}</td>
                        <td class="fw-semibold">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</td>
                        <td>
                            <a href="{{ route('master.products.edit', $product->id) }}#initial_stock"
                               class="badge text-decoration-none {{ $product->current_stock <= $product->stock_minimum ? 'bg-danger bg-opacity-10 text-danger' : 'bg-success bg-opacity-10 text-success' }}"
                               title="Klik untuk menyesuaikan stok">
                                {{ $product->current_stock }} {{ $product->unit }} <i class="ph ph-pencil-simple ms-1" style="font-size: 0.65rem;"></i>
                            </a>
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
                            <i class="ph ph-package fs-1 d-block mb-2 opacity-50"></i>
                            Belum ada data produk.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($products->hasPages())
        <div class="card-footer bg-white">{{ $products->links() }}</div>
    @endif
</div>

<!-- Modal Import -->
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Import Data Produk dari Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('master.products.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 p-3 rounded bg-light border mb-3">
                        <div>
                            <div class="fw-bold text-dark small">1. Download Template Resmi (Model Owner)</div>
                            <div class="text-muted small">Berisi format 19 kolom standar Owner (ID Kategori, Harga Beli, Rak, Supplier, Stok, Multi-Satuan &amp; Kode Margin).</div>
                        </div>
                        <a href="{{ route('master.products.template') }}" class="btn btn-sm btn-success">
                            <i class="ph ph-download-simple"></i> Download Template (.xlsx)
                        </a>
                    </div>

                    <div class="alert alert-info border-0 small mb-3">
                        <div class="fw-bold mb-1">Sekali Upload Bisa Memasukkan Semua Data (Produk + Harga + Stok + Foto):</div>
                        <ul class="mb-0 ps-3">
                            <li><strong>Template Owner (19 Kolom):</strong> Kolom <code>ID Kategori Angka</code> (01–16), <code>Nama Barang</code>, <code>Harga Beli</code>, <code>Stok Awal</code>, <code>Kode Margin 1</code> (cth: <code>K50</code>), <code>Satuan 2</code>, dll.</li>
                            <li><strong>Template Lengkap (Kode, Nama, Harga, Stok &amp; Foto):</strong> Kolom <code>kode_barang</code>, <code>nama_produk</code>, <code>kategori</code>, <code>satuan</code>, <code>harga_beli</code>, <code>harga_jual</code>, <code>stok</code>, <code>minimal_stok</code>, dan foto tertanam.</li>
                            <li><strong>Master Data Owner (.xlsx / .csv) &amp; Export ERP:</strong> Langsung dikenali otomatis tanpa duplikasi data.</li>
                        </ul>
                    </div>

                    <label for="file" class="form-label">2. Pilih File Excel / CSV (.xlsx, .xls, .xlsm, .csv — Maks 25MB)</label>
                    <input type="file" class="form-control mb-3" id="file" name="file" accept=".xlsx, .xls, .xlsm, .csv" required>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="ignore_template_sheet" value="1" id="ignoreTemplateSheet">
                        <label class="form-check-label small" for="ignoreTemplateSheet">
                            Hanya baca sheet <code>kategori</code> &amp; <code>BARANG</code> (centang jika memakai file <strong>UPDATE HARGA BARANG</strong> milik Owner agar baris contoh di sheet <code>Template Import</code> tidak ikut masuk)
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ph ph-upload-simple"></i> Upload & Import Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Import Gambar -->
<div class="modal fade" id="importImagesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="ph ph-images text-primary me-2"></i>Upload Banyak Gambar Produk Sekaligus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 p-3 rounded bg-light border mb-3">
                    <div>
                        <div class="fw-bold text-dark small">Download Template Lengkap (Kode, Produk, Harga, Stok &amp; Kolom Foto)</div>
                        <div class="text-muted small">Berisi <code>kode_barang</code>, <code>nama_produk</code>, <code>harga_beli</code>, <code>harga_jual</code>, <code>stok</code>, dan kolom biru untuk foto (1x upload langsung masuk semua!).</div>
                    </div>
                    <a href="{{ route('master.products.images.template') }}" class="btn btn-sm btn-success">
                        <i class="ph ph-download-simple"></i> Download Template Lengkap (.xlsx)
                    </a>
                </div>

                <ul class="nav nav-pills mb-3 gap-2" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-excel-img-btn" data-bs-toggle="pill" data-bs-target="#tab-excel-img" type="button" role="tab">
                            <i class="ph ph-file-xls me-1"></i> Upload Excel Bergambar (.xlsx)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-batch-btn" data-bs-toggle="pill" data-bs-target="#tab-batch" type="button" role="tab">
                            <i class="ph ph-upload-simple me-1"></i> Pilih Banyak Foto Sekaligus (PNG/JPG)
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- Tab 1 (Default): Embedded Excel Drawings & Place in Cell -->
                    <div class="tab-pane fade show active" id="tab-excel-img" role="tabpanel">
                        <form action="{{ route('master.products.images.import') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="alert alert-light border small mb-3">
                                <div class="fw-bold mb-1">Masukkan Data, Stok, &amp; Foto Sekaligus di Excel (.xlsx):</div>
                                <ol class="mb-0 ps-3">
                                    <li>Download template di atas — sudah tersedia kolom <code>kode_barang</code>, <code>nama_produk</code>, <code>kategori</code>, <code>satuan</code>, <code>harga_beli</code>, <code>harga_jual</code>, <code>stok</code>, <code>minimal_stok</code>, dan <strong>Kolom I (biru muda)</strong> untuk foto.</li>
                                    <li>Isi data/stok dan sisipkan foto lewat <strong>Insert &rarr; Pictures &rarr; This Device</strong> pada Kolom I (PNG/JPG/WEBP).</li>
                                    <li>Simpan <code>.xlsx</code> lalu upload di bawah ini — seluruh produk, harga, stok, dan foto langsung terupdate sekali klik!</li>
                                </ol>
                            </div>
                            <label for="image_excel_file" class="form-label fw-semibold">Pilih File Excel Berisi Gambar (.xlsx — Maks 25MB)</label>
                            <input type="file" class="form-control mb-3" id="image_excel_file" name="file" accept=".xlsx,.xls" required>
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="ph ph-upload-simple"></i> Upload &amp; Import Gambar Excel
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Tab 2: Batch Picker Multi File PNG/JPG -->
                    <div class="tab-pane fade" id="tab-batch" role="tabpanel">
                        <form action="{{ route('master.products.images.batch') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="alert alert-info border-0 small mb-3">
                                <div class="fw-bold mb-1">Upload 100+ File Foto Sekaligus Tanpa Excel (PNG, JPG, JPEG, WEBP):</div>
                                <ol class="mb-0 ps-3">
                                    <li>Beri nama file tiap foto sesuai <strong>kode_barang / barcode</strong> atau <strong>nama produk</strong> (misal: <code>8991234560001.jpg</code> atau <code>PRD-15.png</code>).</li>
                                    <li>Tekan <strong>Ctrl + A</strong> dan pilih semua foto sekaligus pada input di bawah — sistem otomatis memasangkan setiap foto ke barang yang sesuai.</li>
                                </ol>
                            </div>
                            <label for="batch_images_input" class="form-label fw-semibold">Pilih Banyak File Foto (PNG/JPG/WEBP — atau .xlsx)</label>
                            <input type="file" class="form-control mb-2" id="batch_images_input" name="images[]" accept=".png,.jpg,.jpeg,.gif,.webp,.xlsx,.xls" multiple required>
                            <div id="batch_images_preview" class="small text-muted mb-3"></div>
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="ph ph-cloud-arrow-up"></i> Upload &amp; Pasangkan Semua Foto
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Preview Gambar Produk -->
<div class="modal fade" id="productImagePreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2 px-3">
                <h6 class="modal-title fw-bold small mb-0" id="productImagePreviewTitle">Foto Produk</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3 text-center bg-light">
                <img id="productImagePreviewSrc" src="" alt="Preview" class="img-fluid rounded border bg-white" style="max-height: 280px; object-fit: contain;">
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function showProductPreview(src, title) {
    document.getElementById('productImagePreviewSrc').src = src;
    document.getElementById('productImagePreviewTitle').innerText = title;
    if (window.bootstrap && window.bootstrap.Modal) {
        window.bootstrap.Modal.getOrCreateInstance(document.getElementById('productImagePreviewModal')).show();
    }
}
document.getElementById('batch_images_input')?.addEventListener('change', function (e) {
    const files = Array.from(e.target.files || []);
    const box = document.getElementById('batch_images_preview');
    if (!box) return;
    if (files.length === 0) {
        box.innerText = '';
        return;
    }
    const sample = files.slice(0, 5).map(f => f.name).join(', ');
    box.innerHTML = `<span class="badge bg-success bg-opacity-10 text-success">${files.length} foto dipilih</span> <span class="ms-1">Contoh: ${sample}${files.length > 5 ? ', ...' : ''}</span>`;
});
</script>
@endpush
@endsection
