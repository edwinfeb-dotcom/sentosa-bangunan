@extends('layouts.app')

@section('title', isset($supplier) ? 'Edit Supplier' : 'Tambah Supplier')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="d-flex align-items-center mb-4">
            <a href="{{ route('master.suppliers.index') }}" class="btn btn-light border btn-sm me-3">
                <i class="ph ph-arrow-left"></i>
            </a>
            <h4 class="fw-bold mb-0" style="color: #1E293B;">
                {{ isset($supplier) ? 'Edit Supplier' : 'Tambah Supplier Baru' }}
            </h4>
        </div>

        <div class="card">
            <div class="card-body p-4">
                <form action="{{ isset($supplier) ? route('master.suppliers.update', $supplier->id) : route('master.suppliers.store') }}" method="POST">
                    @csrf
                    @if(isset($supplier))
                        @method('PUT')
                    @endif

                    <div class="mb-4">
                        <label class="form-label fw-medium">Nama Supplier</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $supplier->name ?? '') }}" placeholder="Contoh: PT Bangun Jaya" required autofocus>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium">Nomor Telepon</label>
                        <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone', $supplier->phone ?? '') }}" placeholder="Contoh: 08123456789">
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium">Alamat Lengkap</label>
                        <textarea class="form-control @error('address') is-invalid @enderror" name="address" rows="3" placeholder="Tuliskan alamat lengkap...">{{ old('address', $supplier->address ?? '') }}</textarea>
                        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" {{ old('is_active', $supplier->is_active ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label ms-2" for="is_active">Status Supplier Aktif</label>
                        </div>
                    </div>

                    <hr class="mb-4">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('master.suppliers.index') }}" class="btn btn-light border">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection