@extends('layouts.app')

@section('title', isset($customer) ? 'Edit Pelanggan' : 'Tambah Pelanggan')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="d-flex align-items-center mb-4">
            <a href="{{ route('master.customers.index') }}" class="btn btn-light border btn-sm me-3">
                <i class="ph ph-arrow-left"></i>
            </a>
            <h4 class="fw-bold mb-0" style="color: #1E293B;">
                {{ isset($customer) ? 'Edit Pelanggan' : 'Tambah Pelanggan Baru' }}
            </h4>
        </div>

        <div class="card">
            <div class="card-body p-4">
                <form action="{{ isset($customer) ? route('master.customers.update', $customer->id) : route('master.customers.store') }}" method="POST">
                    @csrf
                    @if(isset($customer))
                        @method('PUT')
                    @endif

                    <div class="mb-4">
                        <label class="form-label fw-medium">Nama Pelanggan</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $customer->name ?? '') }}" placeholder="Contoh: Bapak Budi / PT Maju" required autofocus>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-medium">Tipe Pelanggan</label>
                            <select class="form-select @error('type') is-invalid @enderror" name="type" required>
                                <option value="reguler" {{ old('type', $customer->type ?? '') == 'reguler' ? 'selected' : '' }}>Reguler</option>
                                <option value="toko" {{ old('type', $customer->type ?? '') == 'toko' ? 'selected' : '' }}>Toko</option>
                                <option value="kontraktor" {{ old('type', $customer->type ?? '') == 'kontraktor' ? 'selected' : '' }}>Kontraktor</option>
                            </select>
                            @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-medium">Nomor Telepon</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone', $customer->phone ?? '') }}" placeholder="Contoh: 08123456789">
                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium">Alamat Lengkap</label>
                        <textarea class="form-control @error('address') is-invalid @enderror" name="address" rows="3" placeholder="Tuliskan alamat lengkap...">{{ old('address', $customer->address ?? '') }}</textarea>
                        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" {{ old('is_active', $customer->is_active ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label ms-2" for="is_active">Status Pelanggan Aktif</label>
                        </div>
                    </div>

                    <hr class="mb-4">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('master.customers.index') }}" class="btn btn-light border">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection