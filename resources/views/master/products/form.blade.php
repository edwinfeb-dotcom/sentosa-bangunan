@extends('layouts.app')

@section('title', isset($product) ? 'Edit Produk' : 'Tambah Produk')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="d-flex align-items-center mb-4">
            <a href="{{ route('master.products.index') }}" class="btn btn-light border btn-sm me-3">
                <i class="ph ph-arrow-left"></i>
            </a>
            <h4 class="fw-bold mb-0" style="color: #1E293B;">
                {{ isset($product) ? 'Edit Produk' : 'Tambah Produk Baru' }}
            </h4>
        </div>

        <div class="card">
            <div class="card-body p-4">
                <form action="{{ isset($product) ? route('master.products.update', $product->id) : route('master.products.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @if(isset($product))
                        @method('PUT')
                    @endif

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label for="category_id" class="form-label fw-medium">Kategori</label>
                            <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id" required>
                                <option value="">-- Pilih Kategori --</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id', $product->category_id ?? '') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6 mb-4">
                            <label for="barcode" class="form-label fw-medium">Barcode (Opsional)</label>
                            <input type="text" class="form-control @error('barcode') is-invalid @enderror" id="barcode" name="barcode" value="{{ old('barcode', $product->barcode ?? '') }}" placeholder="Scan atau ketik barcode" onkeydown="if(event.key==='Enter'){event.preventDefault();document.getElementById('name')?.focus();}">
                            @error('barcode') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8 mb-4">
                            <label for="name" class="form-label fw-medium">Nama Produk</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $product->name ?? '') }}" placeholder="Contoh: Semen Tiga Roda 50kg" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4 mb-4">
                            <label for="unit" class="form-label fw-medium">Satuan</label>
                            <input type="text" class="form-control @error('unit') is-invalid @enderror" id="unit" name="unit" value="{{ old('unit', $product->unit ?? 'Pcs') }}" required>
                            @error('unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-medium">Gambar Produk (Max 2MB)</label>
                            <input type="file" class="form-control @error('image') is-invalid @enderror" id="productImageInput" name="image" accept="image/*">
                            @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="mt-2 {{ (isset($product) && $product->image) ? '' : 'd-none' }}" id="productImagePreviewWrap">
                                <img src="{{ (isset($product) && $product->image) ? asset('storage/' . $product->image) : '' }}"
                                     alt="Preview Produk" id="productImagePreviewImg" class="rounded border shadow-sm" style="height: 68px; width: 68px; object-fit: cover;">
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-medium">Deskripsi Singkat (Untuk Katalog)</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="2" placeholder="Kegunaan, ukuran, merek, dll...">{{ old('description', $product->description ?? '') }}</textarea>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-4">
                            <label for="purchase_price" class="form-label fw-medium">Harga Beli (HPP)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">Rp</span>
                                <input type="number" class="form-control border-start-0 @error('purchase_price') is-invalid @enderror" id="purchase_price" name="purchase_price" value="{{ old('purchase_price', $product->purchase_price ?? 0) }}" required min="0">
                            </div>
                            @error('purchase_price') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-3 mb-4">
                            <label for="selling_price" class="form-label fw-medium">Harga Jual</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">Rp</span>
                                <input type="number" class="form-control border-start-0 @error('selling_price') is-invalid @enderror" id="selling_price" name="selling_price" value="{{ old('selling_price', $product->selling_price ?? 0) }}" required min="0">
                            </div>
                            <div id="liveMarginPreview" class="small mt-1 fw-semibold text-muted"></div>
                            @error('selling_price') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-3 mb-4">
                            <label for="initial_stock" class="form-label fw-medium">{{ isset($product) ? 'Stok Saat Ini' : 'Stok Awal' }}</label>
                            <input type="number" class="form-control @error('initial_stock') is-invalid @enderror" id="initial_stock" name="initial_stock" value="{{ old('initial_stock', isset($product) ? $product->current_stock : 0) }}" min="0">
                            @error('initial_stock') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-3 mb-4">
                            <label for="stock_minimum" class="form-label fw-medium">Minimal Stok</label>
                            <input type="number" class="form-control @error('stock_minimum') is-invalid @enderror" id="stock_minimum" name="stock_minimum" value="{{ old('stock_minimum', $product->stock_minimum ?? 0) }}" required min="0">
                            @error('stock_minimum') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-4">
                            <label for="rack_location" class="form-label fw-medium">Lokasi Rak (Opsional)</label>
                            <input type="text" class="form-control @error('rack_location') is-invalid @enderror" id="rack_location" name="rack_location" value="{{ old('rack_location', $product->rack_location ?? '') }}" placeholder="Contoh: A1 / Gudang">
                            @error('rack_location') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-3 mb-4">
                            <label for="margin_code" class="form-label fw-medium">Kode Margin (Opsional)</label>
                            <input type="text" class="form-control @error('margin_code') is-invalid @enderror" id="margin_code" name="margin_code" value="{{ old('margin_code', $product->margin_code ?? '') }}" placeholder="Contoh: K50">
                            @error('margin_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-3 mb-4">
                            <label for="secondary_unit" class="form-label fw-medium">Satuan Eceran (Opsional)</label>
                            <input type="text" class="form-control @error('secondary_unit') is-invalid @enderror" id="secondary_unit" name="secondary_unit" value="{{ old('secondary_unit', $product->secondary_unit ?? '') }}" placeholder="Contoh: Kg / Meter">
                            @error('secondary_unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-3 mb-4">
                            <label for="secondary_price" class="form-label fw-medium">Harga Eceran (Opsional)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">Rp</span>
                                <input type="number" class="form-control border-start-0 @error('secondary_price') is-invalid @enderror" id="secondary_price" name="secondary_price" value="{{ old('secondary_price', $product->secondary_price ?? '') }}" min="0" placeholder="0">
                            </div>
                            @error('secondary_price') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" {{ old('is_active', $product->is_active ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label ms-2" for="is_active">Status Produk Aktif (Tampil di Kasir)</label>
                        </div>
                    </div>

                    <hr class="mb-4">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('master.products.index') }}" class="btn btn-light border">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan Produk</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const imgInput = document.getElementById('productImageInput');
    const imgWrap = document.getElementById('productImagePreviewWrap');
    const imgEl = document.getElementById('productImagePreviewImg');
    imgInput?.addEventListener('change', function (e) {
        const file = e.target.files?.[0];
        if (!file) return;
        imgEl.src = URL.createObjectURL(file);
        imgWrap.classList.remove('d-none');
    });

    const buyInput = document.getElementById('purchase_price');
    const sellInput = document.getElementById('selling_price');
    const marginEl = document.getElementById('liveMarginPreview');
    function updateMargin() {
        if (!buyInput || !sellInput || !marginEl) return;
        const buy = parseFloat(buyInput.value) || 0;
        const sell = parseFloat(sellInput.value) || 0;
        if (sell <= 0) {
            marginEl.textContent = '';
            return;
        }
        const diff = sell - buy;
        const pct = buy > 0 ? ((diff / buy) * 100).toFixed(1) : '100';
        const fmt = new Intl.NumberFormat('id-ID').format(Math.abs(diff));
        marginEl.className = 'small mt-1 fw-semibold ' + (diff >= 0 ? 'text-success' : 'text-danger');
        marginEl.textContent = diff >= 0 ? `Laba: +Rp ${fmt} (+${pct}%)` : `Rugi: -Rp ${fmt}`;
    }
    buyInput?.addEventListener('input', updateMargin);
    sellInput?.addEventListener('input', updateMargin);
    updateMargin();
});
</script>
@endpush
@endsection
