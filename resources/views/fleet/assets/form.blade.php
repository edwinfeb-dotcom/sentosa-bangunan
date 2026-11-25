@extends('layouts.app')

@section('title', isset($asset) ? 'Edit Aset' : 'Tambah Aset')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex align-items-center gap-3 mb-4">
            <a href="{{ route('fleet.assets.index') }}" class="btn btn-light border btn-sm"><i class="ph ph-arrow-left"></i></a>
            <h4 class="fw-bold mb-0">{{ isset($asset) ? 'Edit Data Aset' : 'Tambah Aset Baru' }}</h4>
        </div>

        <div class="card">
            <div class="card-body p-4">
                <form action="{{ isset($asset) ? route('fleet.assets.update', $asset->id) : route('fleet.assets.store') }}" method="POST">
                    @csrf
                    @if(isset($asset)) @method('PUT') @endif

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Kode Aset</label>
                            <input type="text" name="code" class="form-control" value="{{ old('code', $asset->code ?? ($suggestedCode ?? '')) }}" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Nama Aset</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $asset->name ?? '') }}" placeholder="Contoh: Komputer Kasir 1 / Truk Canter" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Kategori</label>
                            <input type="text" name="category" class="form-control" value="{{ old('category', $asset->category ?? '') }}" placeholder="Komputer / Bangunan / Kendaraan" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tanggal Perolehan</label>
                            <input type="date" name="purchase_date" class="form-control" value="{{ old('purchase_date', isset($asset) ? $asset->purchase_date->format('Y-m-d') : date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Kondisi</label>
                            <select name="condition" class="form-select" required>
                                <option value="baik" {{ old('condition', $asset->condition ?? '') == 'baik' ? 'selected' : '' }}>Baik</option>
                                <option value="perlu_perbaikan" {{ old('condition', $asset->condition ?? '') == 'perlu_perbaikan' ? 'selected' : '' }}>Perlu Perbaikan</option>
                                <option value="rusak" {{ old('condition', $asset->condition ?? '') == 'rusak' ? 'selected' : '' }}>Rusak</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Harga Perolehan (Rp)</label>
                            <input type="number" name="purchase_price" class="form-control" value="{{ old('purchase_price', $asset->purchase_price ?? 0) }}" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nilai Residu / Sisa (Rp)</label>
                            <input type="number" name="residual_value" class="form-control" value="{{ old('residual_value', $asset->residual_value ?? 0) }}" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Umur Ekonomis (Tahun)</label>
                            <input type="number" name="useful_life_years" class="form-control" value="{{ old('useful_life_years', $asset->useful_life_years ?? 5) }}" min="1" max="50" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Catatan (Opsional)</label>
                        <textarea name="notes" class="form-control" rows="2">{{ old('notes', $asset->notes ?? '') }}</textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('fleet.assets.index') }}" class="btn btn-light border">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan Aset</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
